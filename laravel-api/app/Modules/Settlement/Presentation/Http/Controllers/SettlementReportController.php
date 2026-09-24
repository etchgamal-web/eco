<?php

namespace App\Modules\Settlement\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settlement\Application\UseCases\GetSettlementProviderReport;
use App\Modules\Settlement\Application\UseCases\GetSettlementSummary;
use App\Modules\Settlement\Presentation\Http\Requests\SettlementReportRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SettlementReportController extends Controller
{
    public function summary(SettlementReportRequest $request, GetSettlementSummary $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())]);
    }

    public function providers(SettlementReportRequest $request, GetSettlementProviderReport $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute($request->validated())]);
    }

    public function providersExport(SettlementReportRequest $request, GetSettlementProviderReport $useCase): StreamedResponse
    {
        $report = $useCase->execute($request->validated());

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['provider_code', 'provider_name', 'settlement_count', 'item_count', 'order_amount_expected', 'order_amount_actual', 'order_amount_difference', 'collection_expected', 'collection_actual', 'collection_difference', 'shipping_cost_expected', 'shipping_cost_actual', 'shipping_cost_difference', 'return_fee_expected', 'return_fee_actual', 'return_fee_difference', 'customer_refund_expected', 'customer_refund_actual', 'customer_refund_difference']);
            foreach ($report['items'] as $item) {
                fputcsv($output, [$item['provider_code'], $item['provider_name'], $item['settlement_count'], $item['item_count'], $item['financials']['order_amount']['expected'], $item['financials']['order_amount']['actual'], $item['financials']['order_amount']['difference'], $item['financials']['collection']['expected'], $item['financials']['collection']['actual'], $item['financials']['collection']['difference'], $item['financials']['shipping_cost']['expected'], $item['financials']['shipping_cost']['actual'], $item['financials']['shipping_cost']['difference'], $item['financials']['return_fee']['expected'], $item['financials']['return_fee']['actual'], $item['financials']['return_fee']['difference'], $item['financials']['customer_refund']['expected'], $item['financials']['customer_refund']['actual'], $item['financials']['customer_refund']['difference']]);
            }
            fclose($output);
        }, 'settlement-providers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
