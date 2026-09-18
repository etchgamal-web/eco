<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('social_messages', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('sender')->index();
            $table->string('idempotency_key', 191)->nullable()->unique()->after('provider_message_id');
        });

        Schema::table('social_interactions', function (Blueprint $table): void {
            $table->string('idempotency_key', 191)->nullable()->unique()->after('provider_interaction_id');
        });

        DB::table('social_messages')->where('direction', '!=', 'outbound')->update(['status' => 'sent']);
    }

    public function down(): void
    {
        Schema::table('social_interactions', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
        Schema::table('social_messages', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['status', 'idempotency_key']);
        });
    }
};
