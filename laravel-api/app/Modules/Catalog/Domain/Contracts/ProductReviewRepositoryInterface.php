<?php
namespace App\Modules\Catalog\Domain\Contracts;
interface ProductReviewRepositoryInterface
{
    public function approvedForProduct(int $productId): iterable;
    /** @return array{count:int,average_rating:float} */
    public function summary(int $productId): array;
    public function createForVerifiedCustomer(int $userId, int $productId, array $data): object;
    public function all(): iterable;
    public function moderate(int $reviewId, string $status): object;
}
