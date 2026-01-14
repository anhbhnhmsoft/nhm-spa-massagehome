<?php

namespace App\Http\Controllers\Web;

use App\Services\ZaloService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ZaloController extends Controller
{
    protected $zaloService;

    public function __construct(ZaloService $zaloService)
    {
        $this->zaloService = $zaloService;
    }

    public function hook()
    {
        return response()->json(data: [['status' => 'success']], status: 200);
    }

    public function redirect(Request $request)
    {
        $callbackUrl = route('zalo.callback');
        $state = Str::random(40);
        // cần lưu state  để kiểm tra khi callback
        // session(['zalo_auth_state' => $state]);

        $url = $this->zaloService->getAuthorizationUrlForCustomer($callbackUrl, $state);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        $error = $request->input('error');
        if ($error) {
            return response()->json([
                'error' => $error,
                'message' => 'Zalo permission denied',
            ], 400);
        }

        if (!$code) {
            return response()->json([
                'message' => 'Authorization code not found',
            ], 400);
        }

        $result = $this->zaloService->getAccessTokenFromCodeForOA($code);

        if (!$result) {
            return response()->json([
                'message' => 'Failed to get access token from Zalo',
            ], 500);
        }

        return response()->json([
            'message' => 'Zalo Token Initialized Successfully',
            'data' => true,
        ]);
    }
}
