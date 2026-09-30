<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->timestamp('last_reconciliation_at')->nullable()->after('next_retry_at')->index();
            $table->timestamp('next_reconciliation_at')->nullable()->after('last_reconciliation_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->dropColumn(['last_reconciliation_at', 'next_reconciliation_at']);
        });
    }
};
