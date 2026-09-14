<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_email')->nullable()->after('user_id');
            $table->string('guest_phone', 30)->nullable()->after('guest_email');
        });

        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->dropColumn(['guest_email', 'guest_phone']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
