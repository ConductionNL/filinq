## Context

DocuDesk runs in municipality and government hosting environments that use Prometheus-based monitoring stacks. Prior to this change there was no machine-readable health or metrics surface; operators had to infer health from Nextcloud logs or user-reported errors. ADR-006 mandates `GET /api/metrics` (Prometheus text, admin auth) and `GET /api/health` (JSON) for all Conduction apps. This change implements that contract for DocuDesk and defines the set of business-relevant metrics.

The implementation splits responsibility across two PHP classes: `MetricsController` formats and returns the Prometheus text response, while `MetricsCollector` (a service class) performs the OpenRegister queries. This mirrors the Controller → Service pattern required by ADR-003 and keeps the controller thin.

## Goals / Non-Goals

**Goals**

- Expose `GET /api/metrics` in Prometheus text exposition format (content-type `text/plain; version=0.0.4; charset=utf-8`).
- Require admin authentication on the metrics endpoint; reject unauthenticated requests via Nextcloud's middleware.
- Include `# HELP` and `# TYPE` annotations for every metric.
- Expose the six implemented metrics: `docudesk_info`, `docudesk_up`, `docudesk_documents_total`, `docudesk_templates_total`, `docudesk_pdf_generations_total`, `docudesk_anonymizations_total`.
- Expose `GET /api/health` returning JSON with per-component status (`ok` | `degraded` | `error`).
- Delegate document and template counting to `MetricsCollector` (separation of concerns).
- Document the planned-but-not-yet-implemented metrics so future contributors know what to add.

**Non-Goals**

- Instrumenting individual HTTP requests with duration histograms — planned, tracked as PROM-030 through PROM-041.
- Providing a Prometheus push-gateway integration — pull-only, standard Prometheus scrape.
- Exposing per-user or per-document metrics — aggregate counts only; no PII.
- Building a custom scrape authentication layer — Nextcloud admin auth is sufficient per ADR-005.

## Decisions

### D1. Admin authentication via Nextcloud middleware

The metrics endpoint is annotated with the default (no annotation = admin-only) Nextcloud controller behaviour. `#[NoAdminRequired]` is NOT added. This gives admin-only access without custom authentication logic, consistent with ADR-003 and ADR-005. The health endpoint is annotated `#[PublicPage]` and `#[NoCSRFRequired]` — infrastructure probes must not require a session.

**Trade-off:** Prometheus scrapers must present admin credentials (basic auth or token). This is standard practice for Nextcloud apps; operators configure the Prometheus job with an admin service account.

### D2. MetricsCollector as a separate service class

`MetricsController` does not call OpenRegister's `ObjectService` directly. Instead it injects `MetricsCollector`, which owns the count queries. This satisfies the Controller → Service layering in ADR-003 (controllers stay thin), makes the counting logic independently testable, and means a future refactor to a dedicated `MetricsService` is isolated.

**Trade-off:** Adds one extra class. Justified by ADR-003 strict layering requirement and the expectation that more metrics will be added over time.

### D3. Counter values stored in IConfig, not OpenRegister

`docudesk_pdf_generations_total` and `docudesk_anonymizations_total` are read from `IConfig::getAppValue()`, not from OpenRegister object counts. These counters are incremented inside DocuDesk's PDF and anonymisation pipelines; they are operational counters (a running total since install), not queryable domain objects. Storing them in IConfig is lightweight, atomic-increment-friendly, and consistent with ADR-001 ("App config → `IAppConfig`. NOT OpenRegister").

### D4. Prometheus text format is hand-built, no library

DocuDesk does not pull in a Prometheus PHP client library. The text format (RFC: `# HELP`, `# TYPE`, `metric{labels} value`) is simple enough to build as a PHP string. Adding a composer dependency for six metrics would increase install footprint without material gain.

**Trade-off:** New metric types (histograms with bucket lines) will require more careful string construction. Revisit if planned PROM-031/040/041 histograms land.

### D5. Health check checks database and OpenRegister connectivity

The health endpoint performs two checks: (1) a lightweight DB ping (e.g., `SELECT 1`) and (2) an OpenRegister availability check (attempt to reach `ObjectService` or read a known config value). The response body distinguishes `ok`, `degraded` (partial failure), and `error` (critical component down). This matches the three-state model from REQ-PROM-06 and gives monitoring infrastructure actionable signal.

### D6. Planned metrics declared but not implemented

PROM-030 through PROM-041 (request counter, request duration histogram, error counter, operation duration histograms) are declared in this change's spec and tasks but marked as "Planned". They require middleware instrumentation that is out of scope for the initial implementation. Declaring them here reserves the metric names and prevents future naming drift.

## Reuse Analysis

Per ADR-001 deduplication requirements:

| Existing service | Used? | How |
|---|---|---|
| `ObjectService::findAll()` / `countObjects()` | Yes | Via `MetricsCollector::countDocuments()` and `countTemplates()` |
| `IConfig` (Nextcloud core) | Yes | Reading version labels and counter values |
| `IGroupManager::isAdmin()` | Indirectly | Nextcloud middleware enforces admin gate without DocuDesk-side check |
| OpenRegister `MetricsService` | Reference pattern only | Metrics endpoint pattern mirrors OpenRegister's own `/api/metrics` implementation |

No existing DocuDesk service overlaps with the new `MetricsCollector`. The counting queries are new; the controller is new.

## Seed Data

Not applicable. This change introduces no OpenRegister schemas and no domain objects. Metrics are derived from existing data (object counts and IConfig values). Per ADR-001 exceptions, changes that only modify backend logic and introduce no schemas do not require seed data.

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| OpenRegister unavailable at scrape time | `MetricsCollector::countDocuments()` returns `0` on exception; `HealthController` reports `"openregister": "error"` — metrics endpoint still responds 200 with degraded counts rather than 500 |
| IConfig counter drift (e.g., app reinstall resets to 0) | Counters start at 0 on fresh install; the lack of persistence across reinstall is acceptable for operational counters — Prometheus tracks `increase()` from the last known value |
| Admin credential exposure to scrape infrastructure | Standard Prometheus operational concern; not DocuDesk-specific. Document in admin guide. |
| Future naming conflicts for planned histogram metrics | Reserved in spec now (PROM-030–041); implementors MUST use these exact names |

## Open Questions

- Should `GET /api/health` return HTTP 200 for `degraded` state, or HTTP 207? Current implementation returns 200 for all states to avoid confusing load-balancer health probes. Review once first production deployment provides feedback.
- For planned PROM-031 (`docudesk_request_duration_seconds` histogram), which histogram bucket boundaries should be used? Propose `[0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]` seconds — revisit during implementation based on observed PDF-generation latency distribution.
