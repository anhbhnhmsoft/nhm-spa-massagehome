<?php

namespace App\Http\Requests\API\Booking;

use App\Rules\CoordinateRule;
use Illuminate\Foundation\Http\FormRequest;

class PrepareBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'numeric'],
            'option_id' => ['required', 'numeric'],
            'ktv_id' => ['required', 'numeric'],
            'latitude' => ['required', new CoordinateRule('lat')],
            'longitude' => ['required', new CoordinateRule('lng')],
            'coupon_id' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => __('validation.category_id.required'),
            'category_id.numeric' => __('validation.category_id.invalid'),
            'option_id.required' => __('validation.option_id.required'),
            'option_id.numeric' => __('validation.option_id.numeric'),
            'ktv_id.required' => __('validation.ktv_id.required'),
            'ktv_id.numeric' => __('validation.ktv_id.numeric'),
            'latitude.required' => __('validation.location.latitude_required'),
            'longitude.required' => __('validation.location.longitude_required'),
            'coupon_id.numeric' => __('validation.coupon.invalid'),
        ];
    }
}
