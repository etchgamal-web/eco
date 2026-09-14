<?php

namespace App\Modules\Payment\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\UseCases\ConfirmPayment;
use App\Modules\Payment\Application\UseCases\CreatePayment;
use App\Modules\Payment\Application\UseCases\ListOrderPayments;
use App\Modules\Payment\Application\UseCases\ListPayments;
use App\Modules\Payment\Application\UseCases\RefundPayment;
use App\Modules\Payment\Domain\ValueObjects\PaymentData;
use App\Modules\Payment\Presentation\Http\Requests\CreatePaymentRequest;
use App\Modules\Payment\Presentation\Http\Requests\PaymentManagementRequest;
use Illuminate\Http\JsonResponse;

final class PaymentController extends Controller
{
    public function store(CreatePaymentRequest $request, int $orderId, CreatePayment $create): JsonResponse
    {
        $data = $request->validated();
        $payment = $create->execute($orderId, new PaymentData(
            method: $data['method'],
            currency: strtoupper($data['currency']),
            idempotencyKey: $data['idempotency_key'],
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
        ));

        return response()->json(['data' => $payment], 201);
    }

    public function index(PaymentManagementRequest $request, int $orderId, ListOrderPayments $payments): JsonResponse
    {
        return response()->json(['data' => $payments->execute($orderId)]);
    }

    public function adminIndex(PaymentManagementRequest $request, int $orderId, ListPayments $payments): JsonResponse
    {
        return response()->json(['data' => $payments->execute($orderId)]);
    }

    public function confirm(PaymentManagementRequest $request, int $paymentId, ConfirmPayment $confirm): JsonResponse
    {
        return response()->json(['data' => $confirm->execute($paymentId)]);
    }

    public function refund(PaymentManagementRequest $request, int $paymentId, RefundPayment $refund): JsonResponse
    {
        return response()->json(['data' => $refund->execute($paymentId)]);
    }
}
