<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Enums\ServiceDuration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Set true để cho phép user gọi API này
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // 1. Thông tin chung
            'category_id' => ['required', 'numeric', 'exists:categories,id'],
            'is_active' => ['required', 'boolean'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:20480'], // 20MB

            // 2. Tên dịch vụ (Đa ngôn ngữ)
            'name' => ['required', 'array', function ($attribute, $value, $fail) {
                $this->validateMultilingual($value, $fail, 'name');
            }],
            'name.*' => ['nullable', 'string', 'max:255'],

            // 3. Mô tả (Đa ngôn ngữ)
            'description' => ['required', 'array', function ($attribute, $value, $fail) {
                $this->validateMultilingual($value, $fail, 'description');
            }],
            'description.*' => ['nullable', 'string', 'max:1000'],

//            // 4. Các gói tùy chọn (Options)
//            'options' => ['required', 'array', 'min:1'],
//
//            // 5. Kiểm tra từng phần tử trong mảng options
//            'options.*' => [
//                'required',
//                'numeric',
//                // Đảm bảo không gửi trùng ID trong mảng
//                'distinct',
//                // Kiểm tra ID có tồn tại trong bảng 'category_prices' cột 'id'
//                Rule::exists('category_prices', 'id')->where(function ($query) {
//                    // Bằng với giá trị 'category_id' gửi lên từ request
//                    return $query->where('category_id', $this->input('category_id'));
//                }),
//            ]
        ];
    }

    /**
     * Helper function để validate logic đa ngôn ngữ (Tránh lặp code)
     */
    private function validateMultilingual($value, $fail, $for)
    {
        // 1. Lọc bỏ giá trị rỗng
        $filled = array_filter($value, fn($v) => !empty(trim($v ?? '')));

        if (empty($filled)) {
            if ($for === 'name') {
                $fail(__('validation.name_service.required'));
            } else {
                $fail(__('validation.description_service.required'));
            }
            return;
        }

        $allowedLangs = Language::values();
        $invalidKeys = array_diff(array_keys($value), $allowedLangs);
        if (!empty($invalidKeys)) {
            if ($for === 'name') {
                $fail(__('validation.name_service.invalid') . ': ' . implode(', ', $invalidKeys));
            } else {
                $fail(__('validation.description_service.invalid') . ': ' . implode(', ', $invalidKeys));
            }
        }
    }

    /**
     * Tùy chỉnh thông báo lỗi
     */
    public function messages(): array
    {
        return [
            // Category
            'category_id.required' => __('validation.category_id.required'),
            'category_id.numeric' => __('validation.category_id.invalid'),
            'category_id.exists' => __('validation.category_id.invalid'),

            // Image
            'image.required' => __('validation.image.required'),
            'image.max' => __('validation.image.max'),
            'image.mimes' => __('validation.image.mimes'),

            // Name
            'name.required' => __('validation.name_service.required'),
            'name.array' => __('validation.name_service.invalid'),
            'name.*.max' => __('validation.name_service.max'),

            // Description
            'description.required' => __('validation.description_service.required'),
            'description.array' => __('validation.description_service.invalid'),
            'description.*.max' => __('validation.description_service.max'),

//            // Options
//            'options.required' => __('validation.option_service.required'),
//            'options.min' => __('validation.option_service.required'),
//
//            // Option Details
//            'options.*.required' => __('validation.option_service.required'),
//            'options.*.numeric' => __('validation.option_service.invalid'),
//            'options.*.exists' => __('validation.option_service.invalid'),
//            'options.*.distinct' => __('validation.option_service.distinct'),
        ];
    }
}
