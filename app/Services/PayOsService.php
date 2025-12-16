<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class PayOsService extends BaseService
{
    protected ?array $config = null;

    const URL_PAYMENT = 'https://api-merchant.payos.vn/v2/';

    public function __construct(
        protected  ConfigService $configService,
    )
    {
        parent::__construct();
        $this->initConfigPayOs();
    }
    /**
     * --------- protected methods ---------
     */

    /**
     * Khởi tạo cấu hình thanh toán PayOS.
     * @return void
     */
    protected function initConfigPayOs(): void
    {
        $clientId = $this->configService->getConfig(ConfigName::PAYOS_CLIENT_ID);
        $apiKey = $this->configService->getConfig(ConfigName::PAYOS_API_KEY);
        $checksumKey = $this->configService->getConfig(ConfigName::PAYOS_CHECKSUM_KEY);
        if ($clientId->isSuccess() && $apiKey->isSuccess() && $checksumKey->isSuccess()) {
            $this->config =  [
                'client_id' => $clientId->getData()['config_value'],
                'api_key' => $apiKey->getData()['config_value'],
                'checksum_key' => $checksumKey->getData()['config_value'],
            ];
        }else{
            $this->config = null;
            LogHelper::error(message: "Lỗi không tìm thấy cấu hình thanh toán PayOS");
        }
    }

    /**
     * Lấy cấu hình thanh toán PayOS.
     * @return array Cấu hình thanh toán PayOS.
     * @throws ServiceException
     */
    protected function getConfigPayOs(): array
    {
        if (empty($this->config)) {
            throw new ServiceException(__('common_error.payment_error'));
        }
        return $this->config;
    }

    /**
     * Tạo đường dẫn thanh toán PayOS.
     * @param string $link
     * @return string
     */
    protected function createLink(string $link): string
    {
        return self::URL_PAYMENT . $link;
    }

    /**
     * Tạo chữ ký signature cho yêu cầu thanh toán PayOS.
     * @param array $data Dữ liệu thanh toán.
     * @param string $key Khóa kiểm tra chữ ký.
     * @return string Chữ ký signature.
     */
    protected function generateSignature(array $data, string $key): string
    {
        ksort($data);

        $dataString = urldecode(http_build_query($data));

        return hash_hmac('sha256', $dataString, $key);
    }


    /**
     * --------- public methods ---------
     */

    /**
     * Tạo thanh toán PayOS.
     * return dạng như sau
     * [
     * "code" => "00"
     * "desc" => "success"
     * "data" => array:12 [
         * "bin" => "970422"
         * "accountNumber" => "VQRQAFVNT0008"
         * "accountName" => "MAI VAN TRUONG"
         * "amount" => 100000
         * "description" => "QRBK251211063113259750"
         * "orderCode" => 1765434673259
         * "currency" => "VND"
         * "paymentLinkId" => "ece8cdfd443741d0a271db075c6afa38"
         * "status" => "PENDING"
         * "expiredAt" => 1765435573
         * "checkoutUrl" => "https://pay.payos.vn/web/ece8cdfd443741d0a271db075c6afa38"
         * "qrCode" => "00020101021238570010A000000727012700069704220113VQRQAFVNT00080208QRIBFTTA530370454061000005802VN62260822QRBK2512110631132597506304EA16"
     * ]
     * "signature" => "2f0054a0b6ab4a23d5697d3d1d466e5f15cc6f4d3499e40f573b84ebdd201849"
     * ]
     *
     *
     *
     * @param int $amount Số tiền thanh toán (đơn vị: VND).
     * @param string $cancelUrl URL để chuyển hướng khi người dùng hủy thanh toán.
     * @param string $description Mô tả thanh toán.
     * @param int $orderCode Mã đơn hàng - trùng với ID của transaction.
     * @param string $returnUrl URL để chuyển hướng khi thanh toán thành công.
     * @param Carbon $expiredAt Thời gian hết hạn thanh toán.
     * @return ServiceReturn
     */
    public function createPayment(
        int $amount ,
        string $cancelUrl,
        string $description,
        int $orderCode,
        string $returnUrl,
        Carbon $expiredAt
    ): ServiceReturn
    {
        try {
            $config = $this->getConfigPayOs();

            $payload = [
                'amount' => $amount,
                'cancelUrl' => $cancelUrl,
                'description' => $description,
                'orderCode' => $orderCode,
                'returnUrl' => $returnUrl,
            ];
            $signature = $this->generateSignature($payload, $config['checksum_key']);

            $response = Http::withHeaders([
                'X-Client-Id' => $config['client_id'],
                'X-Api-Key'   => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(15)
                ->post(
                    url: $this->createLink('payment-requests'),
                    data: array_merge($payload, [
                        'expiredAt' => $expiredAt->timestamp,
                        'signature' => $signature
                    ])
                );
            if ($response->failed()) {
                throw new ServiceException(__('common_error.payment_error'));
            }
            $data = $response->json();
            if ($data['code'] !== '00') {
                LogHelper::error(
                    message: "Lỗi PayOsService@createPayment",
                    context: [
                        'response' => $data ,
                        'payload' => $payload
                    ]
                );
                throw new ServiceException(__('common_error.payment_error'));
            }
            return ServiceReturn::success($data);
        }
        catch (ServiceException $e){
            return ServiceReturn::error($e->getMessage());
        }
        catch (\Exception $e){
            LogHelper::error(
                message: "Lỗi PayOsService@createPayment",
                ex: $e
            );
            return ServiceReturn::error(__('common_error.payment_error'));
        }
    }

    /**
     * Kiểm tra dữ liệu từ PayOS có hợp lệ không.
     * @param $transaction
     * @param $transaction_signature
     * @return bool
     * @throws ServiceException
     */
    public function isValidOsPayData($transaction, $transaction_signature): bool
    {
        $config = $this->getConfigPayOs();
        ksort($transaction);
        $transaction_str_arr = [];
        foreach ($transaction as $key => $value) {
            if (in_array($value, ["undefined", "null"]) || gettype($value) == "NULL") {
                $value = "";
            }

            if (is_array($value)) {
                $valueSortedElementObj = array_map(function ($ele) {
                    ksort($ele);
                    return $ele;
                }, $value);
                $value = json_encode($valueSortedElementObj, JSON_UNESCAPED_UNICODE);
            }
            $transaction_str_arr[] = $key . "=" . $value;
        }
        $transaction_str = implode("&", $transaction_str_arr);
        $signature = hash_hmac("sha256", $transaction_str, $config['checksum_key']);
        return $signature == $transaction_signature;
    }
}
