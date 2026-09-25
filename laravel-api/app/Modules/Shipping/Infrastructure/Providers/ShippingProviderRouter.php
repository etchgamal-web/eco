<?php

namespace App\Modules\Shipping\Infrastructure\Providers;

use App\Modules\Shipping\Domain\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Domain\Exceptions\ShippingException;

final class ShippingProviderRouter implements ShippingProviderInterface
{
    /** @var list<ShippingProviderInterface> */
    private array $providers;

    public function __construct(BostaShippingProvider $bosta, ManualShippingProvider $manual)
    {
        $this->providers = [$bosta, $manual];
    }

    public function supports(object $shipment): bool
    {
        if (trim((string) ($shipment->provider_code ?? '')) === '') {
            return false;
        }
        foreach ($this->providers as $provider) {
            if ($provider->supports($shipment)) {
                return true;
            }
        }

        return false;
    }

    public function create(object $shipment): array
    {
        return $this->providerFor($shipment)->create($shipment);
    }

    public function recover(object $shipment): ?array
    {
        return $this->providerFor($shipment)->recover($shipment);
    }

    public function track(object $shipment): array
    {
        return $this->providerFor($shipment)->track($shipment);
    }

    public function cancel(object $shipment): array
    {
        return $this->providerFor($shipment)->cancel($shipment);
    }

    private function providerFor(object $shipment): ShippingProviderInterface
    {
        $requested = strtolower(trim((string) ($shipment->provider_code ?? '')));
        foreach ($this->providers as $provider) {
            if ($provider->supports($shipment) && (($provider instanceof BostaShippingProvider) ? $requested === 'bosta' : $requested === 'manual')) {
                return $provider;
            }
        }
        throw new ShippingException('The selected shipping provider is unavailable or not configured.');
    }
}
