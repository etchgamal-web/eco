<?php

namespace App\Modules\Content\Application\UseCases;

use App\Modules\Content\Domain\Contracts\ContentRepositoryInterface;
use App\Modules\Content\Domain\ValueObjects\ContentData;

final class ManageContent
{
    public function __construct(private readonly ContentRepositoryInterface $content) {}

    public function list(array $filters = []): iterable
    {
        return $this->content->listAdmin($filters);
    }

    public function get(int $id): object
    {
        return $this->content->find($id);
    }

    public function persist(ContentData $data, ?int $id = null): object
    {
        return $this->content->save(array_merge($data->persistenceData(), ['product_ids' => $data->productIds, 'category_ids' => $data->categoryIds]), $id);
    }

    public function remove(int $id): void
    {
        $this->content->delete($id);
    }

    public function publish(int $id): object
    {
        return $this->content->publish($id);
    }

    public function unpublish(int $id): object
    {
        return $this->content->unpublish($id);
    }
}
