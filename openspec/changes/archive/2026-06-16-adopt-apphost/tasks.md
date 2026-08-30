# Tasks: adopt-apphost

## 1. Declarative observability

- [x] 1.1 Add `observability` block to `src/manifest.json` — `health` (database critical +
  openregister orAvailable degraded, `statusCodePolicy: adr006`) and 4 `metrics`
  (`documents_total`, `templates_total`, `pdf_generations_total`, `anonymizations_total`).

## 2. Re-point routes at the AppHost engine

- [x] 2.1 `Application::register()` → `registerAppHostObservability()` registers the FQCN aliases
  `OCA\DocuDesk\Controller\HealthController` → `GenericHealthController(appName=docudesk)` and
  `OCA\DocuDesk\Controller\MetricsController` → `GenericMetricsController(appName=docudesk)` with an
  explicitly constructed `MetricsEngine`.
- [x] 2.2 `appinfo/routes.php` left unchanged — `api/health` + `api/metrics` URLs preserved.

## 3. Delete bespoke observability code

- [x] 3.1 Delete `lib/Controller/HealthController.php`.
- [x] 3.2 Delete `lib/Controller/MetricsController.php`.
- [x] 3.3 Delete `lib/Controller/MetricsCollector.php`.
- [x] 3.4 Delete obsolete tests `HealthControllerTest`, `MetricsControllerTest`,
  `MetricsCollectorTest`.

## 4. Parity test

- [x] 4.1 Add `tests/unit/AppHost/ObservabilityManifestParityTest.php` locking the manifest block
  against the old controllers' behaviour (health checks + 4 metric names/kinds/sources).

## 5. Verify

- [x] 5.1 PHPUnit green (no new failures vs baseline; pre-existing Transliterator errors unchanged).
- [x] 5.2 Live parity verified: health `{status, app, version, checks}` (now public),
  metrics byte-for-byte identical, admin-only posture preserved.
- [x] 5.3 Diff gate-clean; pre-existing DossierController/SigningController gate debt unchanged.
