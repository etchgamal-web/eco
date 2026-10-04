<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SocialCommerceFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $customerId = DB::table('users')->where('email', 'customer@example.com')->value('id');
        $productId = DB::table('products')->where('slug', 'reusable-bamboo-bottle')->value('id');

        if ($customerId === null || $productId === null) {
            return;
        }

        $now = now();

        DB::table('social_connections')->updateOrInsert(
            ['channel' => 'instagram', 'provider_account_id' => 'demo-social-account-001'],
            [
                'name' => 'Demo Instagram (disabled)',
                'access_token' => null,
                'webhook_secret' => null,
                'metadata' => json_encode(['fixture' => true, 'external_calls' => false], JSON_THROW_ON_ERROR),
                'is_active' => false,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('social_conversations')->updateOrInsert(
            ['channel' => 'instagram', 'provider_conversation_id' => 'demo-conversation-001'],
            [
                'provider_customer_id' => 'demo-social-customer-001',
                'customer_id' => $customerId,
                'product_id' => $productId,
                'mode' => 'manual',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $conversationId = DB::table('social_conversations')->where('channel', 'instagram')->where('provider_conversation_id', 'demo-conversation-001')->value('id');

        DB::table('social_messages')->updateOrInsert(
            ['conversation_id' => $conversationId, 'provider_message_id' => 'demo-inbound-message-001'],
            [
                'direction' => 'inbound',
                'sender' => 'customer',
                'body' => 'Is this reusable bottle available?',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'operation_idempotency_key' => null,
                'operation_attempt_count' => 0,
                'operation_status' => null,
                'operation_lease_token' => null,
                'operation_lease_expires_at' => null,
                'operation_last_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        DB::table('social_messages')->updateOrInsert(
            ['conversation_id' => $conversationId, 'provider_message_id' => 'demo-outbound-message-001'],
            [
                'direction' => 'outbound',
                'sender' => 'assistant',
                'body' => 'Yes. This is a local demo reply; no message was sent externally.',
                'metadata' => json_encode(['fixture' => true, 'dry_run' => true], JSON_THROW_ON_ERROR),
                'operation_idempotency_key' => 'seed-social-reply-demo-001',
                'operation_attempt_count' => 1,
                'operation_status' => 'completed',
                'operation_lease_token' => null,
                'operation_lease_expires_at' => null,
                'operation_last_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('social_interactions')->updateOrInsert(
            ['channel' => 'instagram', 'provider_interaction_id' => 'demo-comment-001'],
            [
                'interaction_type' => 'comment',
                'conversation_id' => $conversationId,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'content' => 'Interested in this product.',
                'status' => 'responded',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'operation_idempotency_key' => 'seed-social-comment-demo-001',
                'operation_attempt_count' => 1,
                'operation_status' => 'completed',
                'operation_lease_token' => null,
                'operation_lease_expires_at' => null,
                'operation_last_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('social_webhook_events')->updateOrInsert(
            ['channel' => 'instagram', 'provider_event_id' => 'demo-webhook-event-001'],
            [
                'event_type' => 'comments.created',
                'payload' => json_encode(['comment_id' => 'demo-comment-001', 'fixture' => true], JSON_THROW_ON_ERROR),
                'status' => 'processed',
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('social_message_templates')->updateOrInsert(
            ['name' => 'Demo product availability reply'],
            [
                'channel' => 'instagram',
                'body' => 'Thanks for asking about {{product_name}}.',
                'variables' => json_encode(['product_name'], JSON_THROW_ON_ERROR),
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('social_automation_rules')->updateOrInsert(
            ['name' => 'Demo inquiry rule (disabled)'],
            [
                'channel' => 'instagram',
                'conditions' => json_encode(['event' => 'comment.created'], JSON_THROW_ON_ERROR),
                'actions' => json_encode(['mode' => 'manual_reply'], JSON_THROW_ON_ERROR),
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $ruleId = DB::table('social_automation_rules')->where('name', 'Demo inquiry rule (disabled)')->value('id');

        DB::table('social_automation_executions')->updateOrInsert(
            ['idempotency_key' => 'seed-social-execution-demo-001'],
            [
                'rule_id' => $ruleId,
                'conversation_id' => $conversationId,
                'status' => 'completed',
                'result' => json_encode(['fixture' => true, 'dry_run' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
