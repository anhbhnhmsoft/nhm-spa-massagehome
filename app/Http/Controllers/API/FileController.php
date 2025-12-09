<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class FileController extends BaseController
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function getUserFile(Request $request, $path)
    {
        /** @var ServiceReturn $result */
        $result = $this->userService->getUserFile($path);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $file = $result->getData();
        if (!$file['is_public']) {
            if (Gate::check('download-user-file', $file)) {
                return $this->sendError(__('common_error.unauthorized'));
            }
        }

        if (!Storage::disk('private')->exists($file['file_path'])) {
            return $this->sendError(__('common_error.data_not_found'));
        }

        return Storage::disk('private')->response($file['file_path']);
    }
}
