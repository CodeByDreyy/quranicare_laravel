<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function hasPermission($permission)
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    public function canManageContent()
    {
        return in_array($this->role, ['super_admin', 'content_admin']);
    }

    public function canManageUsers()
    {
        return $this->role === 'super_admin';
    }

    public function canModerate()
    {
        return in_array($this->role, ['super_admin', 'moderator']);
    }
}
