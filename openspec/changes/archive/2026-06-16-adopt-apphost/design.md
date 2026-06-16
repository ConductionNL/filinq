# Design: adopt-apphost

## Mechanism

```
appinfo/routes.php (UNCHANGED)
  ['name' => 'health#index',  'url' => 'api/health',  'verb' => 'GET']
  ['name' => 'metrics#index', 'url' => 'api/metrics', 'verb' => 'GET']
        │  Nextcloud resolves the route name to a controller FQCN
        ▼
  OCA\DocuDesk\Controller\HealthController   ── container alias ─▶ OCA\OpenRegister\AppHost\Controller\GenericHealthController  (appName=docudesk)
  OCA\DocuDesk\Controller\MetricsController  ── container alias ─▶ OCA\OpenRegister\AppHost\Controller\GenericMetricsController (appName=docudesk)
        │
        ▼
  GenericController reads docudesk/src/manifest.json → observability block
        ├─ HealthCheckExecutor runs the declared checks  → {status, app, version, checks}
        └─ MetricsEngine renders implicit info/up + declared metrics → Prometheus 0.0.4
```

The route names still resolve to `OCA\DocuDesk\Controller\HealthController` /
`…\MetricsController`. Those PHP classes are deleted; instead `Application::register()` registers
those exact FQCN **strings** as container services whose factories return the OpenRegister generic
controllers. Nextcloud's dispatcher resolves the controller through the DI container by name, so a
registered service short-circuits the (now absent) class. The auth attributes are read from the
returned instance's class — `#[PublicPage]` on `GenericHealthController::index`, none on
`GenericMetricsController::index` (admin-only) — so the posture is engine-owned.

## Why the MetricsEngine is built explicitly

OpenRegister registers its own `MetricsEngine` factory under the `openregister` app container; that
factory is not visible from DocuDesk's container, and the generic `MetricsController` would
otherwise auto-wire a fresh `MetricsEngine`, which fails (the engine needs the four metric sources
+ renderer + cache + config). The DocuDesk alias factory therefore constructs `MetricsEngine`
explicitly from the sources resolved off the server container (`ObjectMetricSource`,
`TableMetricSource`, `AppConfigMetricSource`, `ProviderMetricSource`, `PrometheusRenderer`,
`ManifestLoader`, `ICacheFactory`, `IConfig`, `LoggerInterface`). `GenericHealthController`'s deps
(`ManifestLoader`, `HealthCheckExecutor`) resolve cleanly off the server container, so health needs
no explicit wiring.

## Health-check severity choice

The old controller set the overall status to **degraded** (not error) when OpenRegister was missing,
and **error** only when the database round-trip failed. Reproduced with:

- `database` check, severity `critical` (failure ⇒ status `error`, HTTP 503 under `adr006`)
- `openregister` check via `orAvailable`, severity `degraded` (failure ⇒ status `degraded`, HTTP 200)

## Metric mapping

| Manifest metric (engine prefixes `docudesk_`) | Old source | Declarative source |
|---|---|---|
| `documents_total` (gauge) | `MetricsCollector::countDocuments()` | `objectCount` schema `document` |
| `templates_total` (gauge) | `MetricsCollector::countTemplates()` | `objectCount` schema `template` |
| `pdf_generations_total` (counter) | `IAppConfig::getValueInt('pdf_generations_total', 0)` | `appConfig` key `pdf_generations_total` |
| `anonymizations_total` (counter) | `IAppConfig::getValueInt('anonymizations_total', 0)` | `appConfig` key `anonymizations_total` |
| `docudesk_info`, `docudesk_up` | hand-written | implicit (engine-owned) |

## Out of scope

- AppHost boilerplate controllers (`Bootstrap::register`, `Routes::standard`, generic
  Settings/Preferences/Dashboard/Init/AdminSettings/DeepLink) — not yet shipped by OpenRegister.
- The `observability` key is not yet in the `@conduction/nextcloud-vue` app-manifest v2 schema
  (`additionalProperties:false`). gate-22 currently fail-opens for DocuDesk (the installed lib does
  not expose the validator subpath), but the canonical schema SHOULD add `observability` so the key
  is legitimate fleet-wide. Tracked as a follow-up against nextcloud-vue.
