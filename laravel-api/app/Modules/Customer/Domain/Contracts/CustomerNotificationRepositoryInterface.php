<?php

namespace App\Modules\Customer\Domain\Contracts;

interface CustomerNotificationRepositoryInterface
{
    public function listForUser(int $userId): iterable;
    public function markAsRead(int $userId, int $notificationId): object;
}
