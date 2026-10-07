# Professional Monitoring

This directory provides the Prometheus scrape configuration and alert rules for the Ecommerce API.

## Components

- **Sentry:** application exceptions, queue failures, traces, and releases.
- **Prometheus:** scrapes the protected `/metrics` endpoint.
- **Grafana:** dashboards built from Prometheus metrics.
- **Alertmanager:** routes critical and warning alerts to email, Slack, or PagerDuty.

The API exports request totals, request duration sums/counts, failed queue job count, pending outbox event count, and metrics availability.

## Application settings

```env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=git-sha
SENTRY_SAMPLE_RATE=1.0
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.0
SENTRY_SEND_DEFAULT_PII=false

METRICS_ENABLED=true
METRICS_TOKEN=generate-a-long-random-secret
METRICS_REDIS_CONNECTION=default
```

The metrics token must be stored in a secret manager and must never be committed to Git.

## Prometheus installation

1. Copy `prometheus.yml` to the Prometheus configuration directory.
2. Copy `ecommerce-alerts.yml` to the Prometheus rules directory.
3. Store the same metrics token in `/etc/prometheus/secrets/ecommerce-metrics-token` with mode `0600`.
4. Replace `api.example.com` with the real HTTPS API host.
5. Reload Prometheus and validate the rules.
6. Configure Alertmanager receivers and test a notification.

## Alertmanager

`alertmanager.yml` routes grouped alerts to a webhook URL loaded from:

```text
/etc/alertmanager/secrets/ecommerce-webhook-url
```

Create that file with mode `0600`, install the configuration, and validate it with `amtool check-config`. The repository deliberately contains no webhook URL or notification credential. The alert rules cover API downtime, 5xx rate, latency, failed jobs, outbox backlog, metrics storage, authentication failures, webhook failures, and payment failures.

Recommended verification after installation:

```bash
promtool check config /etc/prometheus/prometheus.yml
promtool check rules /etc/prometheus/ecommerce-alerts.yml
amtool check-config /etc/alertmanager/alertmanager.yml
curl -fsS -H "Authorization: Bearer $METRICS_TOKEN" https://api.example.com/metrics
```

اختبار الإشعار الفعلي على Staging:

```bash
./scripts/alertmanager-smoke.sh https://alertmanager.staging.example.com
```

تحقق يدويًا من وصول `EcommerceAlertmanagerSmokeTest` إلى الوجهة المكوّنة، ثم سجّل وقت الاختبار والوجهة والنتيجة. قبول Alertmanager للطلب لا يثبت وحده وصول الرسالة إلى Slack أو البريد أو PagerDuty.

Never expose `/metrics` publicly without the Bearer token. Do not include tokens, card data, provider secrets, or raw customer PII in metrics labels.
