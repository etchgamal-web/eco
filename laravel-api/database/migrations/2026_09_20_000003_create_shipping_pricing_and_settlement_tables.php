<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 191);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_pricing_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers')->restrictOnDelete();
            $table->string('name', 191);
            $table->string('pricing_method', 30);
            $table->string('currency', 3)->default('EGP');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['shipping_provider_id', 'pricing_method']);
        });

        Schema::create('shipping_pricing_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_pricing_plan_id')->constrained('shipping_pricing_plans')->cascadeOnDelete();
            $table->string('rule_type', 20)->default('base');
            $table->decimal('min_weight', 10, 3)->nullable();
            $table->decimal('max_weight', 10, 3)->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();
            $table->string('zone_code', 100)->nullable();
            $table->unsignedBigInteger('base_amount')->default(0);
            $table->unsignedBigInteger('additional_unit_amount')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('conditions')->nullable();
            $table->timestamps();
            $table->index(['shipping_pricing_plan_id', 'rule_type', 'is_active']);
        });

        Schema::create('shipping_fee_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_pricing_plan_id')->constrained('shipping_pricing_plans')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name', 191);
            $table->string('fee_type', 20)->default('fixed');
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('applies_to', 40)->default('delivered');
            $table->json('trigger_conditions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['shipping_pricing_plan_id', 'code']);
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->decimal('weight', 10, 3)->nullable()->after('provider_code');
            $table->unsignedInteger('item_quantity')->nullable()->after('weight');
            $table->string('zone_code', 100)->nullable()->after('item_quantity');
            $table->index(['provider_code', 'weight']);
        });

        Schema::create('shipment_pricing_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_id')->unique()->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers')->restrictOnDelete();
            $table->foreignId('shipping_pricing_plan_id')->constrained('shipping_pricing_plans')->restrictOnDelete();
            $table->string('pricing_method', 30);
            $table->decimal('weight', 10, 3)->nullable();
            $table->unsignedInteger('item_quantity')->nullable();
            $table->string('zone_code', 100)->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('base_amount')->default(0);
            $table->json('applied_fees')->nullable();
            $table->unsignedBigInteger('total_expected_cost')->default(0);
            $table->json('calculation_inputs')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_provider_id')->constrained('shipping_providers')->restrictOnDelete();
            $table->string('reference', 100)->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status', 30)->default('draft')->index();
            $table->string('currency', 3)->default('EGP');
            $table->unsignedInteger('shipments_count')->default(0);
            $table->unsignedBigInteger('expected_total')->default(0);
            $table->unsignedBigInteger('actual_total')->default(0);
            $table->bigInteger('difference_total')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['shipping_provider_id', 'period_from', 'period_to']);
        });

        Schema::create('shipping_settlement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipping_settlement_id')->constrained('shipping_settlements')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->restrictOnDelete();
            $table->unsignedBigInteger('expected_total')->default(0);
            $table->unsignedBigInteger('actual_total')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->string('status', 30)->default('matched')->index();
            $table->json('expected_charges')->nullable();
            $table->json('actual_charges')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['shipping_settlement_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_settlement_items');
        Schema::dropIfExists('shipping_settlements');
        Schema::dropIfExists('shipment_pricing_snapshots');
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropIndex(['provider_code', 'weight']);
            $table->dropColumn(['weight', 'item_quantity', 'zone_code']);
        });
        Schema::dropIfExists('shipping_fee_options');
        Schema::dropIfExists('shipping_pricing_rules');
        Schema::dropIfExists('shipping_pricing_plans');
        Schema::dropIfExists('shipping_providers');
    }
};
