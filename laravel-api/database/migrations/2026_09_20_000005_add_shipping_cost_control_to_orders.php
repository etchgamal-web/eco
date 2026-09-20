<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('shipping_cost')->default(0)->after('shipping_amount');
            $table->bigInteger('shipping_subsidy')->default(0)->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_cost', 'shipping_subsidy']);
        });
    }
};
