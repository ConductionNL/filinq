## ADDED Requirements

### Requirement: Metrics Endpoint (REQ-PROM-01)

DocuDesk MUST expose a Prometheus-compatible metrics endpoint at `GET /api/metrics` with the correct content type and admin authentication.

#### Scenario: REQ-PROM-01-A — Authenticated admin retrieves metrics

- **GIVEN** an authenticated Nextcloud admin user
- **WHEN** `GET /api/metrics` is called
- **THEN** the HTTP response status is `200 OK`
- **AND** the `Content-Type` header is `text/plain; version=0.0.4; charset=utf-8`
- **AND** the body contains metrics in Prometheus text exposition format

#### Scenario: REQ-PROM-01-B — Every metric has HELP and TYPE annotations

- **GIVEN** the metrics endpoint returns a response
- **WHEN** the response body is parsed line by line
- **THEN** each metric family has a `# HELP <metric_name> <description>` line preceding its first sample
- **AND** each metric family has a `# TYPE <metric_name> <type>` line declaring its type (`gauge`, `counter`, or `histogram`)

#### Scenario: REQ-PROM-01-C — Unauthenticated access is rejected

- **GIVEN** no Nextcloud session or admin credentials are provided
- **WHEN** `GET /api/metrics` is called
- **THEN** the response status is `401 Unauthorized` or `403 Forbidden`
- **AND** no metrics data is included in the response body

---

### Requirement: Standard Application Metrics (REQ-PROM-02)

DocuDesk MUST expose standard application-identity and health gauges on every installation.

#### Scenario: REQ-PROM-02-A — Application info gauge reflects current version

- **GIVEN** DocuDesk version `0.0.32` is installed on PHP `8.2.x` and Nextcloud `29.x.x`
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains the line `docudesk_info{version="0.0.32",php_version="8.2.x",nextcloud_version="29.x.x"} 1`
- **AND** the `# TYPE docudesk_info gauge` line precedes it

#### Scenario: REQ-PROM-02-B — Up gauge indicates healthy app

- **GIVEN** DocuDesk is running and responding to requests
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains `docudesk_up 1`
- **AND** the `# TYPE docudesk_up gauge` line precedes it

#### Scenario: REQ-PROM-02-C — Version label reflects upgrade

- **GIVEN** DocuDesk has been upgraded from `0.0.31` to `0.0.32`
- **WHEN** `GET /api/metrics` is called after the upgrade
- **THEN** `docudesk_info` carries the label `version="0.0.32"`
- **AND** the previous version label `0.0.31` is no longer present

#### Scenario: REQ-PROM-02-D — Version labels are read from IConfig

- **GIVEN** the DocuDesk version is stored in `IConfig` as an app value
- **WHEN** `MetricsController` builds the info gauge
- **THEN** it reads the version via `IConfig::getAppValue('docudesk', 'installed_version', '0.0.0')`
- **AND** it reads PHP version from `PHP_VERSION` constant and Nextcloud version from `IConfig::getSystemValue`

---

### Requirement: App-Specific Metrics (REQ-PROM-03)

DocuDesk MUST expose counts of its core domain objects and operational counters as Prometheus metrics.

#### Scenario: REQ-PROM-03-A — Document count gauge

- **GIVEN** 50 document objects exist in the DocuDesk OpenRegister register
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains `docudesk_documents_total 50`
- **AND** the count was obtained via `MetricsCollector::countDocuments()`

#### Scenario: REQ-PROM-03-B — Template count gauge

- **GIVEN** 15 template objects exist in the DocuDesk OpenRegister register
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains `docudesk_templates_total 15`
- **AND** the count was obtained via `MetricsCollector::countTemplates()`

#### Scenario: REQ-PROM-03-C — PDF generation counter

- **GIVEN** 200 PDF generation operations have been completed since installation
- **AND** the counter is stored as `IConfig` app value `pdf_generations_total` with value `"200"`
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains `docudesk_pdf_generations_total 200`
- **AND** the `# TYPE docudesk_pdf_generations_total counter` line precedes it

#### Scenario: REQ-PROM-03-D — Anonymization counter

- **GIVEN** 75 anonymization operations have been completed since installation
- **AND** the counter is stored as `IConfig` app value `anonymizations_total` with value `"75"`
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains `docudesk_anonymizations_total 75`
- **AND** the `# TYPE docudesk_anonymizations_total counter` line precedes it

#### Scenario: REQ-PROM-03-E — Counter defaults to zero on fresh install

- **GIVEN** DocuDesk has just been installed and no PDF generations have occurred
- **AND** no `IConfig` app value `pdf_generations_total` has been set
- **WHEN** `GET /api/metrics` is called
- **THEN** the response body contains `docudesk_pdf_generations_total 0`

---

### Requirement: Planned Standard Metrics (REQ-PROM-04)

DocuDesk SHOULD expose request-level counters and a latency histogram. These metrics are reserved for future implementation; they MUST NOT be exposed until fully implemented to avoid presenting incomplete or zero-only data.

#### Scenario: REQ-PROM-04-A — Request counter (planned)

