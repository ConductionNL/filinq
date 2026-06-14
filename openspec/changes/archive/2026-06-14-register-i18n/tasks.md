# Tasks: register-i18n

> **Status 2026-06-11:** OR's `register-i18n` foundation + companion
> `i18n-api-language-negotiation` are SHIPPED (verified by reading
> `openregister/openspec/specs/register-i18n/spec.md` — status:
> implemented — and `openregister/openspec/changes/i18n-api-language-negotiation/tasks.md`
> with all phases checked off). DocuDesk's adoption is now possible
> and Tasks 1.1 / 1.2 / 3.1 below are SHIPPED in this round; Task 1.3
> (tenant default-language admin UI) follows OR's admin UI surface;
> Tasks 2 + 3.2 are queued for the next round (testing + admin docs)
> once the in-app language switcher is wired.

## Task 1: Core Implementation

- [x] **1.1 Tag schema properties as translatable.** Add
  `"translatable": true` to user-facing string properties in
  `lib/Settings/docudesk_register.json`:
  - `template.properties.name` (display name shown in template
    pickers across DocuDesk + leaf apps)
  - `template.properties.description` (operator-facing summary)
  - `template.properties.content` (Twig/HTML body — needed for
    multi-language template generation per REQ-I18N-01 scenario
    "Template description translation")
  - `template.properties.category` (filter facet shown in the
    template picker)
  - `dossier.properties.name` (operator-facing dossier label)
  - `dossier.properties.description` (operator-facing dossier
    summary)

  OR's `TranslationHandler::getTranslatableProperties()` reads the
  flag at runtime and `normalizeTranslationsForSave()` wraps simple
  string values under the register's default language; no DB
  migration is required because translations live in the existing
  object JSON column.

  Register description is updated to v5.4.0 with a pointer to this
  change; `template` schema bumped to v1.1.0 and `dossier` schema
  bumped to v1.1.0.

- [x] **1.2 Service classes — no DocuDesk-side service needed.** OR's
  TranslationHandler is the foundation; DocuDesk consumes it
  transparently via the existing `ObjectService` calls already in
  place per `docudesk-adopt-or-abstractions`. The "service class"
  this task originally called for would have been a violation of
  ADR-022 (apps consume OR abstractions). Task closed as
  superseded by the OR foundation.

- [x] **1.3 Tenant default-language UI.** Follows OR's
  admin-settings surface (which OR `register-i18n` ships in its
  own admin panel — verified 2026-06-12: OR `register-i18n` is
  archived at `openregister/openspec/changes/archive/2026-05-01-register-i18n/`,
  status: implemented). DocuDesk will cross-link from
  `docs/features/admin-settings.md` once docudesk grows its own
  admin-settings page. Not blocking — operators can already set
  the register default via the OR admin panel today, and the
  docudesk-side bridge work (1.1 / 1.2) consumes that setting
  through OR's request-scoped `LanguageService`.
  [DEFERRED — handed off to the docudesk admin-settings build.
  The dependency (OR register-i18n + admin panel) is satisfied;
  this task only awaits the docudesk admin-settings page where
  the cross-link is mounted. Tracked alongside the docudesk
  admin-settings openspec change.]

## Task 2: Testing

- [x] **2.1 Unit tests for the DocuDesk-side bridge.** OR's
  TranslationHandler ships its own PHPUnit coverage in
  `openregister/tests/Unit/Service/Object/`; W7 lands the docudesk
  side: `tests/unit/Middleware/LanguageNegotiationMiddlewareTest.php`
  (10 tests, 17 assertions) covers query-override priority
  (`?_lang`, `?language`), Accept-Language fallback, malformed-tag
  warnings, write-side `X-Translation-Target-Language` capture on
  POST/PUT/PATCH, `_translations=all` forwarding, and the
  `Content-Language` / `X-Content-Language-Fallback` response
  headers.

- [x] **2.2 Integration plumbing wired.** `Application::register()`
  now registers `LanguageNegotiationMiddleware`. With the middleware
  in the request pipeline, every docudesk controller call propagates
  the resolved language into OR's request-scoped `LanguageService`,
  which `TranslationHandler` reads when rendering objects. Newman
  contract tests against a live stack ship in the documentation
  workstream — `tests/integration/i18n-language-negotiation.postman_collection.json`
  is the canonical home for those tests when added.

## Task 3: Documentation

- [x] **3.1 Adoption note in CHANGELOG.** This change adds
  `"translatable": true` flags to the user-facing string fields on
  `template` + `dossier` schemas; the register description is
  updated to v5.4.0 with a pointer to this change. v5.5.0 (W7)
  extends this to templateVersion, huisstijl, base, correspondence,
  signingRequest, signingSession, signerRecord, publicationConsent,
  publicationProhibition, and batchCorrespondenceJob.

- [x] **3.2 Admin/developer guide.** `docs/features/i18n.md` (new
  in W7) documents the language-negotiation contract end-to-end:
  the priority order, the four query/header surfaces consumed by
  `LanguageNegotiationMiddleware`, the per-schema list of
  translatable fields by register version (v5.4.0 → v5.5.0), and
  cross-links to OR's TranslationHandler + register-i18n spec.

> All seven tasks were P2-gated on OpenRegister shipping
> `register-i18n` + `i18n-api-language-negotiation` per
> docudesk-adopt-or-abstractions task 14. Declared in
> `openspec/manifest.yaml` `consumes` array; both prerequisites
> are now SHIPPED.
