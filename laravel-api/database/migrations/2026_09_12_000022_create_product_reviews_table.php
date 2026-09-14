<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 160)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('verified_purchase')->default(false);
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('product_reviews'); }
};
