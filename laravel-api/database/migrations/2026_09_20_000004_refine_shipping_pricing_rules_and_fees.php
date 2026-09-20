<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_pricing_rules', function (Blueprint $table): void {
            $table->string('calculation_method', 30)->default('flat')->after('rule_type');
            $table->string('calculation_unit', 20)->nullable()->after('calculation_method');
            $table->decimal('included_weight', 10, 3)->nullable()->after('max_weight');
            $table->decimal('increment_unit', 10, 3)->nullable()->after('included_weight');
            $table->unsignedBigInteger('increment_amount')->default(0)->after('increment_unit');
        });

        Schema::table('shipping_fee_options', function (Blueprint $table): void {
            $table->string('calculation_basis', 30)->default('base_shipping')->after('fee_type');
        });

        Schema::table('shipment_pricing_snapshots', function (Blueprint $table): void {
            $table->foreignId('shipping_provider_id')->nullable()->change();
            $table->foreignId('shipping_pricing_plan_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_pricing_snapshots', function (Blueprint $table): void {
            $table->foreignId('shipping_provider_id')->nullable(false)->change();
            $table->foreignId('shipping_pricing_plan_id')->nullable(false)->change();
        });
        Schema::table('shipping_fee_options', function (Blueprint $table): void {
            $table->dropColumn('calculation_basis');
        });
        Schema::table('shipping_pricing_rules', function (Blueprint $table): void {
            $table->dropColumn(['calculation_method', 'calculation_unit', 'included_weight', 'increment_unit', 'increment_amount']);
        });
    }
};
