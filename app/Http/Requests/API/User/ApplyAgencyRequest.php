<?php

namespace App\Http\Requests\API\User;

use App\Enums\UserFileType;
use App\Rules\CoordinateRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApplyAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'min:4', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', new CoordinateRule('lat')],
            'longitude' => ['required', new CoordinateRule('lng')],
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
            'nickname.required' => __('validation.nickname.for_real.required'),
            'nickname.string' => __('validation.nickname.for_real.invalid'),
            'nickname.min' => __('validation.nickname.for_real.invalid'),
            'nickname.max' => __('validation.nickname.for_real.invalid'),
            'address.required' => __('validation.address.required'),
            'address.string' => __('validation.address.string'),
            'address.max' => __('validation.address.max' , ['max' => 255]),
            'latitude.required' => __('validation.location.latitude_required'),
            'longitude.required' => __('validation.location.longitude_required'),
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
    }

}
