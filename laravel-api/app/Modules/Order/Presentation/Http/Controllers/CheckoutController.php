<?php

namespace App\Modules\Order\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Application\UseCases\Checkout;
use App\Modules\Order\Application\UseCases\PreviewCheckout;
use App\Modules\Order\Domain\ValueObjects\CheckoutData;
use App\Modules\Order\Presentation\Http\Requests\CheckoutRequest;
use Illuminate\Http\JsonResponse;

final class CheckoutController extends Controller
{
    public function preview(CheckoutRequest $request, PreviewCheckout $preview): JsonResponse
    {
        $data = $request->validated();

        $result = $preview->execute(new CheckoutData(
            addressId: (int) $data['address_id'],
            shippingMethodId: isset($data['shipping_method_id']) ? (int) $data['shipping_method_id'] : null,
            currency: strtoupper($data['currency'] ?? 'EGP'),
            idempotencyKey: null,
            paymentMethod: null,
            paymentIdempotencyKey: null,
            couponCode: $data['coupon_code'] ?? null,
            guestItems: [],
            guestDetails: [],
        ), (int) $request->user()->id);

        return response()->json(['data' => $result]);
    }

    public function __invoke(CheckoutRequest $request, Checkout $checkout): JsonResponse
    {
        $data = $request->validated();

        $order = $checkout->execute(new CheckoutData(
            addressId: isset($data['address_id']) ? (int) $data['address_id'] : null,
            shippingMethodId: isset($data['shipping_method_id']) ? (int) $data['shipping_method_id'] : null,
            currency: strtoupper($data['currency'] ?? 'EGP'),
            idempotencyKey: $data['idempotency_key'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            paymentIdempotencyKey: $data['payment_idempotency_key'] ?? null,
            couponCode: $data['coupon_code'] ?? null,
            guestItems: $data['items'] ?? [],
            guestDetails: $data['guest'] ?? [],
            previewToken: $data['preview_token'] ?? null,
        ));

        return response()->json(['data' => $order], 201);
    }
}
