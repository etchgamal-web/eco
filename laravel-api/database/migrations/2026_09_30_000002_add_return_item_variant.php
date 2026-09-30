<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_return_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_return_items', function (Blueprint $table): void {
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });
    }
};
