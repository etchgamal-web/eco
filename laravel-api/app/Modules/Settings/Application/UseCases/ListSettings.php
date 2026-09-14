<?php
namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use Illuminate\Support\Collection;

final class ListSettings
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function execute(): Collection
    {
        return $this->settings->getAll();
    }
}
