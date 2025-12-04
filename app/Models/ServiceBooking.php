<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Core\GenerateId\HasBigIntId;

class ServiceBooking extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $fillable = [
        'user_id',
        'service_id',
        'duration',
        'booking_time',
        'start_time',
        'end_time',
        'status', // Cast Enum BookingStatus
        'price',
        'note',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'service_id' => 'string',
        'booking_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function user() // Khách hàng đặt
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
