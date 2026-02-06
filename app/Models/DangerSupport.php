<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\DangerSupportStatus;
use Illuminate\Database\Eloquent\Model;

class DangerSupport extends Model
{
    use HasBigIntId;

    protected $table = 'danger_supports';

    protected $fillable = [
        'user_id',
        'content',
        'status',
        'latitude',
        'longitude',
        'address',
        'booking_id',
    ];


    protected $casts = [
        'id' => 'string',
        'booking_id' => 'string',
        'user_id' => 'string',
        'content' => 'string',
        'status' => DangerSupportStatus::class,
        'latitude' => 'string',
        'longitude' => 'string',
        'address' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(ServiceBooking::class);
    }
}
