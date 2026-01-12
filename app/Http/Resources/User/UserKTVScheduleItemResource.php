<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserKTVScheduleItemResource extends JsonResource
{
     public function toArray(Request $request): array
    {
        $workingSchedules = $this->working_schedule;
        // Mapping lại start_time và end_time nếu không có giá trị
        foreach ($workingSchedules as $key => $workingSchedule) {
            $workingSchedules[$key] = [
                'active' => $workingSchedule['active'],
                'start_time' => $workingSchedule['start_time'] ?? "08:00",
                'end_time' => $workingSchedule['end_time'] ?? "16:00",
                'day_key' => $workingSchedule['day_key'],
            ];
        }
        return [
            'id' => $this->id,
            'ktv_id' => $this->ktv_id,
            'working_schedule' => $workingSchedules,
            'is_working' => $this->is_working,
        ];
    }
}
