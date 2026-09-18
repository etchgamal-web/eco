<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\LandingPageEvent;
use App\Models\LandingPageLead;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LandingPagesFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_draft_pages_are_not_public_but_published_pages_are_visible(): void
    {
        $page = LandingPage::query()->create([
            'title' => 'Draft campaign', 'slug' => 'draft-campaign', 'status' => 'draft',
            'sections' => [['type' => 'hero', 'data' => ['title' => 'Draft']]],
        ]);

        $this->getJson('/api/v1/landing-pages/draft-campaign')->assertNotFound();

        $page->update(['status' => 'published', 'published_at' => now()]);
        $this->getJson('/api/v1/landing-pages/draft-campaign')
            ->assertOk()
            ->assertJsonPath('data.slug', 'draft-campaign');
    }

    public function test_manager_can_publish_and_unpublish_a_landing_page(): void
    {
        $page = LandingPage::query()->create([
            'title' => 'Campaign', 'slug' => 'campaign', 'status' => 'draft',
            'sections' => [['type' => 'hero', 'data' => []]],
        ]);
        $owner = $this->userWithRole('owner');

        $this->actingAs($owner)->postJson("/api/v1/admin/landing-pages/{$page->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
        self::assertNotNull($page->fresh()->published_at);

        $this->actingAs($owner)->postJson("/api/v1/admin/landing-pages/{$page->id}/unpublish")
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');
        self::assertNull($page->fresh()->published_at);
    }

    public function test_public_lead_capture_and_event_tracking_are_persisted_and_stats_are_filterable(): void
    {
        $page = LandingPage::query()->create([
            'title' => 'Lead campaign', 'slug' => 'lead-campaign', 'status' => 'published',
            'sections' => [['type' => 'hero', 'data' => []]], 'published_at' => now(),
        ]);

        $leadPayload = [
            'name' => 'Mona', 'email' => 'mona@example.test', 'source' => 'facebook',
        ];
        $this->postJson('/api/v1/landing-pages/lead-campaign/leads', $leadPayload)
            ->assertCreated()->assertJsonPath('data.email', 'mona@example.test');
        $this->postJson('/api/v1/landing-pages/lead-campaign/leads', $leadPayload)
            ->assertConflict()
            ->assertJsonPath('message', 'A lead with the same contact was already submitted recently.');
        $this->postJson('/api/v1/landing-pages/lead-campaign/leads', ['email' => 'bot@example.test', 'website' => 'https://bot.test'])
            ->assertUnprocessable();

        $this->postJson('/api/v1/landing-pages/lead-campaign/events', [
            'event_type' => 'view', 'session_id' => 'session-1', 'source' => 'facebook',
        ])->assertCreated();
        $this->postJson('/api/v1/landing-pages/lead-campaign/events', [
            'event_type' => 'view', 'session_id' => 'session-1', 'source' => 'facebook',
        ])->assertCreated();
        $this->postJson('/api/v1/landing-pages/lead-campaign/events', [
            'event_type' => 'conversion', 'session_id' => 'session-1', 'source' => 'facebook',
        ])->assertCreated();

        $owner = $this->userWithRole('owner');
        $this->actingAs($owner)->getJson("/api/v1/admin/landing-pages/{$page->id}/stats?from=".now()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.views', 1)
            ->assertJsonPath('data.conversions', 1)
            ->assertJsonPath('data.leads', 1)
            ->assertJsonPath('data.by_source.facebook', 2);

        self::assertSame(1, LandingPageLead::query()->where('landing_page_id', $page->id)->count());
        self::assertSame(2, LandingPageEvent::query()->where('landing_page_id', $page->id)->count());
    }

    public function test_cms_routes_are_forbidden_without_cms_permission(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'support_agent')->firstOrFail());

        $this->actingAs($user)->getJson('/api/v1/admin/landing-pages')->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $role)->firstOrFail());
        return $user;
    }
}
