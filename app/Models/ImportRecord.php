<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ImportRecord extends Model
{
    protected $fillable = [
        'upload_id',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

    public function errorLogs(): MorphMany
    {
        return $this->morphMany(ErrorLog::class, 'loggable');
    }
}
