<?php

namespace App\Models;

use App\Enums\Upload\UploadStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    protected $fillable = [
        'file_path',
        'original_file_name',
        'status',
        'total_records',
        'processed_records',
        'success_records',
        'failed_records',
        'mapping_json',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'success_records' => 'integer',
        'failed_records' => 'integer',
        'status' => UploadStatusEnum::class,
        'mapping_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function importRecords(): HasMany
    {
        return $this->hasMany(ImportRecord::class);
    }
}
