<?php

namespace App\Core\Controller;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, HandleApi;
}
