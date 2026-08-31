<?php

namespace App\Models;

use App\Enums\CustomerRank;
use App\Enums\DemandStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCrmData extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_crm_data';
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $attributes = [
        'customer_rank' => CustomerRank::STANDARD->value,
        'demand_status' => DemandStatus::EXPLORING->value,
    ];

    protected $fillable = [
        'user_id',
        'languages',
        'province_id',
        'district_id',
        'ward_id',
        'address_detail',
        'preferred_services',
        'preferred_techniques',
        'preferred_time_slots',
        'demand_status',
        'total_spent',
        'booking_count',
        'aov',
        'first_booking_at',
        'last_booking_at',
        'favorite_ktv_ids',
        'frequent_booking_hours',
        'assigned_cskh_id',
        'customer_rank',
        'cskh_notes',
        'cskh_note',
    ];

    protected $casts = [
        'user_id' => 'string',
        'assigned_cskh_id' => 'string',
        'languages' => 'array',
        'preferred_services' => 'array',
        'preferred_techniques' => 'array',
        'preferred_time_slots' => 'array',
        'favorite_ktv_ids' => 'array',
        'frequent_booking_hours' => 'array',
        'demand_status' => DemandStatus::class,
        'customer_rank' => CustomerRank::class,
        'total_spent' => 'decimal:2',
        'aov' => 'decimal:2',
        'first_booking_at' => 'datetime',
        'last_booking_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function assignedCskh()
    {
        return $this->belongsTo(AdminUser::class, 'assigned_cskh_id', 'id');
    }
}
