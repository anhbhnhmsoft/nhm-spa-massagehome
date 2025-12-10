<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Helper;
use App\Services\PayOsService;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{

    public function __construct(
        protected PayOsService $payOsService,
    )
    {
    }

    /**
     * Lấy thông tin ví của người dùng.
     */
    public function userWallet(Request $request)
    {
        $resService = $this->payOsService->getUserWallet();
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: $resService->getData(),
        );
    }



    public function handleWebhookPayOs(Request $request)
    {

    }


    public function createPaymentService(Request $request)
    {

    }

}
