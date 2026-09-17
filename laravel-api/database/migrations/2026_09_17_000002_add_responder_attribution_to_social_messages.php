<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_messages', function (Blueprint $table): void {
            $table->string('responder_type', 20)->nullable()->after('sender');
            $table->unsignedBigInteger('responder_id')->nullable()->after('responder_type');
            $table->string('responder_name')->nullable()->after('responder_id');
            $table->index(['responder_type', 'responder_id']);
        });

        Schema::table('social_interactions', function (Blueprint $table): void {
            $table->string('responder_type', 20)->nullable()->after('status');
            $table->unsignedBigInteger('responder_id')->nullable()->after('responder_type');
            $table->string('responder_name')->nullable()->after('responder_id');
            $table->index(['responder_type', 'responder_id']);
        });
    }

    public function down(): void
    {
        Schema::table('social_messages', function (Blueprint $table): void {
            $table->dropIndex(['responder_type', 'responder_id']);
            $table->dropColumn(['responder_type', 'responder_id', 'responder_name']);
        });

        Schema::table('social_interactions', function (Blueprint $table): void {
            $table->dropIndex(['responder_type', 'responder_id']);
            $table->dropColumn(['responder_type', 'responder_id', 'responder_name']);
        });
    }
};
