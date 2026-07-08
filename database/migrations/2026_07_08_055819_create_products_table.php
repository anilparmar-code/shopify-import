<?php

use App\Models\ImportRecord;
use App\Models\Upload;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Upload::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ImportRecord::class)->constrained()->cascadeOnDelete();
            $table->string('shopify_product_id')->nullable()->index();
            $table->string('shopify_variant_id')->nullable()->index();
            $table->string('handle')->unique();
            $table->string('title');
            $table->string('vendor');
            $table->string('product_type');
            $table->text('body_html')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('published')->default(true);
            $table->string('variant_sku')->nullable();
            $table->decimal('variant_price', 10, 2)->default(0.00);
            $table->decimal('variant_compare_at_price', 10, 2)->nullable();
            $table->decimal('variant_weight', 10, 2)->nullable();
            $table->string('variant_weight_unit')->nullable();
            $table->integer('variant_inventory_qty')->default(0);
            $table->string('variant_inventory_policy')->nullable();
            $table->string('variant_inventory_tracker')->nullable();
            $table->boolean('variant_requires_shipping')->default(true);
            $table->string('variant_fulfillment_service')->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt_text')->nullable();
            $table->integer('image_position')->nullable();
            $table->enum('status', ['pending', 'processing', 'successful', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
