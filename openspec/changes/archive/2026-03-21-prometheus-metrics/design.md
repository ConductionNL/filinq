# Design: Prometheus Metrics

## Architecture

### Backend
- `MetricsController::index()` exposes `GET /api/metrics`
- `MetricsCollector` queries OpenRegister for document/template counts
- Returns Prometheus text exposition format with HELP/TYPE annotations

### Metrics Exposed
| Metric | Type | Description |
|--------|------|-------------|
| `docudesk_info` | gauge | App version, PHP version, NC version |
| `docudesk_up` | gauge | Application health (always 1) |
| `docudesk_documents_total` | gauge | Total document count |
| `docudesk_templates_total` | gauge | Total template count |
| `docudesk_pdf_generations_total` | counter | PDF generation operations |
| `docudesk_anonymizations_total` | counter | Anonymization operations |

### Health Endpoint
- `GET /api/health` returns JSON health status
- `HealthController::index()` provides basic health check

### Authentication
- Metrics endpoint requires admin authentication via Nextcloud middleware
- No `@NoAdminRequired` annotation on MetricsController

## ADR Compliance
- ADR-008: Controller -> MetricsCollector separation
