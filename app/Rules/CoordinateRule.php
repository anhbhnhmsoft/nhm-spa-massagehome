<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CoordinateRule implements ValidationRule
{
    protected string $type;

    public function __construct(string $type) // 'lat' | 'lng'
    {
        $this->type = $type;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        switch ($this->type) {
            case 'lat':
                if (!is_numeric($value)) {
                    $fail(__('validation.location.latitude_numeric'));
                    return;
                }
                $value = (float) $value;
                if ($value < -90 || $value > 90) {
                    $fail(__('validation.location.latitude_between'));
                    return;
                }
                break;
            case 'lng':
                if (!is_numeric($value)) {
                    $fail(__('validation.location.longitude_numeric'));
                    return;
                }
                $value = (float) $value;
                if ($value < -180 || $value > 180) {
                    $fail(__('validation.location.longitude_between'));
                    return;
                }
                break;
        }
    }
}
