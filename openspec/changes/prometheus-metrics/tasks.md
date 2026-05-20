## Tasks

### Deduplication Check

- [ ] 1. **Deduplication check** — confirm no existing DocuDesk service overlaps with `MetricsController` or `MetricsCollector`; grep `lib/` for any prior `/api/metrics` route or Prometheus-format output; grep OpenRegister's `lib/Service/` for a shared `MetricsService` that could be reused instead of `MetricsCollector`; document findings (expected: no overlap — metrics are new in this change).

### Backend — MetricsCollector

- [ ] 2. **`MetricsCollector::countDocuments()`** — verify `lib/Controller/MetricsCollector.php` exists and implements `countDocuments(): int`; if not present, create the class with constructor injection of `ObjectService`; query OpenRegister for total document objects in the DocuDesk register; catch any `\Throwable`, log the error via `$this->logger->error()`, and return `0`; add `@spec openspec/changes/prometheus-metrics/tasks.md#task-2` to class and method PHPDoc.

- [ ] 3. **`MetricsCollector::countTemplates()`** — verify or implement `countTemplates(): int` on `MetricsCollector`; same pattern as `countDocuments()` targeting the template schema; add `@spec` tag.

- [ ] 4. **`MetricsCollector` unit tests** — `tests/unit/Controller/MetricsCollectorTest.php`: `countDocuments()` returns `ObjectService` count; `countTemplates()` returns `ObjectService` count; `countDocuments()` returns `0` and logs when `ObjectService` throws; mock `ObjectService` and `ILogger`.

### Backend — MetricsController

- [ ] 5. **`MetricsController::metrics()` — endpoint existence and content-type** — verify `lib/Controller/MetricsController.php` exists; confirm `GET /api/metrics` route is registered in `appinfo/routes.php`; confirm the controller method returns `Content-Type: text/plain; version=0.0.4; charset=utf-8`; confirm no `#[NoAdminRequired]` or `#[PublicPage]` annotation (admin-only by default per ADR-005); add `@spec` tags.

- [ ] 6. **`MetricsController` — `docudesk_info` gauge** — confirm `docudesk_info` is emitted with labels `version`, `php_version`, `nextcloud_version`; version read from `IConfig::getAppValue('docudesk', 'installed_version', '0.0.0')`; PHP version from `PHP_VERSION`; Nextcloud version from `IConfig::getSystemValue('version', '0.0.0')`; confirm `# HELP` and `# TYPE gauge` lines precede the sample line.

- [ ] 7. **`MetricsController` — `docudesk_up` gauge** — confirm `docudesk_up 1` is present with `# HELP` and `# TYPE gauge` annotations.

- [ ] 8. **`MetricsController` — `docudesk_documents_total` and `docudesk_templates_total` gauges** — confirm controller calls `$this->metricsCollector->countDocuments()` and `$this->metricsCollector->countTemplates()` (not `ObjectService` directly); values appear with `# HELP` and `# TYPE gauge` annotations.

- [ ] 9. **`MetricsController` — `docudesk_pdf_generations_total` counter** — confirm value read from `IConfig::getAppValue('docudesk', 'pdf_generations_total', '0')` cast to `int`; annotated as `# TYPE counter`; defaults to `0` when no IConfig value is set.

- [ ] 10. **`MetricsController` — `docudesk_anonymizations_total` counter** — confirm value read from `IConfig::getAppValue('docudesk', 'anonymizations_total', '0')` cast to `int`; annotated as `# TYPE counter`; defaults to `0` when no IConfig value is set.

- [ ] 11. **`MetricsController` — unauthenticated access** — verify Nextcloud middleware rejects requests without admin credentials; no DocuDesk-side auth check required (default behaviour); cover with an integration test that sends a request without a session and asserts `401` or `403`.

- [ ] 12. **`MetricsController` unit tests** — `tests/unit/Controller/MetricsControllerTest.php`: response content-type header; `docudesk_info` label values from mocked IConfig; `docudesk_up` value is `1`; document and template counts come from mocked `MetricsCollector`; PDF and anonymization counters are parsed from IConfig and cast to int; counter defaults to `0` when IConfig value absent; every metric has a `# HELP` and `# TYPE` line.

### Backend — HealthController

- [ ] 13. **`HealthController::health()` — endpoint and authentication** — verify `lib/Controller/HealthController.php` exists and `GET /api/health` is registered in `appinfo/routes.php`; confirm `#[PublicPage]` and `#[NoCSRFRequired]` attributes are present; confirm response Content-Type is `application/json`.

- [ ] 14. **`HealthController` — healthy state** — when DB ping succeeds and OpenRegister is reachable, response body is `{"status": "ok", "checks": {"database": "ok", "openregister": "ok"}}`.

- [ ] 15. **`HealthController` — degraded state** — when DB ping succeeds but OpenRegister throws, response body is `{"status": "degraded", "checks": {"database": "ok", "openregister": "error"}}`; HTTP status remains `200`.

- [ ] 16. **`HealthController` — error state** — when DB ping fails, response body contains `{"status": "error", "checks": {"database": "error"}}`; HTTP status remains `200`.

- [ ] 17. **`HealthController` unit tests** — `tests/unit/Controller/HealthControllerTest.php`: all-ok path; degraded path (OpenRegister down); error path (DB down); response shape matches JSON schema; HTTP status is always `200`.

### Routes

- [ ] 18. **Route registration** — confirm `appinfo/routes.php` contains entries for both `GET /api/metrics` → `MetricsController::metrics` and `GET /api/health` → `HealthController::health`; routes MUST appear before any wildcard `{slug}` catch-all routes per ADR-003.

### @spec Traceability

- [ ] 19. **@spec PHPDoc tags** — every class and public method in `MetricsController`, `MetricsCollector`, and `HealthController` MUST carry `@spec openspec/changes/prometheus-metrics/tasks.md#task-N` in PHPDoc; file-level `@spec` in header docblock; verify with a grep for `@spec` in all three files.

### Planned Metrics (Reserved — Do Not Implement Now)

- [ ] 20. **Reserved metric names (documentation only)** — add a `METRICS.md` or inline PHPDoc comment in `MetricsController` reserving the following names for future implementation; do NOT emit these metrics until instrumentation is complete:
  - `docudesk_requests_total` (counter, labels: `method`, `endpoint`, `status`) — PROM-030
  - `docudesk_request_duration_seconds` (histogram, labels: `method`, `endpoint`) — PROM-031
  - `docudesk_errors_total` (counter, label: `type`) — PROM-032
  - `docudesk_pdf_generation_duration_seconds` (histogram) — PROM-040
  - `docudesk_anonymization_duration_seconds` (histogram) — PROM-041

### Quality Gates

- [ ] 21. **`composer check:strict`** — run `composer check:strict`; fix any PHPCS/PHPMD/PHPStan issues in touched files (pre-existing issues in the same files must also be fixed per project policy).

- [ ] 22. **Manual smoke test** — log in as an admin; call `GET /index.php/apps/docudesk/api/metrics` and confirm Prometheus text format; call `GET /index.php/apps/docudesk/api/health` and confirm JSON with `"status": "ok"`; log out and call both endpoints, confirm `401`/`403` for metrics and `200` for health; import the response into a Prometheus scrape config locally and confirm no parse errors.

- [ ] 23. **Newman / API integration test** — add a Postman/Newman collection test: authenticated `GET /api/metrics` → `200`, correct content-type, body matches `# HELP docudesk_info`; unauthenticated `GET /api/metrics` → `401`/`403`; `GET /api/health` (no auth) → `200`, body has `status` key.
