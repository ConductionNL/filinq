# Proposal: adopt-apphost

## Why

DocuDesk hand-maintained three observability classes — `HealthController`, `MetricsController`,
and `MetricsCollector` — to serve `/api/health` and `/api/metrics`. OpenRegister now ships the
**AppHost observability engine** (`OCA\OpenRegister\AppHost`, ADR-006 / ADR-040): leaf apps declare
their health checks and Prometheus metrics as a JSON `observability` block in `src/manifest.json`,
and re-point their existing endpoints at OpenRegister's engine-owned generic controllers
(`GenericHealthController`, `GenericMetricsController`). The engine owns the response contract and
the auth posture (health public, metrics admin-only) so a leaf app can no longer drift them.

Two latent defects in the bespoke code are fixed for free on adoption:

1. **Health was not public.** The old `HealthController::index()` carried only `@NoCSRFRequired`,
   so Nextcloud required a logged-in session — an anonymous probe got `401`. ADR-006 says the
   health endpoint is public. The generic controller is `#[PublicPage]`.
2. **No `app` / `version` in the health body.** The old body was `{status, checks}`; ADR-006
   specifies `{status, app, version, checks}`.

The `documents_total` / `templates_total` metrics also silently always read `0` because the
bespoke collector resolved register/schema from app-config keys (`document_register`,
`document_schema`) that DocuDesk never sets. The declarative `objectCount` source resolves by
schema slug instead, so the metric now reports the real count when objects exist.

## What

- **Declarative observability** — add an `observability` block to `src/manifest.json`:
  - `health.checks` = `database` (critical) + `openregister` via `orAvailable` at **degraded**
    severity (reproducing the old controller's "OR missing ⇒ degraded, not error" semantics);
    `statusCodePolicy: adr006`.
  - `metrics` = `documents_total` (gauge, `objectCount` schema `document`), `templates_total`
    (gauge, `objectCount` schema `template`), `pdf_generations_total` (counter, `appConfig`),
    `anonymizations_total` (counter, `appConfig`). The engine prepends the implicit
    `docudesk_info` + `docudesk_up` metrics, matching the old output exactly.
- **Re-point routes via container aliases** — `appinfo/routes.php` is unchanged (`api/health`,
  `api/metrics` keep their URLs). `Application::register()` registers the two route-target FQCNs
  (`OCA\DocuDesk\Controller\HealthController`, `…\MetricsController`) as thin subclasses of the
  OpenRegister generic controllers with `appName = docudesk`. The classes are kept (route resolves
  to a concrete DocuDesk controller with an explicit auth attribute) but hold no logic — `index()`
  just calls `parent::index()`.
- **Delete** the bespoke logic: `lib/Controller/MetricsCollector.php` + the old
  Health/Metrics/Collector unit tests; replace with a manifest parity test.

## What stays

All DocuDesk domain controllers and services are untouched: GDPR consent, PDF generation /
conversion, anonymisation, signing, dossiers, templates, comparison, correspondence. AppHost's
boilerplate-controller offload (`Bootstrap::register` / `Routes::standard` / generic
Settings/Preferences/Dashboard/Init) is **not yet shipped** in OpenRegister, so no further
mechanical plumbing is replaced in this change — only observability.

## Parity (verified live against the dev environment)

| Endpoint | Before | After |
|---|---|---|
| `GET /api/health` (anon) | `401` (login required) | `200` `{status, app, version, checks}` — public |
| `GET /api/health` checks | `{database:ok, openregister:ok}` | identical |
| `GET /api/metrics` (anon) | `401` | `401` (admin-only preserved) |
| `GET /api/metrics` (admin) | `200`, 6 metrics, `text/plain; version=0.0.4` | byte-for-byte identical |

## Impact

- **Removed logic** (~390 LOC): bespoke health checks + metric lines + `MetricsCollector.php`
  deleted; `HealthController` / `MetricsController` reduced from ~115/135 LOC to ~30 LOC delegators.
  3 obsolete test files deleted.
- **Added**: `observability` block in `src/manifest.json`; `registerAppHostObservability()` in
  `Application.php`; `tests/unit/AppHost/ObservabilityManifestParityTest.php`.
- **Dependency**: requires OpenRegister with the AppHost observability engine (already a hard
  dependency per ADR-022).
