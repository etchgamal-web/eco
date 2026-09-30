<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_settlement_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('requested_customer_refund')->default(0)->after('expected_customer_refund');
            $table->unsignedBigInteger('confirmed_customer_refund')->default(0)->after('requested_customer_refund');
            $table->bigInteger('refund_reconciliation_difference')->default(0)->after('customer_refund_difference');
            $table->string('refund_reconciliation_status', 20)->default('pending')->after('refund_reconciliation_difference')->index();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_settlement_items', function (Blueprint $table): void {
            $table->dropColumn(['requested_customer_refund', 'confirmed_customer_refund', 'refund_reconciliation_difference', 'refund_reconciliation_status']);
        });
    }
};
