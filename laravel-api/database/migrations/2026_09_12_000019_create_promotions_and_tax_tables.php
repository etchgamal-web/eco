<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('type', 20);
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('minimum_order_amount')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('coupon_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('customer_orders')->nullOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamps();
            $table->index(['coupon_id', 'user_id']);
        });
        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('country', 2)->nullable();
            $table->string('state', 120)->nullable();
            $table->decimal('rate', 8, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['country', 'state', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
