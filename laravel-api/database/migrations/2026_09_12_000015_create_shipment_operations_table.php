<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('operation', 40);
            $table->string('status', 30)->index();
            $table->string('idempotency_key', 100);
            $table->string('provider_reference', 191)->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['shipment_id', 'operation', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_operations');
    }
};
