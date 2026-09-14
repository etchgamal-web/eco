<?php

namespace App\Modules\Customer\Infrastructure\Persistence;

use App\Models\CustomerNotification;
use App\Modules\Customer\Domain\Contracts\CustomerNotificationRepositoryInterface;
use App\Modules\Customer\Domain\Exceptions\CustomerFeatureNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class EloquentCustomerNotificationRepository implements CustomerNotificationRepositoryInterface
{
    public function listForUser(int $userId): iterable
    {
        return CustomerNotification::query()->where('user_id', $userId)->latest()->get();
    }

    public function markAsRead(int $userId, int $notificationId): object
    {
        try {
            $notification = CustomerNotification::query()->where('user_id', $userId)->findOrFail($notificationId);
        } catch (ModelNotFoundException) {
            throw new CustomerFeatureNotFoundException('Notification', $notificationId);
        }
        $notification->update(['read_at' => now()]);

        return $notification->fresh();
    }
}
