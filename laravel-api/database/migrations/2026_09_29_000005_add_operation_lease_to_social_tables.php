<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['social_messages', 'social_interactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->string('operation_idempotency_key', 191)->nullable()->index();
                $table->unsignedInteger('operation_attempt_count')->default(0);
                $table->string('operation_status', 30)->nullable()->index();
                $table->uuid('operation_lease_token')->nullable();
                $table->timestamp('operation_lease_expires_at')->nullable();
                $table->text('operation_last_error')->nullable();
                $table->index(['operation_lease_token', 'operation_lease_expires_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (['social_messages', 'social_interactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex(['operation_idempotency_key']);
                $table->dropIndex(['operation_status']);
                $table->dropIndex(['operation_lease_token', 'operation_lease_expires_at']);
                $table->dropColumn(['operation_idempotency_key', 'operation_attempt_count', 'operation_status', 'operation_lease_token', 'operation_lease_expires_at', 'operation_last_error']);
            });
        }
    }
};
