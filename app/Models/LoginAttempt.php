<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps  = false;
    const CREATED_AT    = 'created_at';

    protected $fillable = ['username', 'ip_address', 'user_agent', 'success', 'created_at'];
    protected $casts    = ['success' => 'boolean', 'created_at' => 'datetime'];
}
