<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_cart_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
        });
        Schema::table('customer_carts', function (Blueprint $table): void {
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('abandoned_at')->nullable()->index();
            $table->timestamp('recovered_at')->nullable();
            $table->string('recovery_token', 100)->nullable()->unique();
            $table->unsignedTinyInteger('recovery_reminder_count')->default(0);
        });
        Schema::table('customer_cart_items', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'product_id']);
            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_cart_items', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'product_id', 'variant_id']);
            $table->unique(['cart_id', 'product_id']);
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });
        Schema::table('customer_carts', function (Blueprint $table): void {
            $table->dropUnique(['recovery_token']);
            $table->dropColumn(['last_activity_at', 'abandoned_at', 'recovered_at', 'recovery_token', 'recovery_reminder_count']);
        });
    }
};
