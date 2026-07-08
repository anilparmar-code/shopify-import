<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    protected $fillable = [
        'upload_id',
        'import_record_id',
        'shopify_product_id',
        'shopify_variant_id',
        'status',
        'handle',
        'title',
        'vendor',
        'product_type',
        'body_html',
        'tags',
        'published',
        'variant_sku',
        'variant_price',
        'variant_compare_at_price',
        'variant_weight',
        'variant_weight_unit',
        'variant_inventory_qty',
        'variant_inventory_policy',
        'variant_inventory_tracker',
        'variant_requires_shipping',
        'variant_fulfillment_service',
        'image',
        'image_alt_text',
        'image_position',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function importRecord(): BelongsTo
    {
        return $this->belongsTo(ImportRecord::class);
    }

    public function errorLogs(): MorphMany
    {
        return $this->morphMany(ErrorLog::class, 'loggable');
    }
}
