<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->unsignedBigInteger('requested_amount')->nullable()->after('provider_reference');
            $table->unsignedBigInteger('confirmed_amount')->nullable()->after('requested_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->dropColumn(['requested_amount', 'confirmed_amount']);
        });
    }
};
