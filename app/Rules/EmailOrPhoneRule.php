<?php

namespace App\Rules;

use App\Core\Helper;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailOrPhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $isEmail = Helper::validEmail($value);
        $isPhone = Helper::validPhone($value);

        if (!$isEmail && !$isPhone) {
            $fail(__('validation.username.email_or_phone'));
        }
    }
}
