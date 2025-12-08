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

    public function handleWebhookPayOs(Request $request)
    {

    }


    public function createPaymentService(Request $request)
    {

    }

}
