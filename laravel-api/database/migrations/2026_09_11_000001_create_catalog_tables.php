<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->string('status', 50)->index();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->boolean('is_active')->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['simple', 'variable'])->index();
            $table->string('status', 50)->index();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
            $table->unique(['attribute_id', 'value']);
            $table->unique(['id', 'attribute_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 191)->unique();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('status', 50)->index();
            $table->json('variant_data')->nullable();
            $table->char('combination_hash', 64);
            $table->timestamps();
            $table->unique(['product_id', 'combination_hash']);
        });

        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->primary(['product_variant_id', 'attribute_id'], 'variant_attribute_primary');
            $table->unique(['product_variant_id', 'attribute_value_id'], 'variant_value_unique');
            $table->foreign('product_variant_id', 'variant_attribute_variant_fk')
                ->references('id')->on('product_variants')->cascadeOnDelete();
            $table->foreign(['attribute_value_id', 'attribute_id'], 'variant_attribute_value_fk')
                ->references(['id', 'attribute_id'])->on('attribute_values')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
