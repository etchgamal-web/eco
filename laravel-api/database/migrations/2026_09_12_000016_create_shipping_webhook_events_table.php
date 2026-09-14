<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_id', 191);
            $table->string('event_type', 80)->nullable();
            $table->string('shipment_reference', 191)->nullable();
            $table->string('status', 30)->index();
            $table->json('payload');
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_webhook_events');
    }
};
