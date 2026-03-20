---
status: reviewed
---

# Prometheus Metrics Endpoint

## Purpose

Expose application metrics in Prometheus text exposition format at `GET /api/metrics` for monitoring, alerting, and operational dashboards. Provides a health check endpoint for infrastructure monitoring.

## Requirements

### REQ-PROM-01: Metrics Endpoint (Priority: Must)

Expose a Prometheus-compatible metrics endpoint with proper content type and authentication.

#### Scenario: Retrieve metrics
- GIVEN an authenticated admin user
- WHEN GET /api/metrics is called
- THEN the response content type is `text/plain; version=0.0.4; charset=utf-8`
- AND the body contains metrics in Prometheus text exposition format

#### Scenario: Metrics include HELP and TYPE annotations
- GIVEN the metrics endpoint is called
- WHEN the response is parsed
- THEN each metric has a `# HELP` line describing its purpose
- AND each metric has a `# TYPE` line declaring its type (gauge, counter, histogram)

#### Scenario: Unauthenticated access denied
- GIVEN no authentication is provided
- WHEN GET /api/metrics is called
- THEN the request is rejected by Nextcloud's auth middleware
- AND no metrics data is exposed

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-001 | Metrics at `GET /api/metrics` with Prometheus text format | MUST | Implemented |
| PROM-002 | Content-Type: `text/plain; version=0.0.4; charset=utf-8` | MUST | Implemented |
| PROM-003 | Require admin authentication | MUST | Implemented |

### REQ-PROM-02: Standard Application Metrics (Priority: Must)

Every DocuDesk installation exposes standard metrics for version info, health, and basic operational data.

#### Scenario: Application info metric
- GIVEN DocuDesk version 0.0.32 is installed on PHP 8.2 and Nextcloud 29
- WHEN metrics are retrieved
- THEN `docudesk_info{version="0.0.32",php_version="8.2.x",nextcloud_version="29.x.x"} 1` is present

#### Scenario: Up gauge
- GIVEN DocuDesk is running
- WHEN metrics are retrieved
- THEN `docudesk_up 1` is present indicating the app is healthy

#### Scenario: Version label accuracy
- GIVEN DocuDesk is upgraded from 0.0.31 to 0.0.32
- WHEN metrics are retrieved after upgrade
- THEN the version label reflects "0.0.32"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-010 | `docudesk_info` gauge with version, php_version, nextcloud_version labels | MUST | Implemented |
| PROM-011 | `docudesk_up` gauge (1 = healthy) | MUST | Implemented |
| PROM-012 | Version labels from IConfig app values | MUST | Implemented |

### REQ-PROM-03: App-Specific Metrics (Priority: Must)

DocuDesk exposes metrics specific to its document processing capabilities.

#### Scenario: Document count metric
- GIVEN 50 documents exist in DocuDesk
- WHEN metrics are retrieved
- THEN `docudesk_documents_total 50` is present
- AND the count is fetched via MetricsCollector::countDocuments()

#### Scenario: Template count metric
- GIVEN 15 templates exist in DocuDesk
- WHEN metrics are retrieved
- THEN `docudesk_templates_total 15` is present
- AND the count is fetched via MetricsCollector::countTemplates()

#### Scenario: PDF generation counter
- GIVEN 200 PDFs have been generated since installation
- WHEN metrics are retrieved
- THEN `docudesk_pdf_generations_total 200` is present
- AND the value is read from IConfig app value `pdf_generations_total`

#### Scenario: Anonymization counter
- GIVEN 75 anonymization operations have been performed
- WHEN metrics are retrieved
- THEN `docudesk_anonymizations_total 75` is present
- AND the value is read from IConfig app value `anonymizations_total`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-020 | `docudesk_documents_total` gauge via MetricsCollector | MUST | Implemented |
| PROM-021 | `docudesk_templates_total` gauge via MetricsCollector | MUST | Implemented |
| PROM-022 | `docudesk_pdf_generations_total` counter from IConfig | MUST | Implemented |
| PROM-023 | `docudesk_anonymizations_total` counter from IConfig | MUST | Implemented |

### REQ-PROM-04: Planned Standard Metrics (Priority: Should)

The app MUST expose additional standard metrics for request tracking and error monitoring.

#### Scenario: Request counter (planned)
- GIVEN DocuDesk handles HTTP requests
- WHEN request metrics are implemented
- THEN `docudesk_requests_total` counter with method, endpoint, status labels is exposed

#### Scenario: Request latency (planned)
- GIVEN DocuDesk handles HTTP requests
- WHEN latency metrics are implemented
- THEN `docudesk_request_duration_seconds` histogram with method, endpoint labels is exposed

