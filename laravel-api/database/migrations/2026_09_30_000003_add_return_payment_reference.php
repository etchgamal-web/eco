<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->foreignId('payment_id')->nullable()->after('order_id')->constrained('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }
};
