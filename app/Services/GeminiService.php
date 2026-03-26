<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\ServiceException;
use App\Enums\ConfigName;
use App\Enums\GeminiAiModel;
use App\Enums\Language;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected ?string $apiKey;
    protected ?GeminiAiModel $model;
    public function __construct(
        protected ConfigService $configService,
    )
    {

    }

    /**
     * Dịch văn bản
     * @param string $text
     * @param Language $lang
     * @return string
     * @throws ServiceException
     * @throws ConnectionException
     */
    public function translate(string $text, Language $lang): string
    {
        if (!$this->checkBoot()) {
            $this->bootService();
        }

        $text = trim($text);
        if ($text === '') return '';

        $response = $this->handleCallGemini([
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => "Translate to {$lang->label()}. Only return translation, no explanation: \n\n {$text}"
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0,
            ]
        ]);

        if ($response->failed()) {
            LogHelper::error('Gemini translate error', null, [
                'status' => $response->status(),
                'body' => $response->body(), // Rất quan trọng để biết API chửi gì
                'text' => $text,
                'lang' => $lang->value,
            ]);
            throw new ServiceException(__('common_error.server_error'));
        }

        $result = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if ($result === null) {
            LogHelper::error('Gemini API Missing Text (Possible Safety Block)', null, [
                'response' => $response->json(),
                'status' => $response->status(),
                'body' => $response->body(),
                'text' => $text,
                'lang' => $lang->value,
            ]);
            throw new ServiceException(__('common_error.server_error'));
        }

        return trim(preg_replace('/^[\-\*\s"]+|["]+$/', '', $result));
    }

    /**
     * @throws ServiceException
     * @throws ConnectionException
     */
    public function generateReview(int $rating, Language $lang): string
    {
        if (!$this->checkBoot()) {
            $this->bootService();
        }

        $langLabel = $lang->label();

        // Thêm chỉ dẫn cụ thể về ngành Spa và yêu cầu không cắt ngang
        $prompt = "Write a complete, natural customer review for a Spa & Massage service in {$langLabel}. " .
            "Rating: {$rating}/5 stars. " .
            "Context: Experience with therapist, ambiance, and relaxation. " .
            "Length: Exactly 1 full sentences. " . // Yêu cầu chỉ 1 câu để dễ đọc
            "Important: Do not leave the sentence unfinished. Return only the review text.";

        $response = $this->handleCallGemini([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.85,
                'topP' => 0.95,
                'maxOutputTokens' => 600, // max token để chứa 1 câu
            ]
        ]);

        // ... (phần check response giữ nguyên)

        $result = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (!$result) {
            return match ($lang) {
                Language::VIETNAMESE => "Dịch vụ spa tuyệt vời, nhân viên rất tận tâm và tay nghề cao. Tôi sẽ quay lại.",
                Language::CHINESE => "スパサービスは素晴らしかった。非常に注意してくださったTherapistで、高品質なサービスを提供してくれました。もう一度来ます。",
                default => "The spa service was excellent, with a very attentive and high-quality therapist. I will return again.",
            };
        }

        // Làm sạch kết quả: bỏ ngoặc kép, bỏ dấu gạch đầu dòng nếu AI tự thêm
        return trim(preg_replace('/^[\-\*\s]+/', '', str_replace(['"', '“', '”'], '', $result)));
    }




    /**
     *  ---- Protected methods ----
     */

    /**
     * Kiểm tra xem service đã được boot chưa
     * @return bool
     */
    protected function checkBoot(): bool
    {
        return isset($this->apiKey) && isset($this->model);
    }
    /**
     * Hàm nạp cấu hình từ ConfigService
     * @throws ServiceException
     */
    protected function bootService(): void
    {
        // Lấy API Key từ hệ thống config của bạn
        $this->apiKey = $this->configService->getConfigValue(ConfigName::GEMINI_API_KEY);

        // Lấy Model mặc định
        $this->model = GeminiAiModel::GEMINI_2_5_FLASH;
    }

    /**
     * Gọi API Gemini
     * @param array $data
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Promises\LazyPromise|\Illuminate\Http\Client\Response
     * @throws ConnectionException
     */
    protected function handleCallGemini(array $data)
    {
        return Http::timeout(45) // Tăng timeout lên một chút
        ->withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])
            // Tự động thử lại 3 lần, mỗi lần cách nhau 2 giây
            // Chỉ thử lại khi gặp lỗi Connection hoặc HTTP status 429, 500, 502, 503, 504
            ->retry(3, 2000, function ($exception, $request) {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                $response = $exception->response ?? null;
                return $response && in_array($response->status(), [429, 500, 502, 503, 504]);
            })
            ->post($this->model->endpoint(), $data);
    }

}
