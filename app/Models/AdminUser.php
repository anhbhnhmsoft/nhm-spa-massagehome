<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use App\Enums\Admin\AdminRole;
use App\Enums\Language;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements FilamentUser
{
    use  HasBigIntId, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'language',
        'password',
        'is_active',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'id' => 'string',
        'language' => Language::class,
        'password' => 'hashed',
        'is_active' => 'boolean',
        'role' => AdminRole::class, // Map với Enum của bạn
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }


    public function hasRole(AdminRole $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
}