#### Scenario: Error counter (planned)
- GIVEN DocuDesk encounters errors
- WHEN error metrics are implemented
- THEN `docudesk_errors_total` counter with type label is exposed

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-030 | `docudesk_requests_total` counter with method/endpoint/status labels | SHOULD | Planned |
| PROM-031 | `docudesk_request_duration_seconds` histogram | SHOULD | Planned |
| PROM-032 | `docudesk_errors_total` counter with type label | SHOULD | Planned |

### REQ-PROM-05: Planned Duration Metrics (Priority: Should)

The app MUST expose duration histograms for PDF generation and anonymization operations.

#### Scenario: PDF generation duration (planned)
- GIVEN PDF generation operations vary in duration
- WHEN duration metrics are implemented
- THEN `docudesk_pdf_generation_duration_seconds` histogram is exposed
- AND percentiles (p50, p95, p99) can be derived from the histogram

#### Scenario: Anonymization duration (planned)
- GIVEN anonymization operations vary based on document size
- WHEN duration metrics are implemented
- THEN `docudesk_anonymization_duration_seconds` histogram is exposed

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-040 | `docudesk_pdf_generation_duration_seconds` histogram | SHOULD | Planned |
| PROM-041 | `docudesk_anonymization_duration_seconds` histogram | SHOULD | Planned |

### REQ-PROM-06: Health Check Endpoint (Priority: Must)

A health check endpoint provides infrastructure monitoring with component-level status checks.

#### Scenario: Healthy application
- GIVEN all dependencies are available
- WHEN GET /api/health is called
- THEN `{"status": "ok", "checks": {"database": "ok", "openregister": "ok"}}` is returned

#### Scenario: Degraded state
- GIVEN OpenRegister is unavailable but the database is accessible
- WHEN GET /api/health is called
- THEN `{"status": "degraded", "checks": {"database": "ok", "openregister": "error"}}` is returned

#### Scenario: Error state
- GIVEN the database is not accessible
- WHEN GET /api/health is called
- THEN `{"status": "error", "checks": {"database": "error"}}` is returned

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-050 | Health check at `GET /api/health` returning JSON status | MUST | Implemented |
| PROM-051 | Component-level checks: database, dependencies | MUST | Implemented |
| PROM-052 | Status values: ok, degraded, error | MUST | Implemented |

### REQ-PROM-07: MetricsCollector Delegation (Priority: Must)

MetricsController delegates count queries to MetricsCollector for separation of concerns.

#### Scenario: Document counting via collector
- GIVEN MetricsController needs the document count
- WHEN it calls `$this->metricsCollector->countDocuments()`
- THEN MetricsCollector queries OpenRegister for the total
- AND returns the count

#### Scenario: Template counting via collector
- GIVEN MetricsController needs the template count
- WHEN it calls `$this->metricsCollector->countTemplates()`
- THEN MetricsCollector queries OpenRegister for the total
- AND returns the count

#### Scenario: Counter values from IConfig
- GIVEN PDF generation and anonymization counters are stored in IConfig
- WHEN MetricsController retrieves them
- THEN it reads directly from `IConfig::getAppValue()` with default "0"
- AND the values are cast to integer

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| PROM-060 | MetricsCollector::countDocuments() for document total | MUST | Implemented |
| PROM-061 | MetricsCollector::countTemplates() for template total | MUST | Implemented |
| PROM-062 | Counter values read from IConfig app values | MUST | Implemented |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/metrics` | Prometheus text exposition metrics |
| GET | `/api/health` | JSON health check |

## Dependencies

- **Nextcloud IConfig**: App version and counter values
- **MetricsCollector**: Document and template counting
- **OpenRegister ObjectService**: Object counting (via MetricsCollector)

### Current Implementation Status
- **Partially implemented**:
  - `lib/Controller/MetricsController.php` -- metrics endpoint with info, up, documents, templates, PDF, anonymization metrics
  - `lib/Controller/MetricsCollector.php` -- document and template counting
  - `lib/Controller/HealthController.php` -- health check endpoint
- **Implemented metrics**: docudesk_info, docudesk_up, docudesk_documents_total, docudesk_templates_total, docudesk_pdf_generations_total, docudesk_anonymizations_total
- **Not yet implemented**: requests_total, request_duration_seconds, errors_total, PDF/anonymization duration histograms

### Standards & References
- **Prometheus text exposition format**: https://prometheus.io/docs/instrumenting/exposition_formats/
- **OpenMetrics specification**: https://openmetrics.io/
- **OpenRegister MetricsService**: Reference implementation pattern
