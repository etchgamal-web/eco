<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->timestamp('restocked_at')->nullable()->after('inspected_at');
            $table->timestamp('refund_requested_at')->nullable()->after('restocked_at');
            $table->timestamp('completed_at')->nullable()->after('refund_requested_at');
            $table->unsignedBigInteger('actual_customer_refund')->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->dropColumn(['restocked_at', 'refund_requested_at', 'completed_at', 'actual_customer_refund']);
        });
    }
};
