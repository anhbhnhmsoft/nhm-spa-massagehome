<?php

namespace App\Services;

use App\Core\Service\ServiceException;
use App\Enums\ConfigName;
use App\Enums\GeminiAiModel;
use App\Enums\Language;
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
     * Dịch văn bản
     * @param string $text
     * @param Language $lang
     * @return string
     * @throws ServiceException
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function translate(string $text, Language $lang): string
    {
        $this->bootService();

        if (empty(trim($text))) return '';

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey, // Dùng header theo doc mới
            'Content-Type' => 'application/json',
        ])->post($this->model->endpoint(), [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => "Translate to {$lang->getFullLanguageName()}. Only return translation, no explanation: \n\n {$text}"
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
            ]
        ]);
        if ($response->failed()) {
            throw new ServiceException(
                __('common_error.server_error')
            );
        }
        $result = $response->json('candidates.0.content.parts.0.text') ?? '';
        return trim($result);
    }
}
