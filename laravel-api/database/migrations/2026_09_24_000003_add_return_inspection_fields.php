<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->timestamp('received_at')->nullable()->after('status');
            $table->timestamp('inspected_at')->nullable()->after('received_at');
            $table->text('inspection_notes')->nullable()->after('inspected_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table): void {
            $table->dropColumn(['received_at', 'inspected_at', 'inspection_notes']);
        });
    }
};
