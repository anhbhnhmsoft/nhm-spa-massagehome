<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Core\Helper;
use App\Enums\BankBin;
use App\Enums\PaymentType;
use App\Http\Resources\Payment\WalletResource;
use App\Http\Resources\Payment\WalletTransactionResource;
use App\Services\PaymentService;
use App\Services\PayOsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentController extends BaseController
{

    public function __construct(
        protected PaymentService $paymentService,
    )
    {
    }

    /**
     * Lấy thông tin ví của người dùng.
     */
    public function userWallet()
    {
        $resService = $this->paymentService->getUserWallet(
            userId: Auth::id(),
            withTotal: true,
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $data = $resService->getData();
        return $this->sendSuccess(
            data: new WalletResource(
                resource: $data['wallet'],
                totalDeposit: $data['total_deposit'],
                totalWithdrawal: $data['total_withdrawal'],
            ),
        );
    }

    public function listTransaction(ListRequest $request)
    {
        $dto = $request->getFilterOptions();
        $walletRes = $this->paymentService->getUserWallet(
            userId: Auth::id(),
        );
        if ($walletRes->isError()) {
            return $this->sendError(
                message: $walletRes->getMessage(),
            );
        }
        $dto->addFilter('wallet_id', $walletRes->getData()['wallet']->id);
        $resService = $this->paymentService->transactionPagination($dto);
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $data = $resService->getData();
        return $this->sendSuccess(
            data: WalletTransactionResource::collection($data)->response()->getData(),
        );
    }


    /**
     * Lấy cấu hình thanh toán.
     * @return JsonResponse
     */
    public function configPayment()
    {
        $resService = $this->paymentService->getConfigPayment();
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $data = $resService->getData();
        return $this->sendSuccess(
            data: $data,
        );
    }

    /**
     * Nạp tiền vào ví.
     * @return JsonResponse
     */
    public function deposit(Request $request)
    {
        $validate = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:50000000'],
            'payment_type' => ['required', Rule::in(PaymentType::cases())],
        ], [
            'amount.required' => __('validation.amount.required'),
            'amount.numeric' => __('validation.amount.numeric'),
            'amount.min' => __('validation.amount.min'),
            'amount.max' => __('validation.amount.max'),
            'payment_type.required' => __('validation.payment_type.required'),
            'payment_type.in' => __('validation.payment_type.in'),
        ]);

        $resService = $this->paymentService->deposit(
            amount: $validate['amount'],
            paymentType: PaymentType::from($validate['payment_type']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $data = $resService->getData();
        return $this->sendSuccess(
            data: $data,
        );
    }

    /**
     * Kiểm tra trạng thái giao dịch.
     * @param Request $request
     * @return JsonResponse
     */
    public function checkTransaction(Request $request)
    {
        $validate = $request->validate([
            'transaction_id' => ['required', 'numeric', 'exists:wallet_transactions,id'],
        ], [
            'transaction_id.required' => __('validation.transaction_id.required'),
            'transaction_id.numeric' => __('validation.transaction_id.numeric'),
            'transaction_id.exists' => __('validation.transaction_id.exists'),
        ]);

        $resService = $this->paymentService->checkTransaction(
            transactionId: $validate['transaction_id'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $data = $resService->getData();
        return $this->sendSuccess(
            data: [
                'is_completed' => $data,
            ],
        );
    }

    /**
     * Xử lý webhook PayOS.
     * @param Request $request
     */
    public function handleWebhookPayOs(Request $request)
    {
        $data = $request->all();
        // đây là orderCode test của PayOS
        if (isset($data['data']) && isset($data['data']['orderCode']) && $data['data']['orderCode'] == 123) {
            return $this->sendSuccess(message: "Giao dịch thành công");
        }
        $this->paymentService->checkWebhookPayOs($data);
        // ko cân kiểm tra lỗi vì là webhook
        return $this->sendSuccess(message: "Giao dịch thành công");
    }

    public function getBank(): JsonResponse
    {
        $bank = BankBin::getAll();

        return $this->sendSuccess(
            data: $bank,
        );
    }
}
