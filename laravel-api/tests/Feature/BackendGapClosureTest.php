<?php

namespace Tests\Feature;

use App\Modules\Auth\Infrastructure\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class BackendGapClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_is_generic_and_sends_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'customer@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If the account exists, a password reset link has been sent.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_public_tracking_does_not_require_authentication_and_hides_unknown_tokens(): void
    {
        $this->getJson('/api/v1/public/shipments/'.str_repeat('x', 48))
            ->assertNotFound()
            ->assertJsonPath('message', 'Shipment not found.');
    }
}
