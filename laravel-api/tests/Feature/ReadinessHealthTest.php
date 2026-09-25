<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReadinessHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_reports_all_required_runtime_dependencies(): void
    {
        $this->getJson('/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true)
            ->assertJsonPath('checks.storage', true)
            ->assertJsonPath('checks.queue', true);
    }
}
