<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'integer', Rule::in([
                UserRole::AGENCY->value,
                UserRole::KTV->value,
            ])],
            'agency_id' => ['nullable', 'numeric', 'exists:users,id'],
            'province_code' => ['required', 'string', 'max:10'],
            'experience' => ['nullable', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            // bio đa ngôn ngữ
            'bio.vi' => ['required', 'string'],
            'bio.en' => ['nullable', 'string'],

            'bio' => ['required', 'array', function ($attribute, $value, $fail) {
                $this->validateMultilingual($value, $fail);
            }],
            'bio.*' => ['nullable', 'string', 'max:1000'],

            'file_uploads' => ['required', 'array', function ($attribute, $value, $fail) {
                if (empty($value)) {
                    $fail(__('validation.file_apply_partner_uploads.required'));
                }
                foreach ($value as $file) {
                    // Kiểm tra type_upload và file
                    if (!isset($file['type_upload']) || !isset($file['file'])) {
                        $fail(__('validation.file_apply_partner_uploads.invalid'));
                        return;
                    }
                    // Kiểm tra type_upload có hợp lệ không
                    if (!in_array($file['type_upload'], UserFileType::values())) {
                        $fail(__('validation.file_apply_partner_uploads.invalid_type'));
                        return;
                    }
                }
                $role = $this->input('role');
                if ($role == UserRole::AGENCY->value) {
                    $this->validateAgencyFiles($value, $fail);
                }
                // Kiểm tra file uploads cho role KTV
                if ($role == UserRole::KTV->value) {
                    $this->validateKtvFiles($value, $fail);
                }
            }],
            'file_uploads.*.type_upload' => [
                'required',
                'integer',
                Rule::in(UserFileType::values()),
            ],
            'file_uploads.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => __('validation.role.required'),
            'role.integer' => __('validation.role.invalid'),
            'role.in' => __('validation.role.invalid'),
            'agency_id.required' => __('validation.agency_id.required'),
            'agency_id.numeric' => __('validation.agency_id.invalid'),
            'agency_id.exists' => __('validation.agency_id.invalid'),
            'bio.required' => __('validation.bio.required'),
            'bio.array' => __('validation.bio.invalid'),
            'province_code.required' => __('validation.province_code.required'),
            'province_code.string' => __('validation.province_code.invalid'),
            'province_code.max' => __('validation.province_code.invalid'),
            'address.required' => __('validation.address.required'),
            'address.string' => __('validation.address.invalid'),
            'address.max' => __('validation.address.invalid'),
            'latitude.required' => __('validation.lat.required'),
            'latitude.numeric' => __('validation.lat.invalid'),
            'latitude.between' => __('validation.lat.invalid'),
            'longitude.required' => __('validation.lng.required'),
            'longitude.numeric' => __('validation.lng.invalid'),
            'longitude.between' => __('validation.lng.invalid'),
            'file_uploads.required' => __('validation.file_apply_partner_uploads.required'),
            'file_uploads.array' => __('validation.file_apply_partner_uploads.invalid'),
            'file_uploads.*.type_upload.required' => __('validation.file_apply_partner_uploads.invalid'),
            'file_uploads.*.type_upload.in' => __('validation.file_apply_partner_uploads.invalid_type'),
            'file_uploads.*.file.required' => __('validation.file_apply_partner_uploads.invalid'),
            'file_uploads.*.file.file' => __('validation.file_apply_partner_uploads.invalid'),
            'file_uploads.*.file.mimes' => __('validation.file_apply_partner_uploads.invalid'),
            'file_uploads.*.file.max' => __('validation.file_apply_partner_uploads.invalid'),
            'experience.required' => __('validation.experience.required'),
            'experience.integer' => __('validation.experience.integer'),
            'experience.min' => __('validation.experience.min'),
        ];
    }

    private function validateMultilingual($value, $fail)
    {
        // 1. Lọc bỏ giá trị rỗng
        $filled = array_filter($value, fn($v) => !empty(trim($v ?? '')));

        if (empty($filled)) {
            $fail(__('validation.bio.required'));
            return;
        }

        $allowedLangs = Language::values();
        $invalidKeys = array_diff(array_keys($value), $allowedLangs);
        if (!empty($invalidKeys)) {
            $fail(__('validation.bio.invalid'));
            return;
        }
    }

    // Kiểm tra file uploads cho role đại lý
    private function validateAgencyFiles(array $files, $fail): void
    {
        $requiredTypes = [
            UserFileType::IDENTITY_CARD_FRONT->value,
            UserFileType::IDENTITY_CARD_BACK->value,
            UserFileType::FACE_WITH_IDENTITY_CARD->value,
        ];

        // Đếm số lượng theo type
        $typeCounts = [];
        foreach ($files as $file) {
            $type = $file['type_upload'];
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }

        // Thiếu loại
        foreach ($requiredTypes as $type) {
            if (!isset($typeCounts[$type])) {
                $fail(__('validation.file_apply_partner_uploads.missing_type', ['type' => $type]));
                return;
            }
        }

        // Thừa (mỗi loại chỉ 1 file)
        foreach ($requiredTypes as $type) {
            if ($typeCounts[$type] !== 1) {
                $fail(__('validation.file_apply_partner_uploads.duplicate_type', ['type' => $type]));
                return;
            }
        }

        // Upload sai loại
        foreach (array_keys($typeCounts) as $uploadedType) {
            if (!in_array($uploadedType, $requiredTypes)) {
                $fail(__('validation.file_apply_partner_uploads.invalid_type_for_role'));
                return;
            }
        }
    }

    // Kiểm tra file uploads cho role KTV
    private function validateKtvFiles(array $files, $fail): void
    {
        $requiredTypes = [
            UserFileType::IDENTITY_CARD_FRONT->value,
            UserFileType::IDENTITY_CARD_BACK->value,
            UserFileType::LICENSE->value,
            UserFileType::KTV_IMAGE_DISPLAY->value,
            UserFileType::FACE_WITH_IDENTITY_CARD->value,
        ];

        // Đếm số lượng theo type
        $typeCounts = [];
        foreach ($files as $file) {
            $type = $file['type_upload'];
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }

        // Thiếu loại
        foreach ($requiredTypes as $type) {
            if (!isset($typeCounts[$type])) {
                $fail(__('validation.file_apply_partner_uploads.missing_type', ['type' => $type]));
                return;
            }
        }

        // Thừa (mỗi loại chỉ 1 file)
        foreach ($requiredTypes as $type) {
            switch ($type) {
                case UserFileType::IDENTITY_CARD_FRONT->value:
                case UserFileType::IDENTITY_CARD_BACK->value:
                case UserFileType::LICENSE->value:
                case UserFileType::FACE_WITH_IDENTITY_CARD->value:
                    if ($typeCounts[$type] !== 1) {
                        $fail(__('validation.file_apply_partner_uploads.duplicate_type', ['type' => $type]));
                        return;
                    }
                    break;
                case UserFileType::KTV_IMAGE_DISPLAY->value:
                    // Mỗi loại tối thiểu 3 file, tối đa 5 file
                    if ($typeCounts[$type] < 3 || $typeCounts[$type] > 5) {
                        $fail(__('validation.file_apply_partner_uploads.invalid_type_count', ['type' => $type]));
                        return;
                    }
                    break;
            }
        }

        // Upload sai loại
        foreach (array_keys($typeCounts) as $uploadedType) {
            if (!in_array($uploadedType, $requiredTypes)) {
                $fail(__('validation.file_apply_partner_uploads.invalid_type_for_role'));
                return;
            }
        }
    }
}
