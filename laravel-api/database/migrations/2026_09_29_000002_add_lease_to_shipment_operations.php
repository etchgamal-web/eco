<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_operations', function (Blueprint $table): void {
            $table->string('lease_token', 64)->nullable()->index();
            $table->timestamp('lease_expires_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_operations', function (Blueprint $table): void {
            $table->dropColumn(['lease_token', 'lease_expires_at']);
        });
    }
};
