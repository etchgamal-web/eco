<?php
namespace App\Modules\Settlement\Infrastructure\Persistence;
use App\Models\CustomerOrder;
use App\Models\OrderReturn;
use App\Models\Setting;
use App\Models\ShippingProvider;
use App\Models\ShippingSettlement;
use App\Models\ShippingSettlementItem;
use App\Models\Shipment;
use App\Modules\Settlement\Domain\Contracts\SettlementRepositoryInterface;
use App\Modules\Settlement\Domain\Exceptions\SettlementImportException;
use Illuminate\Support\Facades\DB;
final class EloquentSettlementRepository implements SettlementRepositoryInterface
{
    private const DEFAULTS = [
        'settlement_import.max_rows' => ['integer', 100000, 'Maximum settlement rows per import'],
        'settlement_import.tolerance' => ['integer', 0, 'Allowed amount difference in minor currency units'],
        'settlement_import.allow_duplicates' => ['boolean', false, 'Allow duplicate shipment rows'],
        'settlement_import.auto_finalize' => ['boolean', false, 'Finalize only a completely matched import automatically'],
        'settlement_import.currency' => ['string', 'EGP', 'Settlement currency'],
        'settlement_import.notify_mismatch' => ['boolean', true, 'Create alerts for mismatched rows'],
    ];
    public function settings(): array
    {
        foreach (self::DEFAULTS as $key => [$type, $default, $description]) Setting::query()->firstOrCreate(['key' => $key], ['group' => 'settlement_import', 'value' => is_bool($default) ? ($default ? '1' : '0') : (string) $default, 'type' => $type, 'description' => $description]);
        return Setting::query()->where('group', 'settlement_import')->orderBy('key')->get()->map(fn (Setting $setting): array => ['key' => $setting->key, 'value' => $setting->getTypedValue(), 'type' => $setting->type, 'description' => $setting->description])->all();
    }
    public function saveSetting(string $key, mixed $value): array
    {
        if (! isset(self::DEFAULTS[$key])) throw new SettlementImportException('Unsupported settlement setting.'); [$type] = self::DEFAULTS[$key]; $setting = Setting::query()->firstOrCreate(['key' => $key], ['group' => 'settlement_import', 'type' => $type]); $normalized = match ($type) { 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN), 'integer' => filter_var($value, FILTER_VALIDATE_INT), default => (string) $value }; if ($normalized === false && $type === 'integer') throw new SettlementImportException('Integer settlement settings must contain a valid number.'); $setting->group = 'settlement_import'; $setting->type = $type; $setting->setTypedValue($normalized); $setting->save(); DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'action' => 'settlement_import_setting_updated', 'target_type' => Setting::class, 'target_id' => $setting->id, 'metadata' => json_encode(['key' => $key, 'value' => $normalized]), 'created_at' => now(), 'updated_at' => now()]); return ['key' => $setting->key, 'value' => $setting->getTypedValue(), 'type' => $setting->type, 'description' => $setting->description];
    }
    public function import(string $path, string $providerCode, ?string $periodFrom, ?string $periodTo): mixed
    {
        return DB::transaction(fn () => $this->importWithinTransaction($path, $providerCode, $periodFrom, $periodTo));
    }
    private function importWithinTransaction(string $path, string $providerCode, ?string $periodFrom, ?string $periodTo): mixed
    {
        $provider = ShippingProvider::query()->where('code', $providerCode)->firstOrFail(); $config = $this->config(); $settlement = ShippingSettlement::query()->create(['shipping_provider_id' => $provider->id, 'reference' => basename($path), 'period_from' => $periodFrom, 'period_to' => $periodTo, 'status' => 'processing', 'currency' => $config['currency']]);
        $summary = ['matched' => 0, 'mismatched' => 0, 'missing' => 0, 'duplicates' => 0, 'invalid' => 0]; $totals = ['order' => [0, 0], 'collection' => [0, 0], 'shipping' => [0, 0], 'return_fee' => [0, 0], 'refund' => [0, 0]]; $rowCount = 0; $matchedShipments = 0;
        foreach ($this->rows($path) as $row) { $rowCount++; if ($rowCount > $config['max_rows']) throw new SettlementImportException("Settlement file exceeds the configured maximum of {$config['max_rows']} rows."); $result = $this->reconcileRow($row, $settlement, $config); if ($result['kind'] !== 'item') { $summary[$result['kind']]++; continue; } $summary[$result['status']]++; $matchedShipments++; foreach (['order','collection','shipping','return_fee','refund'] as $metric) { $totals[$metric][0] += $result[$metric][0]; $totals[$metric][1] += $result[$metric][1]; } }
        $allMatched = $summary['mismatched'] === 0 && $summary['missing'] === 0 && $summary['duplicates'] === 0 && $summary['invalid'] === 0; $status = $config['auto_finalize'] && $allMatched ? 'finalized' : 'completed'; $settlement->update(['status' => $status, 'shipments_count' => $matchedShipments, 'total_rows' => $rowCount, 'matched_rows' => $summary['matched'], 'mismatched_rows' => $summary['mismatched'], 'missing_orders' => $summary['missing'], 'duplicate_rows' => $summary['duplicates'], 'invalid_rows' => $summary['invalid'], 'expected_total' => $totals['shipping'][0] + $totals['return_fee'][0], 'actual_total' => $totals['shipping'][1] + $totals['return_fee'][1], 'difference_total' => ($totals['shipping'][1] + $totals['return_fee'][1]) - ($totals['shipping'][0] + $totals['return_fee'][0]), 'expected_collection' => $totals['collection'][0], 'actual_collection' => $totals['collection'][1], 'collection_difference' => $totals['collection'][1] - $totals['collection'][0], 'expected_shipping' => $totals['shipping'][0], 'actual_shipping' => $totals['shipping'][1], 'shipping_difference' => $totals['shipping'][1] - $totals['shipping'][0], 'expected_return_fee' => $totals['return_fee'][0], 'actual_return_fee' => $totals['return_fee'][1], 'return_difference' => $totals['return_fee'][1] - $totals['return_fee'][0], 'expected_customer_refund' => $totals['refund'][0], 'actual_customer_refund' => $totals['refund'][1], 'customer_refund_difference' => $totals['refund'][1] - $totals['refund'][0], 'metadata' => $summary + ['settings' => $config, 'notify_mismatch' => $config['notify_mismatch']]]); return $settlement->fresh(['provider', 'items']);
    }
    private function reconcileRow(array $row, ShippingSettlement $settlement, array $config): array
    {
        $tracking = trim((string) ($row['tracking_number'] ?? $row['tracking'] ?? '')); $orderNumber = trim((string) ($row['order_number'] ?? $row['order'] ?? '')); $shipment = $tracking !== '' ? Shipment::query()->with('order')->where('tracking_number', $tracking)->first() : null; $order = $shipment?->order;
        if (! $order && $orderNumber !== '') { $order = CustomerOrder::query()->where('order_number', $orderNumber)->first(); $shipment = $order?->shipments()->first(); }
        if (! $order && is_numeric($orderNumber)) { $order = CustomerOrder::query()->find((int) $orderNumber); $shipment = $order?->shipments()->first(); }
        $rawCollection = $row['collected_amount'] ?? $row['actual_collected'] ?? $row['collection'] ?? null; $rawOrderAmount = $row['order_amount'] ?? $row['amount'] ?? null; $rawShipping = $row['shipping_cost'] ?? $row['actual_shipping_cost'] ?? $row['actual_total'] ?? null; $rawReturnFee = $row['return_fee'] ?? $row['actual_return_fee'] ?? 0; $rawCustomerRefund = $row['customer_refund'] ?? $row['actual_customer_refund'] ?? $row['refund_amount'] ?? 0;
        if (($tracking === '' && $orderNumber === '') || ($rawCollection !== null && ! is_numeric($rawCollection)) || ($rawOrderAmount !== null && ! is_numeric($rawOrderAmount)) || ($rawShipping !== null && ! is_numeric($rawShipping)) || ! is_numeric($rawReturnFee) || ! is_numeric($rawCustomerRefund)) return ['kind' => 'invalid'];
        if (! $order || ! $shipment) return ['kind' => 'missing']; if (ShippingSettlementItem::query()->where(['shipping_settlement_id' => $settlement->id, 'shipment_id' => $shipment->id])->exists()) { if (! $config['allow_duplicates']) return ['kind' => 'duplicates']; }
        $expectedOrder = (int) $order->total_amount; $actualOrder = $rawOrderAmount === null ? $expectedOrder : (int) round((float) $rawOrderAmount); $expectedCollection = $expectedOrder; $actualCollection = $rawCollection === null ? $actualOrder : (int) round((float) $rawCollection); $expectedShipping = (int) ($shipment->pricingSnapshot?->total_expected_cost ?? $shipment->fee ?? $order->shipping_cost ?? 0); $actualShipping = (int) round((float) ($rawShipping ?? 0)); $return = OrderReturn::query()->where('order_id', $order->id)->whereNotIn('status', ['rejected', 'cancelled'])->where(function ($query) use ($shipment): void { $query->where('shipment_id', $shipment->id)->orWhereNull('shipment_id'); })->orderByRaw('shipment_id IS NULL')->latest('updated_at')->first(); $expectedReturnFee = (int) ($return?->return_shipping_fee ?? 0); $actualReturnFee = (int) round((float) $rawReturnFee); $expectedCustomerRefund = (int) ($return?->refund_amount ?? 0); $actualCustomerRefund = (int) round((float) $rawCustomerRefund); $orderDiff = $actualOrder - $expectedOrder; $collectionDiff = $actualCollection - $expectedCollection; $shippingDiff = $actualShipping - $expectedShipping; $returnFeeDiff = $actualReturnFee - $expectedReturnFee; $refundDiff = $actualCustomerRefund - $expectedCustomerRefund; $matched = max(abs($orderDiff), abs($collectionDiff), abs($shippingDiff), abs($returnFeeDiff), abs($refundDiff)) <= $config['tolerance']; $expectedTotal = $expectedShipping + $expectedReturnFee; $actualTotal = $actualShipping + $actualReturnFee; ShippingSettlementItem::query()->create(['shipping_settlement_id' => $settlement->id, 'shipment_id' => $shipment->id, 'order_number' => $order->order_number ?? (string) $order->id, 'expected_order_amount' => $expectedOrder, 'actual_order_amount' => $actualOrder, 'order_amount_difference' => $orderDiff, 'expected_collection' => $expectedCollection, 'actual_collection' => $actualCollection, 'collection_difference' => $collectionDiff, 'expected_shipping_cost' => $expectedShipping, 'actual_shipping_cost' => $actualShipping, 'shipping_difference' => $shippingDiff, 'expected_return_fee' => $expectedReturnFee, 'actual_return_fee' => $actualReturnFee, 'return_difference' => $returnFeeDiff, 'expected_customer_refund' => $expectedCustomerRefund, 'actual_customer_refund' => $actualCustomerRefund, 'customer_refund_difference' => $refundDiff, 'expected_total' => $expectedTotal, 'actual_total' => $actualTotal, 'difference' => $actualTotal - $expectedTotal, 'status' => $matched ? 'matched' : 'mismatched', 'expected_charges' => ['order_amount' => $expectedOrder, 'collection' => $expectedCollection, 'shipping' => $expectedShipping, 'return_shipping_fee' => $expectedReturnFee, 'customer_refund' => $expectedCustomerRefund], 'actual_charges' => $row, 'metadata' => ['tracking_number' => $tracking, 'order_number' => $order->order_number ?? (string) $order->id, 'tolerance' => $config['tolerance']]]); return ['kind' => 'item', 'status' => $matched ? 'matched' : 'mismatched', 'order' => [$expectedOrder, $actualOrder], 'collection' => [$expectedCollection, $actualCollection], 'shipping' => [$expectedShipping, $actualShipping], 'return_fee' => [$expectedReturnFee, $actualReturnFee], 'refund' => [$expectedCustomerRefund, $actualCustomerRefund]];
    }
    private function config(): array { $values = $this->settings(); $config = []; foreach ($values as $value) $config[str_replace('settlement_import.', '', $value['key'])] = $value['value']; return $config; }
    private function rows(string $path): iterable { return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'xlsx' ? $this->xlsxRows($path) : $this->csvRows($path); }
    private function csvRows(string $path): iterable { $handle = fopen($path, 'rb'); if (! $handle) throw new SettlementImportException('Settlement file could not be opened.'); $headers = fgetcsv($handle); if (! $headers) throw new SettlementImportException('Settlement file is empty.'); $headers = array_map(fn ($value) => strtolower(trim((string) $value)), $headers); while (($values = fgetcsv($handle)) !== false) yield array_combine($headers, array_pad($values, count($headers), null)); fclose($handle); }
    private function xlsxRows(string $path): iterable { $shared = $this->sharedStrings($path); $reader = new \XMLReader(); if (! $reader->open('zip://' . $path . '#xl/worksheets/sheet1.xml')) throw new SettlementImportException('Invalid XLSX worksheet.'); $headers = []; while ($reader->read()) { if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'row') continue; $xml = simplexml_import_dom($reader->expand()); $values = []; foreach ($xml->c as $cell) { $value = (string) $cell->v; $values[] = (string) ($cell['t'] === 's' ? ($shared[(int) $value] ?? $value) : $value); } if (! $headers) $headers = array_map(fn ($value) => strtolower(trim($value)), $values); else yield array_combine($headers, array_pad($values, count($headers), null)); } $reader->close(); }
    private function sharedStrings(string $path): array { $shared = []; $reader = new \XMLReader(); if (! $reader->open('zip://' . $path . '#xl/sharedStrings.xml')) return $shared; while ($reader->read()) { if ($reader->nodeType === \XMLReader::ELEMENT && $reader->localName === 'si') { $xml = simplexml_import_dom($reader->expand()); $shared[] = (string) ($xml->t ?? $xml->r->r->t ?? ''); } } $reader->close(); return $shared; }
    public function paginate(array $filters = []): mixed
    {
        $query = ShippingSettlement::query()->with('provider')->withCount('items');

        $query->when(! empty($filters['provider_code']), fn ($builder) => $builder->whereHas('provider', fn ($provider) => $provider->where('code', $filters['provider_code'])));
        $query->when(! empty($filters['status']), fn ($builder) => $builder->where('status', $filters['status']));
        $query->when(! empty($filters['from']), fn ($builder) => $builder->whereDate('period_from', '>=', $filters['from']));
        $query->when(! empty($filters['to']), fn ($builder) => $builder->whereDate('period_to', '<=', $filters['to']));
        $query->when(! empty($filters['search']), function ($builder) use ($filters): void {
            $term = $filters['search'];
            $builder->where(function ($search) use ($term): void {
                $search->where('reference', 'like', "%{$term}%")
                    ->orWhere('currency', 'like', "%{$term}%")
                    ->orWhereHas('provider', fn ($provider) => $provider->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
            });
        });
        $query->when(array_key_exists('has_discrepancy', $filters), function ($builder) use ($filters): void {
            $operator = filter_var($filters['has_discrepancy'], FILTER_VALIDATE_BOOLEAN) ? '!=' : '=';
            $builder->where('difference_total', $operator, 0);
        });

        $sort = in_array($filters['sort'] ?? null, ['created_at', 'period_from', 'period_to', 'status', 'actual_total', 'difference_total'], true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 25)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query->orderBy($sort, $direction)->paginate($perPage, ['*'], 'page', $page);
    }

    public function summary(array $filters = []): array
    {
        $settlements = $this->filteredSettlementQuery($filters);
        $settlementTotals = (clone $settlements)->selectRaw('COUNT(*) AS settlement_count, COALESCE(SUM(shipments_count), 0) AS shipments_count, COALESCE(SUM(total_rows), 0) AS total_rows, COALESCE(SUM(matched_rows), 0) AS matched_rows, COALESCE(SUM(mismatched_rows), 0) AS mismatched_rows, COALESCE(SUM(missing_orders), 0) AS missing_orders, COALESCE(SUM(duplicate_rows), 0) AS duplicate_rows, COALESCE(SUM(invalid_rows), 0) AS invalid_rows')->first();
        $itemTotals = ShippingSettlementItem::query()->whereIn('shipping_settlement_id', (clone $settlements)->select('shipping_settlements.id'))
            ->selectRaw('COUNT(*) AS item_count, COALESCE(SUM(expected_order_amount), 0) AS expected_order_amount, COALESCE(SUM(actual_order_amount), 0) AS actual_order_amount, COALESCE(SUM(order_amount_difference), 0) AS order_amount_difference, COALESCE(SUM(expected_collection), 0) AS expected_collection, COALESCE(SUM(actual_collection), 0) AS actual_collection, COALESCE(SUM(collection_difference), 0) AS collection_difference, COALESCE(SUM(expected_shipping_cost), 0) AS expected_shipping_cost, COALESCE(SUM(actual_shipping_cost), 0) AS actual_shipping_cost, COALESCE(SUM(shipping_difference), 0) AS shipping_difference, COALESCE(SUM(expected_return_fee), 0) AS expected_return_fee, COALESCE(SUM(actual_return_fee), 0) AS actual_return_fee, COALESCE(SUM(return_difference), 0) AS return_difference, COALESCE(SUM(expected_customer_refund), 0) AS expected_customer_refund, COALESCE(SUM(actual_customer_refund), 0) AS actual_customer_refund, COALESCE(SUM(customer_refund_difference), 0) AS customer_refund_difference')->first();

        return $this->reportPayload($settlementTotals, $itemTotals, $filters);
    }

    public function providers(array $filters = []): array
    {
        $settlementIds = $this->filteredSettlementQuery($filters)->select('shipping_settlements.id');
        $rows = ShippingSettlementItem::query()
            ->join('shipping_settlements', 'shipping_settlements.id', '=', 'shipping_settlement_items.shipping_settlement_id')
            ->join('shipping_providers', 'shipping_providers.id', '=', 'shipping_settlements.shipping_provider_id')
            ->whereIn('shipping_settlement_items.shipping_settlement_id', $settlementIds)
            ->selectRaw('shipping_providers.code AS provider_code, shipping_providers.name AS provider_name, COUNT(DISTINCT shipping_settlements.id) AS settlement_count, COUNT(*) AS item_count, COALESCE(SUM(shipping_settlement_items.expected_order_amount), 0) AS expected_order_amount, COALESCE(SUM(shipping_settlement_items.actual_order_amount), 0) AS actual_order_amount, COALESCE(SUM(shipping_settlement_items.order_amount_difference), 0) AS order_amount_difference, COALESCE(SUM(shipping_settlement_items.expected_collection), 0) AS expected_collection, COALESCE(SUM(shipping_settlement_items.actual_collection), 0) AS actual_collection, COALESCE(SUM(shipping_settlement_items.collection_difference), 0) AS collection_difference, COALESCE(SUM(shipping_settlement_items.expected_shipping_cost), 0) AS expected_shipping_cost, COALESCE(SUM(shipping_settlement_items.actual_shipping_cost), 0) AS actual_shipping_cost, COALESCE(SUM(shipping_settlement_items.shipping_difference), 0) AS shipping_difference, COALESCE(SUM(shipping_settlement_items.expected_return_fee), 0) AS expected_return_fee, COALESCE(SUM(shipping_settlement_items.actual_return_fee), 0) AS actual_return_fee, COALESCE(SUM(shipping_settlement_items.return_difference), 0) AS return_difference, COALESCE(SUM(shipping_settlement_items.expected_customer_refund), 0) AS expected_customer_refund, COALESCE(SUM(shipping_settlement_items.actual_customer_refund), 0) AS actual_customer_refund, COALESCE(SUM(shipping_settlement_items.customer_refund_difference), 0) AS customer_refund_difference')
            ->groupBy('shipping_providers.code', 'shipping_providers.name')
            ->orderBy('shipping_providers.name')
            ->get();

        return ['items' => $rows->map(fn ($row): array => $this->providerReportRow($row))->all(), 'filters' => $filters];
    }

    private function filteredSettlementQuery(array $filters): mixed
    {
        return ShippingSettlement::query()
            ->when(! empty($filters['provider_code']), fn ($query) => $query->whereHas('provider', fn ($provider) => $provider->where('code', $filters['provider_code'])))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate('period_from', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate('period_to', '<=', $filters['to']));
    }

    private function reportPayload(object $settlements, object $items, array $filters): array
    {
        $differenceFields = ['order_amount' => 'order_amount_difference', 'collection' => 'collection_difference', 'shipping_cost' => 'shipping_difference', 'return_fee' => 'return_difference', 'customer_refund' => 'customer_refund_difference'];
        $financial = fn (string $name): array => ['expected' => (int) $items->{'expected_'.$name}, 'actual' => (int) $items->{'actual_'.$name}, 'difference' => (int) $items->{$differenceFields[$name]}];

        return [
            'filters' => $filters,
            'settlement_count' => (int) $settlements->settlement_count,
            'shipments_count' => (int) $settlements->shipments_count,
            'total_rows' => (int) $settlements->total_rows,
            'matched_rows' => (int) $settlements->matched_rows,
            'mismatched_rows' => (int) $settlements->mismatched_rows,
            'missing_orders' => (int) $settlements->missing_orders,
            'duplicate_rows' => (int) $settlements->duplicate_rows,
            'invalid_rows' => (int) $settlements->invalid_rows,
            'item_count' => (int) $items->item_count,
            'financials' => [
                'order_amount' => $financial('order_amount'),
                'collection' => $financial('collection'),
                'shipping_cost' => $financial('shipping_cost'),
                'return_fee' => $financial('return_fee'),
                'customer_refund' => $financial('customer_refund'),
            ],
        ];
    }

    private function providerReportRow(object $row): array
    {
        $differenceFields = ['order_amount' => 'order_amount_difference', 'collection' => 'collection_difference', 'shipping_cost' => 'shipping_difference', 'return_fee' => 'return_difference', 'customer_refund' => 'customer_refund_difference'];
        $financial = fn (string $name): array => ['expected' => (int) $row->{'expected_'.$name}, 'actual' => (int) $row->{'actual_'.$name}, 'difference' => (int) $row->{$differenceFields[$name]}];

        return [
            'provider_code' => $row->provider_code,
            'provider_name' => $row->provider_name,
            'settlement_count' => (int) $row->settlement_count,
            'item_count' => (int) $row->item_count,
            'financials' => [
                'order_amount' => $financial('order_amount'),
                'collection' => $financial('collection'),
                'shipping_cost' => $financial('shipping_cost'),
                'return_fee' => $financial('return_fee'),
                'customer_refund' => $financial('customer_refund'),
            ],
        ];
    }

    public function show(int $id): mixed { return ShippingSettlement::query()->with('provider')->findOrFail($id); }
    public function items(int $id): mixed { return ShippingSettlementItem::query()->with('shipment.order')->where('shipping_settlement_id', $id)->paginate(100); }
    public function exportItems(int $id): iterable { $this->show($id); return ShippingSettlementItem::query()->with('shipment.order')->where('shipping_settlement_id', $id)->orderBy('id')->cursor(); }
    public function finalize(int $id): mixed { $settlement = $this->show($id); if ($settlement->status !== 'completed') throw new SettlementImportException('Only completed settlements can be finalized after reconciliation review.'); $settlement->update(['status' => 'finalized']); return $settlement->fresh(['provider', 'items']); }
}
