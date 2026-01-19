<?php
/**
 * Ko dùng nữa
 */


//
//namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;
//
//use App\Enums\UserRole;
//use App\Enums\UserFileType;
//use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
//use App\Services\WalletService;
//use Filament\Resources\Pages\CreateRecord;
//use Illuminate\Database\Eloquent\Model;
//use App\Models\UserFile;
//
//class CreateKTV extends CreateRecord
//{
//    protected static string $resource = KTVResource::class;
//
//    protected array $tempFiles = [];
//
//    protected function getRedirectUrl(): string
//    {
//        return static::getResource()::getUrl('index');
//    }
//
//    protected function mutateFormDataBeforeCreate(array $data): array
//    {
//        // Set role to KTV
//        $data['role'] = UserRole::KTV->value;
//        $data['phone_verified_at'] = now();
//        $data['language'] = app()->getLocale();
//        $data['is_active'] = true;
//
//        if (isset($data['reviewApplication']) && is_array($data['reviewApplication'])) {
//            $data['reviewApplication']['role'] = UserRole::KTV->value;
//        }
//
//        if (isset($data['files']) && is_array($data['files'])) {
//            foreach ($data['files'] as $key => $file) {
//                $data['files'][$key]['role'] = UserRole::KTV->value;
//            }
//        }
//
//        if (isset($data['cccd_front_path'])) {
//            $this->tempFiles['cccd_front_path'] = $data['cccd_front_path'];
//            unset($data['cccd_front_path']);
//        }
//        if (isset($data['cccd_back_path'])) {
//            $this->tempFiles['cccd_back_path'] = $data['cccd_back_path'];
//            unset($data['cccd_back_path']);
//        }
//        if (isset($data['certificate_path'])) {
//            $this->tempFiles['certificate_path'] = $data['certificate_path'];
//            unset($data['certificate_path']);
//        }
//        if (isset($data['face_with_identity_card_path'])) {
//            $this->tempFiles['face_with_identity_card_path'] = $data['face_with_identity_card_path'];
//            unset($data['face_with_identity_card_path']);
//        }
//
//        return $data;
//    }
//
//    protected function handleRecordCreation(array $data): Model
//    {
//        $record = parent::handleRecordCreation($data);
//
//        if (isset($record->id)) {
//            $walletService = app(WalletService::class);
//            $walletService->initWalletForStaff($record->id);
//
//            // Manual save of files
//            if (isset($this->tempFiles['cccd_front_path'])) {
//                UserFile::create([
//                    'user_id' => $record->id,
//                    'type' => UserFileType::IDENTITY_CARD_FRONT,
//                    'file_path' => $this->tempFiles['cccd_front_path'],
//                    'role' => UserRole::KTV->value,
//                    'is_public' => false,
//                ]);
//            }
//            if (isset($this->tempFiles['cccd_back_path'])) {
//                UserFile::create([
//                    'user_id' => $record->id,
//                    'type' => UserFileType::IDENTITY_CARD_BACK,
//                    'file_path' => $this->tempFiles['cccd_back_path'],
//                    'role' => UserRole::KTV->value,
//                    'is_public' => false,
//                ]);
//            }
//            if (isset($this->tempFiles['certificate_path'])) {
//                UserFile::create([
//                    'user_id' => $record->id,
//                    'type' => UserFileType::LICENSE,
//                    'file_path' => $this->tempFiles['certificate_path'],
//                    'role' => UserRole::KTV->value,
//                    'is_public' => false,
//                ]);
//            }
//            if (isset($this->tempFiles['face_with_identity_card_path'])) {
//                UserFile::create([
//                    'user_id' => $record->id,
//                    'type' => UserFileType::FACE_WITH_IDENTITY_CARD,
//                    'file_path' => $this->tempFiles['face_with_identity_card_path'],
//                    'role' => UserRole::KTV->value,
//                    'is_public' => false,
//                ]);
//            }
//        }
//
//        return $record;
//    }
//}
