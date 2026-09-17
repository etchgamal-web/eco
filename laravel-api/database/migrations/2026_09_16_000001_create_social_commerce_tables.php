<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_connections', function (Blueprint $t) {
            $t->id();
            $t->string('channel', 30);
            $t->string('name');
            $t->string('provider_account_id')->nullable();
            $t->text('access_token')->nullable();
            $t->text('webhook_secret')->nullable();
            $t->json('metadata')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['channel', 'is_active']);
        });
        Schema::create('social_conversations', function (Blueprint $t) {
            $t->id();
            $t->string('channel', 30);
            $t->string('provider_conversation_id')->nullable();
            $t->string('provider_customer_id')->nullable();
            $t->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $t->string('mode', 20)->default('manual');
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['channel', 'provider_conversation_id']);
            $t->index(['customer_id', 'mode']);
        });
        Schema::create('social_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('social_conversations')->cascadeOnDelete();
            $t->string('direction', 20);
            $t->string('sender', 20);
            $t->string('provider_message_id')->nullable();
            $t->text('body')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['conversation_id', 'provider_message_id']);
        });
        Schema::create('social_interactions', function (Blueprint $t) {
            $t->id();
            $t->string('channel', 30);
            $t->string('interaction_type', 30);
            $t->string('provider_interaction_id')->nullable();
            $t->foreignId('conversation_id')->nullable()->constrained('social_conversations')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $t->text('content')->nullable();
            $t->string('status', 30)->default('new');
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->index(['channel', 'interaction_type', 'status']);
            $t->unique(['channel', 'provider_interaction_id']);
        });
        Schema::create('social_webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('channel', 30);
            $t->string('provider_event_id');
            $t->string('event_type', 60);
            $t->json('payload');
            $t->string('status', 20)->default('received');
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
            $t->unique(['channel', 'provider_event_id']);
        });
        Schema::create('social_message_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('channel', 30)->nullable();
            $t->text('body');
            $t->json('variables')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('social_automation_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('channel', 30)->nullable();
            $t->json('conditions');
            $t->json('actions');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('social_automation_executions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rule_id')->nullable()->constrained('social_automation_rules')->nullOnDelete();
            $t->foreignId('conversation_id')->nullable()->constrained('social_conversations')->nullOnDelete();
            $t->string('idempotency_key')->unique();
            $t->string('status', 20)->default('pending');
            $t->json('result')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['social_automation_executions', 'social_automation_rules', 'social_message_templates', 'social_webhook_events', 'social_interactions', 'social_messages', 'social_conversations', 'social_connections'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
