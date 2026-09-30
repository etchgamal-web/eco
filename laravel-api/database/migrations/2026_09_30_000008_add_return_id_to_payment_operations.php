<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->foreignId('return_id')->nullable()->after('payment_id')->constrained('order_returns')->nullOnDelete()->index();
        });
    }

    public function down(): void
    {
        Schema::table('payment_operations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('return_id');
        });
    }
};
