<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table): void {
            $table->id();
            $table->string('aggregate_type', 40);
            $table->unsignedBigInteger('aggregate_id');
            $table->string('event_type', 80);
            $table->string('deduplication_key', 191)->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->json('payload');
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
            $table->index(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
