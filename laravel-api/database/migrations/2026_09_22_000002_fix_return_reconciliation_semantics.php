<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_settlements', function (Blueprint $table): void {
            $table->date('period_from')->nullable()->change();
            $table->date('period_to')->nullable()->change();
        });
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->foreignId('shipment_id')->nullable()->after('order_id')->constrained('shipments')->nullOnDelete();
            $table->unsignedBigInteger('return_shipping_fee')->default(0)->after('refund_amount');
        });
        Schema::table('shipping_settlements', function (Blueprint $table): void {
            $table->unsignedBigInteger('expected_customer_refund')->default(0)->after('return_difference');
            $table->unsignedBigInteger('actual_customer_refund')->default(0)->after('expected_customer_refund');
            $table->bigInteger('customer_refund_difference')->default(0)->after('actual_customer_refund');
        });
        Schema::table('shipping_settlement_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('expected_customer_refund')->default(0)->after('return_difference');
            $table->unsignedBigInteger('actual_customer_refund')->default(0)->after('expected_customer_refund');
            $table->bigInteger('customer_refund_difference')->default(0)->after('actual_customer_refund');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_settlement_items', function (Blueprint $table): void {
            $table->dropColumn(['expected_customer_refund', 'actual_customer_refund', 'customer_refund_difference']);
        });
        Schema::table('shipping_settlements', function (Blueprint $table): void {
            $table->dropColumn(['expected_customer_refund', 'actual_customer_refund', 'customer_refund_difference']);
        });
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->dropForeign(['shipment_id']);
            $table->dropColumn(['shipment_id', 'return_shipping_fee']);
        });
        Schema::table('shipping_settlements', function (Blueprint $table): void {
            $table->date('period_from')->nullable(false)->change();
            $table->date('period_to')->nullable(false)->change();
        });
    }
};
