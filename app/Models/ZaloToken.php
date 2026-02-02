<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZaloToken extends Model
{
    protected $table = "zalo_tokens";

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_in',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
