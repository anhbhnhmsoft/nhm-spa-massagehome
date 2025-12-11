<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Helper;
use App\Enums\PaymentType;
use App\Http\Resources\Payment\WalletResourcePayment;
use App\Services\PaymentService;
use App\Services\PayOsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends BaseController
{

    public function __construct(
        protected PayOsService $payOsService,
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
            userId: Auth::id() ?? 0,
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $data = $resService->getData();
        return $this->sendSuccess(
            data: new WalletResourcePayment($data),
        );
    }



    public function handleWebhookPayOs(Request $request)
    {

    }


    public function createPaymentService(Request $request)
    {

    }

}
