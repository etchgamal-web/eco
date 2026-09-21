<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('customer_orders', function (Blueprint $table): void { $table->string('order_number', 100)->nullable()->unique()->after('id'); });
        Schema::table('shipping_settlements', function (Blueprint $table): void {
            $table->unsignedInteger('total_rows')->default(0)->after('shipments_count'); $table->unsignedInteger('matched_rows')->default(0)->after('total_rows'); $table->unsignedInteger('mismatched_rows')->default(0)->after('matched_rows'); $table->unsignedInteger('missing_orders')->default(0)->after('mismatched_rows'); $table->unsignedInteger('duplicate_rows')->default(0)->after('missing_orders'); $table->unsignedInteger('invalid_rows')->default(0)->after('duplicate_rows');
            $table->unsignedBigInteger('expected_collection')->default(0)->after('difference_total'); $table->unsignedBigInteger('actual_collection')->default(0)->after('expected_collection'); $table->bigInteger('collection_difference')->default(0)->after('actual_collection'); $table->unsignedBigInteger('expected_shipping')->default(0)->after('collection_difference'); $table->unsignedBigInteger('actual_shipping')->default(0)->after('expected_shipping'); $table->bigInteger('shipping_difference')->default(0)->after('actual_shipping'); $table->unsignedBigInteger('expected_return_fee')->default(0)->after('shipping_difference'); $table->unsignedBigInteger('actual_return_fee')->default(0)->after('expected_return_fee'); $table->bigInteger('return_difference')->default(0)->after('actual_return_fee');
        });
        Schema::table('shipping_settlement_items', function (Blueprint $table): void {
            $table->string('order_number', 100)->nullable()->after('shipment_id'); $table->unsignedBigInteger('expected_order_amount')->default(0)->after('order_number'); $table->unsignedBigInteger('actual_order_amount')->default(0)->after('expected_order_amount'); $table->bigInteger('order_amount_difference')->default(0)->after('actual_order_amount'); $table->unsignedBigInteger('expected_collection')->default(0)->after('order_amount_difference'); $table->unsignedBigInteger('actual_collection')->default(0)->after('expected_collection'); $table->bigInteger('collection_difference')->default(0)->after('actual_collection'); $table->unsignedBigInteger('expected_shipping_cost')->default(0)->after('collection_difference'); $table->unsignedBigInteger('actual_shipping_cost')->default(0)->after('expected_shipping_cost'); $table->bigInteger('shipping_difference')->default(0)->after('actual_shipping_cost'); $table->unsignedBigInteger('expected_return_fee')->default(0)->after('shipping_difference'); $table->unsignedBigInteger('actual_return_fee')->default(0)->after('expected_return_fee'); $table->bigInteger('return_difference')->default(0)->after('actual_return_fee');
        });
    }
    public function down(): void {
        Schema::table('shipping_settlement_items', function (Blueprint $table): void { $table->dropColumn(['order_number','expected_order_amount','actual_order_amount','order_amount_difference','expected_collection','actual_collection','collection_difference','expected_shipping_cost','actual_shipping_cost','shipping_difference','expected_return_fee','actual_return_fee','return_difference']); });
        Schema::table('shipping_settlements', function (Blueprint $table): void { $table->dropColumn(['total_rows','matched_rows','mismatched_rows','missing_orders','duplicate_rows','invalid_rows','expected_collection','actual_collection','collection_difference','expected_shipping','actual_shipping','shipping_difference','expected_return_fee','actual_return_fee','return_difference']); });
        Schema::table('customer_orders', function (Blueprint $table): void { $table->dropUnique(['order_number']); $table->dropColumn('order_number'); });
    }
};
