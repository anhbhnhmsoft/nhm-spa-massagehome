<?php

namespace App\Http\Controllers\Web;

use App\Core\Controller\BaseController;

class HomeController extends BaseController
{

    public function index()
    {
        $chplay = config('services.store.chplay');
        $appstore = config('services.store.appstore');
        return view('web.home', compact('chplay', 'appstore'));
    }
}
