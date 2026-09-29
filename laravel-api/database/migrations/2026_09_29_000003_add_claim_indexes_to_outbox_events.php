<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox_events', function (Blueprint $table): void {
            $table->index(['status', 'next_attempt_at', 'id'], 'outbox_pending_claim_idx');
            $table->index(['status', 'lease_until', 'id'], 'outbox_processing_claim_idx');
        });
    }

    public function down(): void
    {
        Schema::table('outbox_events', function (Blueprint $table): void {
            $table->dropIndex('outbox_pending_claim_idx');
            $table->dropIndex('outbox_processing_claim_idx');
        });
    }
};
