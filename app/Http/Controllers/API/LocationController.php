<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends BaseController
{
    /**
     * search map
     */
    public function search(Request $request)
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
            'input' => "Hanoi",
            'key' => "AIzaSyDc5gJqICVzNvMKQrs7UEgRBpVF89le6lo",
            'sessiontoken' => $request->input('session_token'),
            'components' => 'country:vn',
            'language' => 'vi',
        ]);
    }
}
