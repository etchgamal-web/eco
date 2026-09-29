<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox_events', function (Blueprint $table): void {
            $table->uuid('claim_token')->nullable()->after('lease_until')->index();
        });
    }

    public function down(): void
    {
        Schema::table('outbox_events', function (Blueprint $table): void {
            $table->dropColumn('claim_token');
        });
    }
};
