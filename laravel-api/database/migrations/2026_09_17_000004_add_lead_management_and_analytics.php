<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_leads', function (Blueprint $table): void {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('landing_page_id');
            $table->text('notes')->nullable()->after('message');
            $table->timestamp('contacted_at')->nullable();
            $table->index(['assigned_to', 'status']);
        });
        Schema::create('landing_page_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->string('session_id', 120)->nullable();
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->string('content')->nullable();
            $table->string('term')->nullable();
            $table->string('referrer')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['landing_page_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_events');
        Schema::table('landing_page_leads', function (Blueprint $table): void {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropColumn(['assigned_to', 'notes', 'contacted_at']);
        });
    }
};
