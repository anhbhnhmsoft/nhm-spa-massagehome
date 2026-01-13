<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AgencyExistsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $agency = User::where('id', $value)
            ->where('role', UserRole::AGENCY->value)
            ->where('is_active', true)
            ->first();

        if (!$agency) {
            $fail(__('validation.agency_not_found'));
        }
    }
}

