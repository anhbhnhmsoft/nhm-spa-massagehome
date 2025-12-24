<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
    */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1️⃣ Kiểm tra trống
        if (empty($value) || trim($value) === '') {
            $fail(__('validation.password.required'));
            return;
        }

        // 2️⃣ Kiểm tra độ dài
        if (strlen($value) < 8) {
            $fail(__('validation.password.min', ['min' => 8]));
            return;
        }

        // 3️⃣ Kiểm tra chữ hoa, chữ thường, chữ số
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $value)) {
            $fail(__('validation.password.regex'));
        }
    }
}
