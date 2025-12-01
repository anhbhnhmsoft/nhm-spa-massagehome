<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class PhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        // Không dc để trống
        if (empty($value) || trim($value) === '') {
            $fail(__('auth.error.phone_invalid'));
        }

        // Kiểm tra định dạng số điện thoại Việt Nam
        if (!preg_match('/^(0[3|5|7|8|9])+([0-9]{8})\b$/', $value)) {
            $fail(__('auth.error.phone_invalid'));
        }

    }
}
