# adopt-apphost Specification

@e2e exclude Backend observability adoption — health/metrics are HTTP endpoints with no navigable
UI, the `observability` block is a manifest data contract, and "controllers are thin delegators" is
a source-structure invariant. Covered by PHPUnit (ObservabilityManifestParityTest), live endpoint
parity verification (anon + admin curl against /api/health and /api/metrics), and code review.

## Purpose

DocuDesk's `/api/health` and `/api/metrics` endpoints are served by OpenRegister's AppHost
observability engine (ADR-006 / ADR-040): thin DocuDesk subclasses of the engine-owned generic
controllers, driven by a declarative `observability` block in `src/manifest.json`. The bespoke
health checks, metric lines, and `MetricsCollector` are removed. Endpoint URLs and the
admin/public auth posture are preserved (with health correctly made public).

## Requirements

### Requirement: Declarative observability block in the manifest

DocuDesk SHALL declare its health checks and Prometheus metrics in the `observability` block of
`src/manifest.json`, consumed by the OpenRegister AppHost engine.

#### Scenario: Health checks declared

- **GIVEN** `src/manifest.json`
- **WHEN** the `observability.health` section is read
- **THEN** it declares `statusCodePolicy: adr006`, a `database` check at `critical` severity, and an
  `openregister` check of type `orAvailable` at `degraded` severity

#### Scenario: Metrics declared

- **GIVEN** `src/manifest.json`
- **WHEN** the `observability.metrics` array is read
- **THEN** it declares exactly `documents_total` (gauge, objectCount schema `document`),
  `templates_total` (gauge, objectCount schema `template`), `pdf_generations_total` (counter,
  appConfig), and `anonymizations_total` (counter, appConfig)

### Requirement: Endpoints re-pointed at the AppHost engine with unchanged URLs

DocuDesk SHALL serve `/api/health` and `/api/metrics` from thin subclasses of OpenRegister's generic
AppHost controllers, leaving the route URLs in `appinfo/routes.php` unchanged.

#### Scenario: Health endpoint is public and ADR-006 shaped

- **GIVEN** an anonymous request to `GET /api/health`
- **WHEN** the endpoint responds
- **THEN** it returns HTTP 200 with a body of shape `{status, app, version, checks}` where `app` is
  `docudesk`

#### Scenario: Metrics endpoint is admin-only Prometheus text

- **GIVEN** a request to `GET /api/metrics`
- **WHEN** the caller is anonymous
- **THEN** the response is not 200 (login/admin required)
- **AND WHEN** the caller is an admin
- **THEN** the response is HTTP 200 `text/plain; version=0.0.4` beginning with the implicit
  `docudesk_info` and `docudesk_up` metrics

### Requirement: Bespoke observability logic removed

DocuDesk SHALL NOT carry hand-written health-check or metric-collection logic once the declarative
block is in place. `HealthController` and `MetricsController` remain only as thin subclasses of the
OpenRegister generic controllers (so the routes resolve to concrete classes with explicit auth
attributes) and contain no observability logic; `MetricsCollector` is deleted.

#### Scenario: Controllers are thin delegators

- **GIVEN** `lib/Controller/HealthController.php` and `lib/Controller/MetricsController.php`
- **WHEN** they are read
- **THEN** each extends the OpenRegister generic AppHost controller and its `index()` only calls
  `parent::index()` with no bespoke checks or metric lines
- **AND** `lib/Controller/MetricsCollector.php` is absent
