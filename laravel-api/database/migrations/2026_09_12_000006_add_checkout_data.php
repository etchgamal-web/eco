<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('price')->nullable()->after('status');
        });

        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('subtotal_amount')->default(0)->after('total_amount');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('subtotal_amount');
            $table->unsignedBigInteger('tax_amount')->default(0)->after('discount_amount');
            $table->unsignedBigInteger('shipping_amount')->default(0)->after('tax_amount');
            $table->json('shipping_address')->nullable()->after('currency');
            $table->string('idempotency_key', 100)->nullable()->unique()->after('shipping_address');
        });

        Schema::table('customer_order_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('sku')->nullable()->after('name');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('unit_price');
            $table->unsignedBigInteger('tax_amount')->default(0)->after('discount_amount');
            $table->unsignedBigInteger('total_amount')->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('customer_order_items', function (Blueprint $table): void {
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['variant_id', 'sku', 'discount_amount', 'tax_amount', 'total_amount']);
        });

        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['subtotal_amount', 'discount_amount', 'tax_amount', 'shipping_amount', 'shipping_address', 'idempotency_key']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('price');
        });
    }
};
