<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\UserFileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFile extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $fillable = [
        'user_id',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'type' => UserFileType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
