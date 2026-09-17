# Social Commerce backend module

The module is implemented under `App\Modules\SocialCommerce` and follows the project’s Controller → Form Request → Use Case → Contract → Infrastructure flow. Customers remain the existing `users` records and products remain the existing `products` records. Connection operations are split into `CreateSocialConnection`, `GetSocialConnection`, `UpdateSocialConnection`, and `DeleteSocialConnection`; messaging is split into Facebook, Instagram, and WhatsApp adapters behind `SocialMessagingProviderRouter`.

## Endpoints

Webhook verification and reception are available at `GET/POST /api/v1/social/webhooks/{facebook|instagram|whatsapp}`. Authenticated management endpoints are under `/api/v1/admin/social`: connections CRUD, interaction filtering, conversation inspection, message sending, manual/paused/automated mode changes, and ready-message templates CRUD with preview. Tokens and webhook secrets are encrypted and are not serialized by the connection model.

Ready-message templates are stored in `social_message_templates`. The template body can contain placeholders such as `{customer_name}`, `{product_name}`, `{product_price}`, and `{order_number}`. Use `POST /api/v1/admin/social/templates/{template}/preview` with a `values` object to render a message before sending. Missing placeholders are rejected rather than silently rendered as empty text.

## Lifecycle

A provider adapter verifies and normalizes an event. The use case records a unique webhook event, creates or reuses a conversation, and stores the inbound message. Facebook and Instagram comment events retain `provider_comment_id`. Conversation mode defaults to `manual`; `manual` and `paused` modes prevent automation from sending replies, while `resume` sets `automated`. In automated mode, active rules with `conditions.keywords` can use `{ "type": "send_message", "message": "..." }` for direct messages or `{ "type": "reply_to_comment", "message": "..." }` for Facebook/Instagram comments. Replies use `/{comment_id}/comments` for Facebook and `/{comment_id}/replies` for Instagram. Each rule/event pair is protected by an idempotency key and stored in `social_automation_executions`.

## Provider configuration

Configure `SOCIAL_FACEBOOK_VERIFY_TOKEN`, `SOCIAL_INSTAGRAM_VERIFY_TOKEN`, `SOCIAL_WHATSAPP_VERIFY_TOKEN`, `SOCIAL_HTTP_TIMEOUT`, provider send URLs, and the Facebook/Instagram comment reply URL templates through environment/configuration. Use `{comment_id}` in `SOCIAL_FACEBOOK_COMMENT_REPLY_URL` and `SOCIAL_INSTAGRAM_COMMENT_REPLY_URL`. The bound Meta provider verifies `X-Hub-Signature-256`, emits WhatsApp Cloud API payloads for WhatsApp, emits recipient/message payloads for direct Facebook/Instagram messages, and uses the platform-specific comment edges for replies. Each connection supplies its encrypted access token and provider account ID.

## Security

Webhook event uniqueness provides replay/idempotency protection. Admin APIs require the existing `auth` middleware and `social.*` permissions. Credentials are encrypted at rest and are excluded from normal model serialization.
