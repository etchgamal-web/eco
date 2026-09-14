<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('carrier')->nullable();
            $table->unsignedBigInteger('base_fee');
            $table->string('currency', 3)->default('EGP');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->restrictOnDelete();
            $table->string('method_code', 60);
            $table->string('tracking_number', 191)->nullable()->unique();
            $table->unsignedBigInteger('fee');
            $table->string('currency', 3);
            $table->string('status', 30)->default('pending')->index();
            $table->json('address_snapshot');
            $table->string('idempotency_key', 100)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('order_id');
        });

        Schema::create('shipment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_methods');
    }
};
