<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = [
        'user_id', 'tool', 'query', 'result_json',
        'status', 'error_message', 'ip_address',
    ];

    protected $casts = ['result_json' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
