<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

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

    protected function avatarUrlFull(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->avatar_url)) {
                    return null;
                }
                // Nếu dữ liệu bị lỗi dấu \ (do Windows), sửa lại cho an toàn
                $path = str_replace('\\', '/', $this->avatar_url);
                return Storage::disk('public')->url($path);
            }
        );
    }
    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


}
