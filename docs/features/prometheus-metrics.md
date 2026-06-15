# Prometheus Metrics

## Overview

DocuDesk exposes application metrics in Prometheus text exposition format for monitoring, alerting, and operational dashboards. A health check endpoint is also provided for infrastructure monitoring.

## Endpoints

- `GET /apps/docudesk/api/metrics` - Prometheus metrics (admin auth required)
- `GET /apps/docudesk/api/health` - Health check (JSON)

## Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `docudesk_info` | gauge | Application version, PHP version, Nextcloud version |
| `docudesk_up` | gauge | Application health (1 = up) |
| `docudesk_documents_total` | gauge | Total number of documents managed |
| `docudesk_templates_total` | gauge | Total number of templates |
| `docudesk_pdf_generations_total` | counter | Total PDF generation operations |
| `docudesk_anonymizations_total` | counter | Total anonymization operations |

## Example Output

```
# HELP docudesk_info Application information
# TYPE docudesk_info gauge
docudesk_info{version="0.0.34",php_version="8.2.0",nextcloud_version="29.0.0"} 1
# HELP docudesk_up Whether the application is up
# TYPE docudesk_up gauge
docudesk_up 1
```

## Prometheus Configuration

```yaml
scrape_configs:
  - job_name: 'docudesk'
    basic_auth:
      username: 'admin'
      password: 'admin'
    static_configs:
      - targets: ['localhost:8080']
    metrics_path: '/apps/docudesk/api/metrics'
```
