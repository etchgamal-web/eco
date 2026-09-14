# API v1 Integration Guide

## Base URL and authentication

All registered application endpoints use the versioned base path `/api/v1`. Legacy `/api` routes are not registered. Public storefront reads and authentication endpoints do not require a bearer token. Protected operations require an authenticated token:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

The complete machine-readable contract is available in [`openapi.json`](./openapi.json).

## Authorization

Authentication and authorization are separate checks. A valid token is not sufficient for protected business operations; the authenticated user must also hold the permission declared by the operation's `x-required-permission` OpenAPI extension. Missing credentials return `401`; missing permissions return `403`.

## Safe retries and idempotency

Retryable mutations should send a stable key in the `Idempotency-Key` header:

```http
Idempotency-Key: checkout-2026-0001
```

Checkout, payment creation, and shipment creation accept this header and preserve the existing body fields for backward compatibility. Repeating the same request with the same key returns the original operation result. Reusing a key with a materially different request is a conflict and returns `409`.

## Correlation and rate limits

Every request receives or propagates an `X-Correlation-Id` response header. Include this value when reporting a failure. Rate-limited endpoints return `429 Too Many Requests` and may include `Retry-After` with the number of seconds to wait.

The OpenAPI operation extension `x-rate-limit` identifies the application limiter used by the endpoint, including `auth-login`, `auth-register`, `checkout`, `cart-mutation`, `payment-create`, `payment-webhook`, `shipping-webhook`, and `return-create`.

## Error contract

Errors are JSON objects. Validation failures include field-level messages:

```json
{
  "message": "Validation failed.",
  "errors": {
    "email": ["The email field is invalid."]
  }
}
```

The stable error categories are documented in the OpenAPI `x-error-codes` extension. The principal HTTP mapping is:

| Status | Meaning |
| --- | --- |
| `400` | Malformed request |
| `401` | Unauthenticated or invalid webhook credentials |
| `403` | Missing permission |
| `404` | Resource not found or not visible |
| `409` | Idempotency or state conflict |
| `422` | Validation or business rule failure |
| `429` | Rate limit exceeded |
| `500` | Unexpected server failure |

## Webhooks

Webhook endpoints are public transport endpoints but are not unauthenticated business operations. They verify provider credentials before changing state, then perform idempotency and event validation:

```text
Signature verification
Idempotency registration or lookup
Event payload validation
Local entity lookup
Business validation
State transition
Mark event processed
```

Paymob accepts its HMAC through `hmac` or `X-Paymob-Hmac`. Bosta validates the configured authorization header. Kashier validates its signed event payload. Duplicate events are safely ignored after a previously processed event.

## Versioning policy

New breaking behavior requires a new API version. Existing `/api/v1` paths must remain compatible, and any change to routes, permissions, rate limits, request schemas, or response schemas must update `openapi.json` and pass the OpenAPI contract tests.
