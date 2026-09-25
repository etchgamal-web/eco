<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_leads', function (Blueprint $table): void {
            $table->string('dedupe_key', 191)->nullable()->after('source');
            $table->string('ip_hash', 64)->nullable()->after('dedupe_key');
            $table->index(['landing_page_id', 'dedupe_key']);
        });

        Schema::table('landing_page_events', function (Blueprint $table): void {
            $table->string('dedupe_key', 191)->nullable()->after('metadata');
            $table->index(['landing_page_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_events', function (Blueprint $table): void {
            $table->dropIndex(['landing_page_id', 'dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
        Schema::table('landing_page_leads', function (Blueprint $table): void {
            $table->dropIndex(['landing_page_id', 'dedupe_key']);
            $table->dropColumn(['dedupe_key', 'ip_hash']);
        });
    }
};
