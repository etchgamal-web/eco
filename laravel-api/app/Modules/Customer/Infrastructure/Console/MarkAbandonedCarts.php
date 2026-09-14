<?php

namespace App\Modules\Customer\Infrastructure\Console;

use App\Models\CustomerCart;
use App\Models\CustomerNotification;
use Illuminate\Console\Command;

final class MarkAbandonedCarts extends Command
{
    protected $signature = 'cart:mark-abandoned {--hours=24 : Hours without activity before the cart is abandoned}';
    protected $description = 'Mark inactive carts as abandoned and notify customers once';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) $this->option('hours'));
        $count = 0;
        CustomerCart::query()->with('user')->whereNull('abandoned_at')->whereNotNull('last_activity_at')->where('last_activity_at', '<=', $cutoff)->whereHas('items')->chunkById(100, function ($carts) use (&$count): void {
            foreach ($carts as $cart) {
                $cart->update(['abandoned_at' => now(), 'recovery_reminder_count' => 1]);
                CustomerNotification::query()->create(['user_id' => $cart->user_id, 'type' => 'abandoned_cart', 'title' => 'Your cart is waiting for you', 'body' => 'You have items waiting in your cart. Come back to complete your order.']);
                $count++;
            }
        });
        $this->info("Marked {$count} abandoned cart(s).");
        return self::SUCCESS;
    }
}
