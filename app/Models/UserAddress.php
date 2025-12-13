<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use SoftDeletes;
    protected $table = 'user_address';
    protected $fillable = [
        'user_id',
        'address',
        'latitude',
        'longitude',
        'desc',
        'is_primary',
    ];
 
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
