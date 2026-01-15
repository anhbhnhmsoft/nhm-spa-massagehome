<?php

namespace App\Http\Controllers\Web;

use App\Core\Controller\BaseController;
use App\Services\ZaloService;
use Illuminate\Http\Request;

class ZaloController extends BaseController
{

    public function __construct(protected ZaloService $zaloService)
    {
    }

    public function hook()
    {
        return $this->sendSuccess(
            data: [['status' => 'success']],
        );
    }

    public function callback(Request $request)
    {
        $code = $request->string('code');
        $error = $request->input('error');
        if ($error) {
            return $this->sendError(
                message: 'Zalo permission denied',
            );
        }
        if (!$code) {
            return $this->sendError(
                message: 'Authorization code not found',
            );
        }

        $result = $this->zaloService->initAccessToken($code);

        if ($result->isError()) {
            return $this->sendError(
                message: 'Failed to get access token from Zalo',
                code: 500,
            );
        }

        return $this->sendSuccess(
            data: true,
            message: 'Zalo Token Initialized Successfully',
        );
    }
}
