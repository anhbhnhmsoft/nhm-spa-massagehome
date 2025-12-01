<?php

namespace App\Http\Controllers\Web;
use App\Core\Controller\BaseController;

class HomeController extends BaseController
{


    public function index()
    {
        return $this->rendering('Home/Index');
    }

}
