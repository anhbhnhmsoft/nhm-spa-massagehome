<?php

namespace App\Http\Requests;

use App\Enums\KTVConfigSchedules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditConfigScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_working' => ['required', 'boolean'],
            // Validate mảng
            'working_schedule' => ['required', 'array', 'size:7'],
            // Validate từng phần tử trong mảng
            'working_schedule.*.day_key' => [
                'required',
                Rule::enum(KTVConfigSchedules::class)
            ],

            'working_schedule.*.active' => ['required', 'boolean'],

            'working_schedule.*.start_time' => [
                'exclude_if:working_schedule.*.active,false', // Thêm dòng này
                'required',
                'date_format:H:i'
            ],

            'working_schedule.*.end_time' => [
                'exclude_if:working_schedule.*.active,false', // Thêm dòng này
                'required',
                'date_format:H:i',
                'after:working_schedule.*.start_time'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'is_working.required' => __('validation.is_working.required'),
            'is_working.boolean' => __('validation.is_working.invalid'),
            'working_schedule.required' => __('validation.working_schedule.required'),
            'working_schedule.array' => __('validation.working_schedule.array'),
            'working_schedule.size' => __('validation.working_schedule.size'),
            'working_schedule.*.day_key.required' => __('validation.working_schedule.day_key.required'),
            'working_schedule.*.active.required' => __('validation.working_schedule.active.required'),
            'working_schedule.*.start_time.required_if' => __('validation.working_schedule.start_time.required_if'),
            'working_schedule.*.end_time.required_if' => __('validation.working_schedule.end_time.required_if'),
            'working_schedule.*.start_time.date_format' => __('validation.working_schedule.start_time.date_format'),
            'working_schedule.*.end_time.date_format' => __('validation.working_schedule.end_time.date_format'),
            'working_schedule.*.end_time.after' => __('validation.working_schedule.end_time.after'),
        ];
    }
}
