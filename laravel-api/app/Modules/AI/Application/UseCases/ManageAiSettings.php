<?php

namespace App\Modules\AI\Application\UseCases;

use App\Modules\Settings\Domain\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Domain\ValueObjects\SettingData;

final class ManageAiSettings
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function view(): array
    {
        $out = [];
        foreach ($this->settings->getByGroup('ai') as $s) {
            $out[$s->key] = $s->is_secret ? '********' : $s->getTypedValue();
        }

return $out;
    }

    public function update(array $data): array
    {
        foreach ($data as $key => $value) {
            $type = is_bool($value) ? 'boolean' : ($key === 'provider_order' ? 'json' : 'string');
            $this->settings->save(new SettingData('ai', $key, $value, $type, 'AI Gateway setting', $key === 'api_key', $key === 'api_key'));
        }

return $this->view();
    }
}
