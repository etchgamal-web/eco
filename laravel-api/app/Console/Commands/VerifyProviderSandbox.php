<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

if (! class_exists(__NAMESPACE__ . '\\VerifyProviderSandbox', false)) {
final class VerifyProviderSandbox extends Command
{
    protected $signature = 'payments:sandbox-verify {--provider=* : Providers to verify (paymob or kashier)}';
    protected $description = 'Verify configured payment provider sandbox endpoints without creating a charge';

    public function handle(): int
    {
        $providers = $this->option('provider') ?: ['paymob', 'kashier'];
        $failed = 0;
        foreach ($providers as $provider) {
            $url = config("services.{$provider}.sandbox_probe_url");
            if (! $url) {
                $this->warn("{$provider}: sandbox_probe_url is not configured");
                $failed++;
                continue;
            }
            $response = Http::acceptJson()->timeout(10)->get($url);
            $ok = $response->successful();
            $this->line(sprintf('%s: %s (%s)', $provider, $ok ? 'ok' : 'failed', $response->status()));
            $failed += $ok ? 0 : 1;
        }
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
}
