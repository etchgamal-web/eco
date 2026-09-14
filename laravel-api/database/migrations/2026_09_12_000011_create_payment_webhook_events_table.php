<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 60);
            $table->string('event_id', 191);
            $table->string('event_type', 100);
            $table->string('status', 30)->default('received')->index();
            $table->string('payment_reference', 191)->nullable()->index();
            $table->json('payload');
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
