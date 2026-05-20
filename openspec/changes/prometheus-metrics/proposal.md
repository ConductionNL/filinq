## Why

DocuDesk is deployed in production environments across Dutch municipalities and government bodies where site-reliability teams need observable metrics. Without a Prometheus-compatible metrics endpoint, operators cannot integrate DocuDesk into standard monitoring stacks (Prometheus + Grafana, VictoriaMetrics, Alertmanager). Degradation — too many PDF-generation failures, elevated processing latency, a crashed app — goes undetected until a user reports it. A Prometheus endpoint and a structured health check close this gap.

ADR-006 mandates that every Conduction app exposes `GET /api/metrics` (Prometheus text, admin auth) and `GET /api/health` (JSON, public). This change implements that requirement for DocuDesk and defines which business-specific counters and gauges the endpoint exposes.

## What Changes

- **IMPLEMENTED — `MetricsController`** (`lib/Controller/MetricsController.php`): exposes `GET /api/metrics`, requires admin authentication, returns Prometheus text exposition format with `docudesk_info`, `docudesk_up`, `docudesk_documents_total`, `docudesk_templates_total`, `docudesk_pdf_generations_total`, and `docudesk_anonymizations_total`.
- **IMPLEMENTED — `MetricsCollector`** (`lib/Controller/MetricsCollector.php`): delegates `countDocuments()` and `countTemplates()` queries to OpenRegister's `ObjectService`, separating counting concerns from controller logic.
- **IMPLEMENTED — `HealthController`** (`lib/Controller/HealthController.php`): exposes `GET /api/health`, returns JSON status with component-level checks for the database and OpenRegister dependency.
- **PLANNED — request metrics**: `docudesk_requests_total` (method/endpoint/status labels), `docudesk_request_duration_seconds` histogram, `docudesk_errors_total` counter with type label.
- **PLANNED — duration histograms**: `docudesk_pdf_generation_duration_seconds`, `docudesk_anonymization_duration_seconds`.

## Capabilities

### New Capabilities

- `prometheus-metrics` — Prometheus text-format metrics endpoint for DocuDesk
- `health-check` — JSON health check endpoint with component-level status

## Cross-app Dependencies

- **Hard** — `openregister:ObjectService` — `MetricsCollector` delegates document and template counting to OpenRegister's `ObjectService`. The endpoint degrades gracefully if OpenRegister is unavailable (health check reports `"openregister": "error"`).
- **Soft** — `nextcloud:IConfig` — app version, PDF generation counter, and anonymization counter are stored as `IConfig` app values. No additional dependency; `IConfig` is a Nextcloud core service.

## Impact

- **Code (docudesk):** `lib/Controller/MetricsController.php`, `lib/Controller/MetricsCollector.php`, `lib/Controller/HealthController.php`, `appinfo/routes.php`.
- **API contract:** Two new public-facing endpoints. `GET /api/metrics` requires admin auth; `GET /api/health` is public. No existing endpoints are modified.
- **Privacy/compliance:** Metrics are aggregate counts and version strings. No PII is exposed. Admin authentication gates all metric detail.
- **Migration:** None. No schema changes. Counter values bootstrap from 0 if no `IConfig` value is set.
- **Monitoring:** Once deployed, `docudesk_up` can serve as an uptime probe and `docudesk_documents_total` as a data-plane health signal.
