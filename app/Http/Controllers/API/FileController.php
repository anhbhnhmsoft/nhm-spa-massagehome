<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $absolutePath = Storage::disk('private')->path($file['file_path']);
        return response()->file($absolutePath);
    }

    /**
     * Upload file tạm và trả về đường dẫn.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'], // 10MB
            'type' => ['nullable', 'integer'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->sendValidation(
                message: __('validation.error'),
                errors: $validator->errors()->toArray()
            );
        }

        $data = $validator->validated();
        $file = $request->file('file');
        $isPublic = (bool)($data['is_public'] ?? false);
        $type = $data['type'] ?? null;

        $result = $this->userService->uploadTempFile(
            file: $file,
            type: $type,
            isPublic: $isPublic
        );

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: $result->getData(),
            message: __('common.success.data_created')
        );
    }

    public function getCommercialFile(Request $request, $path)
    {
        if (Storage::disk('private')->exists($path)) {
            $absolutePath = Storage::disk('private')->path($path);
        } elseif (Storage::disk('public')->exists($path)) {
            $absolutePath = Storage::disk('public')->path($path);
        } else {
            return $this->sendError(__('common_error.data_not_found'));
        }

        return response()->file($absolutePath);
    }
}
