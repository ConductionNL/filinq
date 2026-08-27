# Prometheus Metrics

## Overview

Filinq exposes application metrics in Prometheus text exposition format for monitoring, alerting, and operational dashboards. A health check endpoint is also provided for infrastructure monitoring.

## Endpoints

- `GET /apps/filinq/api/metrics` - Prometheus metrics (admin auth required)
- `GET /apps/filinq/api/health` - Health check (JSON)

## Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `filinq_info` | gauge | Application version, PHP version, Nextcloud version |
| `filinq_up` | gauge | Application health (1 = up) |
| `filinq_documents_total` | gauge | Total number of documents managed |
| `filinq_templates_total` | gauge | Total number of templates |
| `filinq_pdf_generations_total` | counter | Total PDF generation operations |
| `filinq_anonymizations_total` | counter | Total anonymization operations |

## Example Output

```
# HELP filinq_info Application information
# TYPE filinq_info gauge
filinq_info{version="0.0.34",php_version="8.2.0",nextcloud_version="29.0.0"} 1
# HELP filinq_up Whether the application is up
# TYPE filinq_up gauge
filinq_up 1
```

## Prometheus Configuration

```yaml
scrape_configs:
  - job_name: 'filinq'
    basic_auth:
      username: 'admin'
      password: 'admin'
    static_configs:
      - targets: ['localhost:8080']
    metrics_path: '/apps/filinq/api/metrics'
```
