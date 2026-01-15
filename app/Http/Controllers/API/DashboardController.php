<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\DateRangeDashboard;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends BaseController
{

    public function __construct(
        protected DashboardService $dashboardService
    )
    {

    }



}
