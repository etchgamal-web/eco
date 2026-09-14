<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('reason', 120);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('order_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('return_id')->constrained('order_returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('customer_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->timestamps();
            $table->unique(['return_id', 'order_item_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('order_return_items'); Schema::dropIfExists('order_returns'); }
};
