<?php

namespace App\Http\Requests\API\Booking;


class BookingRequest extends PrepareBookingRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'address' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'address.required' => __('validation.address.required'),
            'address.string' => __('validation.address.string'),
            'address.max' => __('validation.address.max' , ['max' => 255]),
            'note.max' => __('validation.note.max'),
        ]);
    }
}
