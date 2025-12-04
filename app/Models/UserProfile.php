<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory, HasBigIntId;

    protected $table = 'user_profiles';
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'avatar_url',
        'date_of_birth',
        'gender', // Cast Enum Gender
        'bio',
    ];
    protected $casts = [
        'user_id' => 'string',
        'date_of_birth' => 'date',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


}
