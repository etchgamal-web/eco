<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 40);
            $table->string('provider_reference', 191)->nullable()->unique();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);
            $table->string('status', 30)->default('pending')->index();
            $table->string('idempotency_key', 100)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
