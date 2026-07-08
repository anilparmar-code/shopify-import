<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ErrorLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'event',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
