<?php

namespace App\Http\Requests\API\Auth;

use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\TypeAuthenticate;
use App\Rules\PasswordRule;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'type_authenticate' => ['required', Rule::in(TypeAuthenticate::cases())],
            'phone' => [
                // Bắt buộc nếu type_authenticate là EMAIL
                'required_if:type_authenticate,' . TypeAuthenticate::EMAIL->value,
                // Cho phép null nếu không bắt buộc (tránh lỗi khi chạy PhoneRule)
                'nullable',
                // Validate định dạng số điện thoại
                new PhoneRule(),
            ],
            'password' => ['required', new PasswordRule()],
            'address' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(Gender::cases())],
            'language' => ['required', Rule::in(Language::cases())],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => __('validation.username.required'),
            'phone.required_if' => __('validation.phone.required_if'),
            'type_authenticate.required' => __('validation.type_authenticate.required'),
            'type_authenticate.in' => __('validation.type_authenticate.in'),
            'password.required' => __('validation.password.required'),
            'name.required' => __('validation.name.required'),
            'name.string' => __('validation.name.string'),
            'name.max' => __('validation.name.max', ['max' => 255]),
            'gender.required' => __('validation.gender.required'),
            'gender.in' => __('validation.gender.in'),
            'language.required' => __('validation.language.required'),
            'language.in' => __('validation.language.in'),
            'address.string' => __('validation.address.string'),
            'address.max' => __('validation.address.max', ['max' => 255]),
        ];
    }
}
