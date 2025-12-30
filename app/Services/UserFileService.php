<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\ServiceReturn;
use App\Enums\DirectFile;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Repositories\UserFileRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class UserFileService
{
    public function __construct(
        protected UserFileRepository $userFileRepository
    ) {}

    /**
     * Sync user file: Create, Update or Delete based on file path.
     *
     * @param string $userId
     * @param UserFileType $type
     * @param string|null $filePath
     * @param UserRole $role
     * @return ServiceReturn
     */
    public function syncUserFile(string $userId, UserFileType $type, ?string $filePath, UserRole $role): ServiceReturn
    {
        try {
            DB::beginTransaction();
            if (empty($filePath)) {
                $this->userFileRepository->query()
                    ->where('user_id', $userId)
                    ->where('type', $type)
                    ->delete();
            } else {
                $this->userFileRepository->query()->updateOrCreate(
                    ['user_id' => $userId, 'type' => $type],
                    [
                        'file_path' => $filePath,
                        'role' => $role,
                        'is_public' => $type === UserFileType::KTV_IMAGE_DISPLAY,
                    ]
                );
            }

            DB::commit();
            return ServiceReturn::success();
        } catch (\Throwable $th) {
            LogHelper::error('UserFileService@syncUserFile: ' . $th->getMessage(), $th);
            DB::rollBack();
            return ServiceReturn::error($th->getMessage(), $th);
        }
    }

    /**
     * Create user file.
     *
     * @param string $userId
     * @param UserFileType $type
     * @param string $filePath
     * @param UserRole $role
     * @return ServiceReturn
     */
    public function createUserFile(string $userId, UserFileType $type, string $filePath, UserRole $role): ServiceReturn
    {
        try {
            $this->userFileRepository->create([
                'user_id' => $userId,
                'type' => $type,
                'file_path' => $filePath,
                'role' => $role,
                'is_public' => $type === UserFileType::KTV_IMAGE_DISPLAY,
            ]);

            return ServiceReturn::success();
        } catch (\Throwable $th) {
            LogHelper::error('UserFileService@createUserFile: ' . $th->getMessage(), $th);
            return ServiceReturn::error($th->getMessage(), $th);
        }
    }

    /**
     * Delete user file.
     *
     * @param string|int $id
     * @param string $userId
     * @return ServiceReturn
     */
    public function deleteUserFile(string|int $id, string $userId): ServiceReturn
    {
        try {
            /** @var \App\Models\UserFile $file */
            $file = $this->userFileRepository->find($id);

            if (!$file) {
                return ServiceReturn::error(__('common.error.not_found'));
            }

            if ($file->user_id !== $userId) {
                return ServiceReturn::error(__('common.error.forbidden'));
            }

            // Xóa file vật lý nếu tồn tại
            if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            $file->delete();

            return ServiceReturn::success();
        } catch (\Throwable $th) {
            LogHelper::error('UserFileService@deleteUserFile: ' . $th->getMessage(), $th);
            return ServiceReturn::error($th->getMessage(), $th);
        }
    }

    /**
     * Upload KTV images (Overwrite existing).
     *
     * @param array $images
     * @return ServiceReturn
     */
    public function uploadKtvImages(array $images): ServiceReturn
    {
        try {
            DB::beginTransaction();
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // 1. Upload new images
            $newFiles = [];
            foreach ($images as $image) {
                /** @var \Illuminate\Http\UploadedFile $image */
                $path = Storage::disk('public')->put(DirectFile::makePathById(DirectFile::KTVA, $user->id), $image);

                $newFile = $this->userFileRepository->create([
                    'user_id' => $user->id,
                    'type' => \App\Enums\UserFileType::KTV_IMAGE_DISPLAY,
                    'file_path' => $path,
                    'role' => \App\Enums\UserRole::KTV,
                    'is_public' => true,
                ]);
                $newFiles[] = $newFile;
            }
            DB::commit();

            return ServiceReturn::success(data: $newFiles);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::error('UserFileService@uploadKtvImages: ' . $th->getMessage(), $th);
            return ServiceReturn::error($th->getMessage(), $th);
        }
    }
}
