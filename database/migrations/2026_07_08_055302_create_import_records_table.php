<?php

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
        Schema::create('import_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Upload::class)->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->enum('status', ['pending', 'validated', 'invalid', 'warning'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('import_records');
    }
};
