<?php

namespace App\Modules\SocialCommerce\Domain\Contracts;

interface MessageTemplateRepositoryInterface
{
    public function create(array $data): object;

    public function find(int $id): object;

    public function update(object $template, array $data): object;

    public function delete(object $template): void;

    public function list(array $filters = []): array;
}
