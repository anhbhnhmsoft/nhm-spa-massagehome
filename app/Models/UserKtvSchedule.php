<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;

class UserKtvSchedule extends Model
{
    use HasBigIntId;

    protected $table = 'user_ktv_schedules';

    /**
     * Các trường có thể gán dữ liệu hàng loạt (Mass Assignment)
     */
    protected $fillable = [
        'ktv_id',
        'working_schedule',
        'is_working',
    ];

    /**
     * Tự động cast dữ liệu khi truy vấn
     */
    protected $casts = [
        'id' => 'string',
        'ktv_id' => 'string',
        'working_schedule' => 'array',
        'is_working' => 'boolean',
    ];

    /**
     * Thiết lập quan hệ với bảng Users
     * Một bản ghi lịch trình thuộc về một Kỹ thuật viên
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'ktv_id');
    }
}
