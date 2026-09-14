<?php

namespace App\Modules\Shipping\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Domain\ValueObjects\CreateShipmentData;
use App\Modules\Shipping\Application\UseCases\CreateShipment;
use App\Modules\Shipping\Application\UseCases\CreateShippingMethod;
use App\Modules\Shipping\Application\UseCases\DeleteShippingMethod;
use App\Modules\Shipping\Application\UseCases\GetShippingMethod;
use App\Modules\Shipping\Application\UseCases\ListAllShippingMethods;
use App\Modules\Shipping\Application\UseCases\ListOrderShipments;
use App\Modules\Shipping\Application\UseCases\ListShippingMethods;
use App\Modules\Shipping\Application\UseCases\UpdateShipmentStatus;
use App\Modules\Shipping\Application\UseCases\UpdateShippingMethod;
use App\Modules\Shipping\Presentation\Http\Requests\ShippingRequest;
use Illuminate\Http\JsonResponse;

final class ShippingController extends Controller
{
    public function customerMethods(ShippingRequest $request, ListShippingMethods $methods): JsonResponse { return response()->json(['data' => $methods->execute()]); }
    public function customerShipments(ShippingRequest $request, int $orderId, ListOrderShipments $shipments): JsonResponse { return response()->json(['data' => $shipments->execute($orderId)]); }
    public function createShipment(ShippingRequest $request, int $orderId, CreateShipment $create): JsonResponse
    {
        $data = $request->validated();
        return response()->json(['data' => $create->execute($orderId, new CreateShipmentData((int) $data['shipping_method_id'], $data['idempotency_key']))], 201);
    }
    public function index(ShippingRequest $request, ListAllShippingMethods $methods): JsonResponse { return response()->json(['data' => $methods->execute()]); }
    public function show(ShippingRequest $request, int $id, GetShippingMethod $method): JsonResponse { return response()->json(['data' => $method->execute($id)]); }
    public function store(ShippingRequest $request, CreateShippingMethod $create): JsonResponse { return response()->json(['data' => $create->execute($request->validated())], 201); }
    public function update(ShippingRequest $request, int $id, UpdateShippingMethod $update): JsonResponse { return response()->json(['data' => $update->execute($id, $request->validated())]); }
    public function destroy(ShippingRequest $request, int $id, DeleteShippingMethod $delete): JsonResponse { $delete->execute($id); return response()->json(null, 204); }
    public function status(ShippingRequest $request, int $id, UpdateShipmentStatus $update): JsonResponse
    {
        $data = $request->validated();
        return response()->json(['data' => $update->execute($id, $data['status'], $data['note'] ?? null)]);
    }
}