- **GIVEN** `docudesk_requests_total` is implemented
- **WHEN** `GET /api/metrics` is called
- **THEN** the metric is present with labels `method`, `endpoint`, and `status`
- **AND** its type is `counter`

#### Scenario: REQ-PROM-04-B — Request latency histogram (planned)

- **GIVEN** `docudesk_request_duration_seconds` is implemented
- **WHEN** `GET /api/metrics` is called
- **THEN** the metric is present with labels `method` and `endpoint`
- **AND** its type is `histogram`
- **AND** standard `_bucket`, `_sum`, and `_count` suffix lines are present

#### Scenario: REQ-PROM-04-C — Error counter (planned)

- **GIVEN** `docudesk_errors_total` is implemented
- **WHEN** `GET /api/metrics` is called
- **THEN** the metric is present with label `type`
- **AND** its type is `counter`

---

### Requirement: Planned Duration Histograms (REQ-PROM-05)

DocuDesk SHOULD expose per-operation duration histograms for PDF generation and anonymization. These metrics are reserved for future implementation.

#### Scenario: REQ-PROM-05-A — PDF generation duration histogram (planned)

- **GIVEN** `docudesk_pdf_generation_duration_seconds` is implemented
- **WHEN** `GET /api/metrics` is called
- **THEN** the metric is present as a histogram
- **AND** it enables derivation of p50, p95, and p99 latency percentiles

#### Scenario: REQ-PROM-05-B — Anonymization duration histogram (planned)

- **GIVEN** `docudesk_anonymization_duration_seconds` is implemented
- **WHEN** `GET /api/metrics` is called
- **THEN** the metric is present as a histogram
- **AND** it reflects variation in processing time based on document size

---

### Requirement: Health Check Endpoint (REQ-PROM-06)

DocuDesk MUST expose `GET /api/health` returning a JSON document with overall status and per-component check results.

#### Scenario: REQ-PROM-06-A — All components healthy

- **GIVEN** the Nextcloud database is accessible
- **AND** OpenRegister is installed and its `ObjectService` is reachable
- **WHEN** `GET /api/health` is called
- **THEN** the HTTP response status is `200 OK`
- **AND** the response body is `{"status": "ok", "checks": {"database": "ok", "openregister": "ok"}}`

#### Scenario: REQ-PROM-06-B — Degraded state (dependency unavailable)

- **GIVEN** the Nextcloud database is accessible
- **AND** OpenRegister is not reachable or throws an exception
- **WHEN** `GET /api/health` is called
- **THEN** the HTTP response status is `200 OK`
- **AND** the response body is `{"status": "degraded", "checks": {"database": "ok", "openregister": "error"}}`

#### Scenario: REQ-PROM-06-C — Error state (database unavailable)

- **GIVEN** the Nextcloud database is not accessible
- **WHEN** `GET /api/health` is called
- **THEN** the HTTP response status is `200 OK`
- **AND** the response body contains `{"status": "error", "checks": {"database": "error"}}`

#### Scenario: REQ-PROM-06-D — Health endpoint is publicly accessible

- **GIVEN** no authentication credentials are provided
- **WHEN** `GET /api/health` is called
- **THEN** the response is returned without a `401` or `403` error
- **AND** the controller is annotated with `#[PublicPage]` and `#[NoCSRFRequired]`

---

### Requirement: MetricsCollector Delegation (REQ-PROM-07)

`MetricsController` MUST delegate OpenRegister counting queries to `MetricsCollector` and MUST NOT call `ObjectService` directly.

#### Scenario: REQ-PROM-07-A — Document count delegated to MetricsCollector

- **GIVEN** `MetricsController` needs the total document count
- **WHEN** it builds the `docudesk_documents_total` metric value
- **THEN** it calls `$this->metricsCollector->countDocuments()`
- **AND** `MetricsCollector::countDocuments()` queries OpenRegister's `ObjectService` for the total

#### Scenario: REQ-PROM-07-B — Template count delegated to MetricsCollector

- **GIVEN** `MetricsController` needs the total template count
- **WHEN** it builds the `docudesk_templates_total` metric value
- **THEN** it calls `$this->metricsCollector->countTemplates()`
- **AND** `MetricsCollector::countTemplates()` queries OpenRegister's `ObjectService` for the total

#### Scenario: REQ-PROM-07-C — Counter values read from IConfig in controller

- **GIVEN** PDF generation and anonymization counters are stored as `IConfig` app values
- **WHEN** `MetricsController` builds the counter metrics
- **THEN** it reads `pdf_generations_total` via `IConfig::getAppValue('docudesk', 'pdf_generations_total', '0')`
- **AND** it reads `anonymizations_total` via `IConfig::getAppValue('docudesk', 'anonymizations_total', '0')`
- **AND** both values are cast to `int` before being written into the metrics body

#### Scenario: REQ-PROM-07-D — MetricsCollector handles OpenRegister unavailability gracefully

- **GIVEN** OpenRegister's `ObjectService` throws an exception
- **WHEN** `MetricsCollector::countDocuments()` is called
- **THEN** it catches the exception, logs the error server-side
- **AND** returns `0` so `MetricsController` can still respond `200` with degraded counts
