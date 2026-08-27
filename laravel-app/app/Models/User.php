<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function forensicCases()
    {
        return $this->hasMany(ForensicCase::class, 'assigned_to');
    }
}
