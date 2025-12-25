<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWithdrawInfo extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $table = 'user_withdraw_info';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'config',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'type' => 'integer',
        'config' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
