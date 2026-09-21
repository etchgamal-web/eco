<?php
namespace App\Modules\Settlement\Infrastructure\Persistence;
use App\Models\Setting;
use App\Models\ShippingProvider;
use App\Models\ShippingSettlement;
use App\Models\ShippingSettlementItem;
use App\Models\Shipment;
use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;
use App\Modules\Settlement\Domain\Exceptions\SettlementImportException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
final class EloquentSettlementRepository implements SettlementRepositoryInterface
{
    private const DEFAULTS = [
        'settlement_import.max_rows' => ['integer', 100000, 'Maximum settlement rows per import'],
        'settlement_import.tolerance' => ['integer', 0, 'Allowed amount difference in minor currency units'],
        'settlement_import.allow_duplicates' => ['boolean', false, 'Allow duplicate shipment rows'],
        'settlement_import.auto_finalize' => ['boolean', false, 'Finalize a successful import automatically'],
        'settlement_import.currency' => ['string', 'EGP', 'Settlement currency'],
        'settlement_import.notify_mismatch' => ['boolean', true, 'Create alerts for mismatched rows'],
    ];
    public function settings(): array
    {
        foreach (self::DEFAULTS as $key => [$type, $default, $description]) {
            $setting = Setting::query()->firstOrCreate(['key' => $key], ['group' => 'settlement_import', 'value' => is_bool($default) ? ($default ? '1' : '0') : (string) $default, 'type' => $type, 'description' => $description]);
            if ($setting->group !== 'settlement_import') $setting->update(['group' => 'settlement_import']);
        }
        return Setting::query()->where('group', 'settlement_import')->orderBy('key')->get()->map(fn (Setting $setting): array => ['key' => $setting->key, 'value' => $setting->getTypedValue(), 'type' => $setting->type, 'description' => $setting->description])->all();
    }
    public function saveSetting(string $key, mixed $value): array
    {
        if (! isset(self::DEFAULTS[$key])) throw new SettlementImportException('Unsupported settlement setting.');
        [$type] = self::DEFAULTS[$key];
        $setting = Setting::query()->firstOrCreate(['key' => $key], ['group' => 'settlement_import', 'type' => $type]);
        $normalized = match ($type) { 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN), 'integer' => filter_var($value, FILTER_VALIDATE_INT), default => (string) $value };
        if ($normalized === false && $type === 'integer') throw new SettlementImportException('Integer settlement settings must contain a valid number.');
        $setting->group = 'settlement_import'; $setting->type = $type; $setting->setTypedValue($normalized); $setting->save();
        DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'action' => 'settlement_import_setting_updated', 'target_type' => Setting::class, 'target_id' => $setting->id, 'metadata' => json_encode(['key' => $key, 'value' => $value]), 'created_at' => now(), 'updated_at' => now()]);
        return ['key' => $setting->key, 'value' => $setting->getTypedValue(), 'type' => $setting->type, 'description' => $setting->description];
    }
    public function import(string $path, string $providerCode, ?string $periodFrom, ?string $periodTo): mixed
    {
        $provider = ShippingProvider::query()->where('code', $providerCode)->firstOrFail(); $config = $this->config(); $rows = $this->rows($path); $from = $periodFrom ?? now()->startOfMonth()->toDateString(); $to = $periodTo ?? now()->toDateString();
        $settlement = ShippingSettlement::query()->create(['shipping_provider_id' => $provider->id, 'reference' => basename($path), 'period_from' => $from, 'period_to' => $to, 'status' => 'draft', 'currency' => $config['currency']]);
        $summary = ['matched' => 0, 'mismatched' => 0, 'missing' => 0, 'duplicates' => 0, 'invalid' => 0]; $expected = 0; $actual = 0; $rowCount = 0;
        foreach ($rows as $row) {
            $rowCount++; if ($rowCount > $config['max_rows']) throw new SettlementImportException("Settlement file exceeds the configured maximum of {$config['max_rows']} rows.");
            $tracking = trim((string) ($row['tracking_number'] ?? $row['tracking'] ?? '')); $rawActual = $row['actual_total'] ?? $row['collected_amount'] ?? $row['amount'] ?? null;
            if ($tracking === '' || $rawActual === null || ! is_numeric($rawActual)) { $summary['invalid']++; continue; }
            $actualValue = (int) round((float) $rawActual); $shipment = Shipment::query()->where('tracking_number', $tracking)->first();
            if (! $shipment) { $summary['missing']++; continue; }
            $existing = ShippingSettlementItem::query()->where(['shipping_settlement_id' => $settlement->id, 'shipment_id' => $shipment->id])->exists();
            if ($existing) { $summary['duplicates']++; if (! $config['allow_duplicates']) continue; }
            $expectedValue = (int) ($shipment->pricingSnapshot?->total_expected_cost ?? $shipment->fee ?? 0); $difference = $actualValue - $expectedValue; $matched = abs($difference) <= $config['tolerance']; $summary[$matched ? 'matched' : 'mismatched']++; $expected += $expectedValue; $actual += $actualValue;
            ShippingSettlementItem::query()->create(['shipping_settlement_id' => $settlement->id, 'shipment_id' => $shipment->id, 'expected_total' => $expectedValue, 'actual_total' => $actualValue, 'difference' => $difference, 'status' => $matched ? 'matched' : 'mismatched', 'expected_charges' => ['shipping' => $expectedValue], 'actual_charges' => $row, 'metadata' => ['tracking_number' => $tracking, 'tolerance' => $config['tolerance']]]);
        }
        $settlement->update(['shipments_count' => $rowCount, 'expected_total' => $expected, 'actual_total' => $actual, 'difference_total' => $actual - $expected, 'status' => $config['auto_finalize'] && $summary['invalid'] === 0 ? 'finalized' : 'draft', 'metadata' => $summary + ['settings' => $config, 'notify_mismatch' => $config['notify_mismatch']]]);
        return $settlement->fresh(['provider', 'items']);
    }
    private function config(): array { $values = $this->settings(); $config = []; foreach ($values as $value) $config[str_replace('settlement_import.', '', $value['key'])] = $value['value']; return $config; }
    private function rows(string $path): iterable { $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)); if ($extension === 'xlsx') yield from $this->xlsxRows($path); else yield from $this->csvRows($path); }
    private function csvRows(string $path): iterable { $handle = fopen($path, 'rb'); if (! $handle) throw new SettlementImportException('Settlement file could not be opened.'); $headers = fgetcsv($handle); if (! $headers) throw new SettlementImportException('Settlement file is empty.'); $headers = array_map(fn ($value) => strtolower(trim((string) $value)), $headers); while (($values = fgetcsv($handle)) !== false) yield array_combine($headers, array_pad($values, count($headers), null)); fclose($handle); }
    private function xlsxRows(string $path): iterable { $zip = new \ZipArchive(); if ($zip->open($path) !== true) throw new SettlementImportException('Invalid XLSX file.'); $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml')); if (! $xml) throw new SettlementImportException('XLSX worksheet is missing.'); $shared = []; if ($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) foreach (simplexml_load_string($sharedXml)->si as $item) $shared[] = (string) ($item->t ?? $item->r->r->t ?? ''); $headers = []; foreach ($xml->sheetData->row as $row) { $values = []; foreach ($row->c as $cell) { $value = (string) $cell->v; $values[] = (string) ($cell['t'] === 's' ? ($shared[(int) $value] ?? $value) : $value); } if (! $headers) $headers = array_map(fn ($value) => strtolower(trim($value)), $values); else yield array_combine($headers, array_pad($values, count($headers), null)); } $zip->close(); }
    public function show(int $id): mixed { return ShippingSettlement::query()->with('provider')->findOrFail($id); }
    public function items(int $id): mixed { return ShippingSettlementItem::query()->with('shipment.order')->where('shipping_settlement_id', $id)->paginate(100); }
    public function finalize(int $id): mixed { $settlement = $this->show($id); $settlement->update(['status' => 'finalized']); return $settlement->fresh(['provider', 'items']); }
}
