<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasBigIntId;

    protected $table = 'user_address';

    protected $fillable = [
        'id',
        'user_id',
        'address',
        'latitude',
        'longitude',
        'desc',
        'is_primary',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'latitude' => 'string',
        'longitude' => 'string',
        'is_primary' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
