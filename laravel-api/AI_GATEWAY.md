# AI Gateway

The AI module exposes one application-facing gateway behind the existing layered architecture: controllers and use cases depend on `AiGatewayInterface`, while provider adapters remain infrastructure details. The current adapters support Gemini, OpenAI, Groq, and OpenRouter through a common structured-JSON request contract.

## Routing and fallback

Provider order, primary model, per-provider models, timeout, and fallback behavior are read from the `ai` settings group and fall back to `config/services.php`. The gateway attempts providers in the configured order, records each failed attempt in `ai_generations`, and returns the first valid `AiResponse`. When fallback is exhausted it throws `AiAllProvidersFailedException`.

Fallback is an availability mechanism, not permission to perform sensitive actions. Product drafts and social reply suggestions remain drafts for human approval; the gateway does not publish, send, refund, edit orders, or otherwise execute a sensitive action autonomously.

## Exception contract

Provider-specific messages, URLs, response bodies, credentials, and model errors are internal diagnostics only. API consumers receive a stable message and safe code:

| Code | HTTP status | Meaning |
| --- | ---: | --- |
| `AI_CONFIGURATION_ERROR` | 503 | A selected provider is not configured completely. |
| `AI_INVALID_RESPONSE` | 502 | A provider returned a response that did not satisfy the structured JSON contract. |
| `AI_PROVIDER_ERROR` | 503 | A provider request failed for another provider-level reason. |
| `AI_ALL_PROVIDERS_FAILED` | 503 | Every eligible provider failed or fallback was exhausted. |

For API routes, Laravel renders these failures as `AI service is temporarily unavailable.` together with `error_code`, `retryable`, and the request `correlation_id`. The original exception is reported server-side and is stored in the internal AI generation log without being exposed to customers. Correlation IDs should be included when investigating a failed request.

When fallback is disabled, a typed configuration or structured-response exception is preserved so operators can distinguish configuration and contract failures. When fallback is enabled, the gateway tries the next configured provider and emits the aggregate `AI_ALL_PROVIDERS_FAILED` code only after the chain is exhausted.

## Configuration checklist

Each enabled provider requires its API key, base URL where applicable, and model. Keep credentials in environment-backed configuration or encrypted settings; never include credentials in prompts, logs, or API responses. Set a bounded HTTP timeout and enable fallback only when an alternative provider is configured and approved for the same data classification.

## Observability

Successful and failed attempts are recorded in `ai_generations` with request kind, selected model, provider attribution, status, token counts when available, and internal failure details. Responder attribution is internal metadata and is not returned to social customers. Operational logs should be access-controlled because prompts and provider diagnostics may contain customer or catalog data.

## API behavior

Controllers should return generated content as a draft or suggestion according to their use case. Clients should treat 502 as a provider response-contract problem and 503 as a temporary availability/configuration problem, display a generic retry-safe message, and preserve the correlation ID for support and internal tracing.
