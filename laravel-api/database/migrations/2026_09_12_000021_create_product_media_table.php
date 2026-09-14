<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->string('disk', 40);
            $table->string('path', 500);
            $table->string('url', 1000);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['product_id', 'variant_id', 'sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('product_media'); }
};
