<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Services\ProfileService;

class ProfileController extends BaseController
{
    public function __construct(
        protected ProfileService $profileService
    )
    {
    }


    public function dashboardProfile()
    {
        $result = $this->profileService->dashboardProfile();

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
        );
    }
}
