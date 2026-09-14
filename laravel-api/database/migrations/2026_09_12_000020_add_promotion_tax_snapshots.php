<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->string('coupon_code', 80)->nullable()->after('discount_amount');
            $table->decimal('tax_rate', 8, 4)->default(0)->after('tax_amount');
            $table->foreignId('tax_rule_id')->nullable()->after('tax_rate')->constrained('tax_rules')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->dropForeign(['tax_rule_id']);
            $table->dropColumn(['coupon_code', 'tax_rate', 'tax_rule_id']);
        });
    }
};
