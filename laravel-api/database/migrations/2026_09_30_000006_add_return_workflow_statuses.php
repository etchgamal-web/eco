<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->string('restock_status', 20)->default('pending')->after('restocked_at')->index();
            $table->string('refund_status', 20)->default('pending')->after('refund_requested_at')->index();
            $table->text('workflow_error')->nullable()->after('refund_status');
            $table->timestamp('last_workflow_attempt_at')->nullable()->after('workflow_error')->index();
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->dropColumn(['restock_status', 'refund_status', 'workflow_error', 'last_workflow_attempt_at']);
        });
    }
};
