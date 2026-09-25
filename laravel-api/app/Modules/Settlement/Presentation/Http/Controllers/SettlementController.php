<?php

namespace App\Modules\Settlement\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settlement\Application\UseCases\ExportSettlementItems;
use App\Modules\Settlement\Application\UseCases\FinalizeSettlement;
use App\Modules\Settlement\Application\UseCases\GetSettlement;
use App\Modules\Settlement\Application\UseCases\GetSettlementItems;
use App\Modules\Settlement\Application\UseCases\GetSettlementSettings;
use App\Modules\Settlement\Application\UseCases\ImportSettlement;
use App\Modules\Settlement\Application\UseCases\ListSettlements;
use App\Modules\Settlement\Application\UseCases\UpdateSettlementSetting;
use App\Modules\Settlement\Presentation\Http\Requests\FinalizeSettlementRequest;
use App\Modules\Settlement\Presentation\Http\Requests\SettlementListRequest;
use App\Modules\Settlement\Presentation\Http\Requests\SettlementReadRequest;
use App\Modules\Settlement\Presentation\Http\Requests\SettlementRequest;
use App\Modules\Settlement\Presentation\Http\Requests\SettlementSettingsRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SettlementController extends Controller
{
    public function import(SettlementRequest $r, ImportSettlement $u): JsonResponse
    {
        $d = $r->validated();

        return response()->json(['data' => $u->execute($r->file('file')->getRealPath(), $d['provider_code'], $d['period_from'] ?? null, $d['period_to'] ?? null)], 201);
    }

    public function index(SettlementListRequest $r, ListSettlements $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($r->validated())]);
    }

    public function show(SettlementReadRequest $r, int $id, GetSettlement $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($id)]);
    }

    public function items(SettlementReadRequest $r, int $id, GetSettlementItems $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($id)]);
    }

    public function export(SettlementReadRequest $r, int $id, ExportSettlementItems $u): StreamedResponse
    {
        return response()->streamDownload(function () use ($u, $id): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['id', 'tracking_number', 'order_number', 'status', 'expected_total', 'actual_total', 'difference_total', 'currency']);
            foreach ($u->execute($id) as $item) {
                fputcsv($output, [$item->id, $item->shipment?->tracking_number, $item->shipment?->order?->order_number, $item->status, $item->expected_total, $item->actual_total, $item->difference_total, $item->currency]);
            }fclose($output);
        }, 'settlement-'.$id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function finalize(FinalizeSettlementRequest $r, int $id, FinalizeSettlement $u): JsonResponse
    {
        return response()->json(['data' => $u->execute($id)]);
    }

    public function settings(SettlementSettingsRequest $r, GetSettlementSettings $u): JsonResponse
    {
        return response()->json(['data' => $u->execute()]);
    }

    public function updateSetting(SettlementSettingsRequest $r, UpdateSettlementSetting $u): JsonResponse
    {
        $d = $r->validated();

        return response()->json(['data' => $u->execute($d['key'],$d['value'])]);
    }
}
