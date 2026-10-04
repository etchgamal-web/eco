<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketingFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');

        if ($adminId === null) {
            return;
        }

        $now = now();
        $slug = 'demo-sustainable-home';

        DB::table('landing_pages')->updateOrInsert(
            ['slug' => $slug],
            [
                'title' => 'Sustainable Home Essentials (Demo)',
                'status' => 'draft',
                'excerpt' => 'A local-only landing page fixture for API and analytics tests.',
                'sections' => json_encode([
                    ['type' => 'hero', 'heading' => 'Make everyday choices more sustainable'],
                    ['type' => 'product_highlight', 'product_slug' => 'reusable-bamboo-bottle'],
                ], JSON_THROW_ON_ERROR),
                'seo_title' => 'Sustainable Home Demo',
                'seo_description' => 'Draft fixture content for local testing.',
                'og_image_url' => null,
                'canonical_url' => null,
                'settings' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'published_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $pageId = DB::table('landing_pages')->where('slug', $slug)->value('id');

        DB::table('landing_page_leads')->updateOrInsert(
            ['landing_page_id' => $pageId, 'dedupe_key' => 'seed-demo-lead-001'],
            [
                'assigned_to' => $adminId,
                'name' => 'Demo Visitor',
                'email' => 'demo.visitor@example.test',
                'phone' => '+201000000099',
                'message' => 'Please send information about reusable home products.',
                'notes' => 'Local fixture only; do not contact.',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'source' => 'demo-seeder',
                'ip_hash' => null,
                'status' => 'new',
                'contacted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('landing_page_events')->updateOrInsert(
            ['landing_page_id' => $pageId, 'dedupe_key' => 'seed-demo-view-001'],
            [
                'event_type' => 'view',
                'session_id' => 'demo-session-001',
                'source' => 'fixture',
                'medium' => 'test',
                'campaign' => 'local-demo',
                'content' => 'hero',
                'term' => null,
                'referrer' => 'https://example.test/',
                'ip_hash' => null,
                'user_agent' => 'Seeder fixture',
                'metadata' => json_encode(['fixture' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('ai_generations')->updateOrInsert(
            ['kind' => 'landing_page_copy', 'model' => 'mock-fixture', 'user_id' => $adminId],
            [
                'input' => json_encode(['prompt' => 'Write concise sustainable-product landing page copy.', 'fixture' => true], JSON_THROW_ON_ERROR),
                'output' => json_encode(['headline' => 'Small choices, lasting impact.', 'fixture' => true], JSON_THROW_ON_ERROR),
                'status' => 'completed',
                'error' => null,
                'prompt_tokens' => 20,
                'completion_tokens' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
