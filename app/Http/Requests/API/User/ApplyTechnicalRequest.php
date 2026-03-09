<?php

namespace App\Http\Requests\API\User;

use App\Enums\UserFileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApplyTechnicalRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'min:4', 'max:255'],
            'referrer_id' => ['nullable', 'numeric', 'exists:users,id'],
            'experience' => ['required', 'integer', 'min:1', 'max:20'],
            'is_leader' => ['nullable', 'boolean'],
            'bio' => ['required', 'string', 'min:10', 'max:1000'],
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'], // Giảm max xuống 5MB
            'dob' => ['required', 'date_format:Y-m-d'],

            // Validate mảng tổng thể
            'file_uploads' => ['required', 'array', 'min:1'],

            // Validate từng phần tử con bên trong bằng Dot Notation của Laravel
            'file_uploads.*.type_upload' => [
                'required',
                // Trực tiếp check type có nằm trong Enum hay không
                Rule::enum(UserFileType::class)
            ],
            'file_uploads.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:20480'], // max 20MB cho giấy tờ/ảnh
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => __('validation.nickname.required_if'),
            'nickname.string' => __('validation.nickname.invalid'),
            'nickname.min' => __('validation.nickname.invalid'),
            'nickname.max' => __('validation.nickname.invalid'),

            'referrer_id.numeric' => __('validation.referrer_id.numeric'),
            'referrer_id.exists' => __('validation.referrer_id.exists'),

            'experience.required' => __('validation.experience.required'),
            'experience.integer' => __('validation.experience.integer'),
            'experience.min' => __('validation.experience.min'),

            'bio.required' => __('validation.bio.required'),
            'bio.string' => __('validation.bio.invalid'),
            'bio.min' => __('validation.bio.min', ['min' => 10]),
            'bio.max' => __('validation.bio.max', ['max' => 1000]),

            'dob.required' => __('validation.date_of_birth.required'),
            'dob.date_format' => __('validation.date_of_birth.invalid'),

            'avatar.required' => __('validation.avatar.required'),
            'avatar.file' => __('validation.avatar.invalid'),
            'avatar.mimes' => __('validation.avatar.invalid'),
            'avatar.max' => __('validation.avatar.max', ['max' => 20]),

            'file_uploads.required' => __('validation.file_apply_partner_uploads.required'),
            'file_uploads.array' => __('validation.file_apply_partner_uploads.invalid'),
            'file_uploads.*.type_upload.required' => __('validation.file_apply_partner_uploads.required'),
            'file_uploads.*.type_upload.in' => __('validation.file_apply_partner_uploads.invalid_type'),
            'file_uploads.*.file.required' => __('validation.file_apply_partner_uploads.invalid', ['max' => 20]),
            'file_uploads.*.file.file' => __('validation.file_apply_partner_uploads.invalid', ['max' => 20]),
            'file_uploads.*.file.mimes' => __('validation.file_apply_partner_uploads.invalid', ['max' => 20]),
            'file_uploads.*.file.max' => __('validation.file_apply_partner_uploads.invalid', ['max' => 20]),

        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Nếu các validation cấu trúc cơ bản đã lỗi thì không cần chạy đếm số lượng nữa
                if ($validator->errors()->has('file_uploads') || $validator->errors()->has('file_uploads.*')) {
                    return;
                }

                $files = $this->input('file_uploads', []);
                $this->validateFilesCount($files, $validator);
            }
        ];
    }


    private function validateFilesCount(array $files, Validator $validator): void
    {
        // Đếm số lượng theo type
        $typeCounts = [];
        foreach ($files as $file) {
            // Chắc chắn $file là mảng vì đã qua validation basic
            if (is_array($file) && isset($file['type_upload'])) {
                $type = $file['type_upload'];
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
            }
        }

        // Kiểm tra logic số lượng cho từng loại cụ thể
        // 1. CCCD mặt trước
        if (($typeCounts[UserFileType::IDENTITY_CARD_FRONT->value] ?? 0) !== 1) {
            $validator->errors()->add('file_uploads', __('validation.file_apply_partner_uploads.count.cccd_front', ['count' => 1]));
        }

        // 2. CCCD mặt sau
        if (($typeCounts[UserFileType::IDENTITY_CARD_BACK->value] ?? 0) !== 1) {
            $validator->errors()->add('file_uploads', __('validation.file_apply_partner_uploads.count.cccd_back', ['count' => 1]));
        }

        // 3. Ảnh mặt với CCCD
        if (($typeCounts[UserFileType::FACE_WITH_IDENTITY_CARD->value] ?? 0) !== 1) {
            $validator->errors()->add('file_uploads', __('validation.file_apply_partner_uploads.count.face_with_id', ['count' => 1]));
        }

        // 4. Ảnh hiển thị của KTV với KH
        $ktvImageCount = $typeCounts[UserFileType::KTV_IMAGE_DISPLAY->value] ?? 0;
        if ($ktvImageCount < 3 || $ktvImageCount > 5) {
            $validator->errors()->add('file_uploads', __('validation.file_apply_partner_uploads.count.ktv_image_display', ['min' => 3, 'max' => 5]));
        }
    }
}
