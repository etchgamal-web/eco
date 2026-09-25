<?php

namespace App\Modules\LandingPage\Application\UseCases;

use App\Modules\LandingPage\Domain\Contracts\LandingPageRepositoryInterface;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use Illuminate\Support\Str;

final class CaptureLandingLead
{
    public function __construct(private readonly LandingPageRepositoryInterface $pages) {}

    public function execute(string $slug, array $data, ?string $ipAddress = null): object
    {
        $page = $this->pages->bySlug($slug, true);

        $email = isset($data['email']) ? Str::lower(trim((string) $data['email'])) : null;
        $phone = isset($data['phone']) ? preg_replace('/[^0-9+]/', '', (string) $data['phone']) : null;
        $contact = $email ?: $phone;
        $dedupeKey = $contact ? hash('sha256', $page->id.'|'.$contact) : null;
        if ($dedupeKey && $this->pages->recentLeadByKey($page->id, $dedupeKey)) {
            throw new BusinessRuleException('A lead with the same contact was already submitted recently.');
        }

        $ip = $ipAddress;
        $data['email'] = $email ?: null;
        $data['phone'] = $phone ?: null;
        $data['dedupe_key'] = $dedupeKey;
        $data['ip_hash'] = $ip ? hash('sha256', $ip) : null;
        unset($data['website']);

        return $this->pages->addLead($page->id, $data);
    }
}
