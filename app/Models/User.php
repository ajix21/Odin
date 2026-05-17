<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password',
        'role', 'is_active', 'api_token',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token', 'api_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isOperator(): bool { return $this->role === 'operator'; }
    public function canSearch(): bool  { return in_array($this->role, ['admin', 'operator']); }

    public function searchLogs() { return $this->hasMany(SearchLog::class); }
}
