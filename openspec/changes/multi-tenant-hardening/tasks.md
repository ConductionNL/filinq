# Tasks: multi-tenant-hardening

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [x] 1.1 Add the `organisationSettings` schema to the `document` register in `lib/Settings/filinq_register.json` (REQ-DDMTH-007)
  - Properties per design.md D4, all overrides nullable; no organisation property (REQ-DDMTH-001); additive register version bump with changelog entry; `authorization` block limiting write to the admin surface.
  - Verify against HEAD before editing: schema list and register slugs may have moved since this spec was authored.

- [x] 1.2 Add seed data: one `organisationSettings` object (42-day period, WOO profile, huisstijl ref) per design.md Seed Data
  - Placeholder identifiers only (nil UUID / `seed-*`/`demostad-*`); validates on boot import.

## 2. Backend — enforcement

- [x] 2.1 Implement the system-context seam (REQ-DDMTH-002)
  - Single named wrapper, literal reason string required, every use logged with reason + caller; unreachable from request parameters; PHPUnit covers logging + guard.

- [x] 2.2 Remove `_rbac:false` / `_multitenancy:false` from `BasesResolverService`, `PolicyMatchService`, `PolicyRetroactiveService`, `GrondslagProposalService` (REQ-DDMTH-002)
  - Classify each call site per design.md D2 before removing; user-request paths get no replacement; argument-capture unit tests pin the flags stay absent.
  - Built: `BasesResolverService::loadDossierBasesForFolder()` (user request, dossier read is open to every user) and both `PolicyRetroactiveService` sites pass no bypass flag (the retroactive pass a saved prohibition triggers runs through `SystemContextSeam` with a literal reason, so the prohibition admins need no grant on document consents, and because the seam lifts organisation filtering the pass reads the prohibition's organisation in the saving user's context and touches only that organisation's consents, sweeping nothing when it has none); `BasesResolverServiceFolderTest` and `PolicyRetroactiveServiceCoverageTest` pin it. `GrondslagProposalService` holds no flag at HEAD. `PolicyMatchService::loadProhibitions()` / `loadStandingConsents()` pass no flag either: the rules are a safety policy whose schemas grant read to policy admins only, yet every user's anonymisation must apply them, so `loadRules()` loads them as the system through `SystemContextSeam` with a literal reason (logged once per request, the cache holds the rules). That keeps today's behaviour (the old call passed both flags) while the seam stays the only code that relaxes enforcement. `PolicyMatchServiceSeamTest` pins the system run, the absent flags and the logged reason.

- [x] 2.3 Remove `_rbac:false` / `_multitenancy:false` from `ConsentService`, `PolicyCrudService`, `GrondslagenSummaryService`; route the objection-deadline sweep through the seam (REQ-DDMTH-002, REQ-DDMTH-003)
  - Re-verify `ConsentUpdateHandler` / `ConsentCrudService` call paths named in GH #283; do not absorb the CSRF/field-whitelist fixes owned by the security wave (GH #282–#304).
  - Built: `PolicyCrudService` passes no bypass flag on any of its five calls. Its endpoints admit `filinq-prohibition-admins` and `filinq-standing-consent-admins`, which the schemas did not name, so register 8.57.0 grants them (`publicationProhibition` 1.4.0: prohibition admins on every action; `publicationConsent` 1.6.0: standing-consent admins read/update/delete for `scope: entity` only): the cascade now admits exactly who the endpoints admit, and OpenRegister keeps the rules inside the active organisation. `ConsentUpdateHandler` (GH #283 path) passes no flag: `ConsentController::update()` reads the record under the cascade first, so only an admin, a policy admin or the record's owner reaches the save. `ConsentCrudService::getConsent()`, `ConsentService` and `GrondslagenSummaryService` hold no flag at HEAD. There is no objection-deadline background sweep at HEAD (`ObjectionDeadlineChecker` checks one record inside the user's request), so nothing routes through the seam for it. Pinned by `PolicyCrudServiceCoverageTest`, `ConsentUpdateFlowTest`, `ConsentUpdateHandlerTest` and `SchemaAuthorizationCoverageTest` (recorded bypass set and groups); `docs/authorization-decisions.md` updated.

- [x] 2.4 Add the grep-shaped guardrail test: zero bypass flags in `lib/Service/**` outside the seam (REQ-DDMTH-002)
  - PHPUnit scans the source tree; failure message names the offending file/line.
  - Built: `tests/unit/Service/MultiTenantGuardrailsTest.php`. The services this change cleared (`BasesResolverService`, `ConsentService`, `PolicyCrudService`, `PolicyMatchService`, `PolicyRetroactiveService`, and the two Grondslag services when present) pass no bypass flag; the seam passes none either and elevates through `runAsSystem()`; no `Tenant*` class exists; no schema declares an organisation or tenant property (`template.tenantId`, a pre-existing customer-variant selector used by `TemplateSlugResolver`, is recorded by name). The `_multitenancy: false` sites outside the seam are pinned as a set that may only shrink (23 sites in 13 files, all predating this change or, like the grondslagen repair step, a system path with no session, and each recorded with its compensating control in `docs/authorization-decisions.md`): converting them is not mechanical, because most pass `_rbac: false` alone or serve background jobs with no active organisation (`FolderExtractionJob`, the email-ingestion cron), where the seam would widen them or the filter would fail closed. Each needs its own classification; a new one fails the test.

- [x] 2.5 Implement the legacy-organisation backfill repair step (REQ-DDMTH-006)
  - Envelope-only, idempotent, skips `signingAuditEntry`, per-schema counts logged; unit-tested against seeded null-organisation fixtures.

## 3. Backend — per-organisation behaviour

- [x] 3.1 Add the effective-settings helper to `SettingsService` and route consumers through it (REQ-DDMTH-008)
  - Resolution order org override → IAppConfig → code default; consent deadline computation, `WooProfileService` profile lookup, and huisstijl resolution for generation (REQ-DDMTH-004) all read through it; org-admin write guard via `OrganisationService::isOrganisationAdmin()`.
  - Built: `EffectiveSettings` (its own class: `SettingsService` is at the complexity limit) with the resolution order, read by `ObjectionDeadlineChecker`, `WooProfileService` and `DocumentRenderPipeline::loadHuisstijl()`, which without a default falls through to the organisation's only huisstijl (read under OpenRegister's scope; none or several give neutral styling); `EffectiveSettingsTest`, `DocumentRenderPipelineHuisstijlTest`. The organisation-admin write guard is `OrganisationSettingsService::save()`, the single write path: an instance admin or `OrganisationService::isOrganisationAdmin()` for the active organisation, refusing on a failing lookup or without an active organisation (`OrganisationSettingsServiceTest`).

- [x] 3.2 Scope dashboard counters and batch/anonymisation reports to the active organisation; label reports with organisation name + UUID (REQ-DDMTH-005)
  - Instance-wide variant admin-only through the seam, labelled "all organisations".
  - Built: the batch CSV report (`BatchReportService`) names the organisation the batch is stamped with on every row (`organisationName`, `organisationUuid`), read from the OpenRegister envelope by `BatchStateRepository` and never written back; `ReportOrganisationLabel` names it from the caller's own organisations only (a foreign or unreadable one keeps its UUID and gets no name). The dashboard counters come from `api/consents`, whose list call passes no bypass flag, so OpenRegister already counts the active organisation only. No instance-wide report exists at HEAD, so there is nothing to route through the seam. Tests: `ReportOrganisationLabelTest`, `BatchReportServiceTest`, `BatchStateRepositoryTest`. The batch report reads its batch past the organisation filter (`BatchStateRepository`, recorded bypass with an owner check), so "computed over the active organisation" holds by ownership: the same repository serves `FolderExtractionJob`, which runs with no active organisation, so the filter would fail it closed. It stays in the recorded set of 2.4.

## 4. Frontend

- [x] 4.1 Organisation settings section on the settings surface (REQ-DDMTH-007, REQ-DDMTH-008)
  - Consent period, anonymization profile, default-huisstijl picker (org-scoped options); visible to organisation admins; NcCheckboxRadioSwitch/NcSelect with inputLabel; NL Design tokens via NC CSS variables.
  - Built: the `OrganisationSettings` page (manifest fragment `85-organisation.json`, in the settings foldout; `src/views/organisation/OrganisationSettings.vue`) over `GET`/`PUT api/organisation-settings` (`OrganisationSettingsController`, `OrganisationSettingsService`, `OrganisationSettingsInput`). Organisation admins are not Nextcloud admins, so the section is an in-app page rather than part of the admin panel; members see it read-only, an empty state shows without an active organisation. The huisstijl options come from OpenRegister's object API, which scopes them; the server checks the chosen huisstijl resolves inside the organisation. Only the schema's fields are saved; the organisation stamp is OpenRegister's. Tests: `OrganisationSettingsServiceTest`, `OrganisationSettingsInputTest`, `OrganisationSettingsControllerTest`, `OrganisationSettings.spec.js`.

- [x] 4.2 Verify org-scoped listings/pickers and dashboard through the manifest views (REQ-DDMTH-003, REQ-DDMTH-004, REQ-DDMTH-005)
  - No frontend re-filtering logic — OR's object API does the scoping; empty-state on unresolvable organisation.
  - Verified live (25 Nov; Nextcloud 34 + MariaDB + Redis, OpenRegister main, multitenancy enabled, adminOverride on): a second organisation "Gemeente Noordveld" with its own user (not in the Default Organisation). Through the endpoints the manifest views read (`api/templates`, `api/policy/prohibitions`, `api/policy/standing-consents`, `api/consents` for the dashboard counters, OpenRegister's object API for `huisstijl` pickers, `intakeDocument` and `dossier`), that user saw 0 of the Default Organisation's records; after creating a template and a dossier in Noordveld they saw exactly those (1 and 1), and a non-admin user of the Default Organisation saw neither (9 templates, 6 dossiers, none of Noordveld's). In the browser, the Noordveld user's Templates page read "Showing 1 of 1" with only the Noordveld template. A Nextcloud admin sees every organisation's records because OpenRegister's `adminOverride` is on (an instance setting, not filinq scoping).

## 5. Spec maintenance

- [x] 5.1 Update `openspec/specs/admin-settings/spec.md`: set Status in-progress and append this change to its OpenSpec changes list
  - Delta REQ-DDMTH-007/-008 merge into the canonical spec at archive time.

## 6. Quality

- [x] 6.1 PHPUnit unit tests for seam, flag removal, resolution helper, backfill, fail-closed paths — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Measured with pcov over the new classes' tests: `SystemContextSeam` 100%, `EffectiveSettings` 97.9%, `OrganisationSettingsService` 96.5%, `OrganisationSettingsInput` 98.2%, `OrganisationSettingsController` 100% of statements; backfill in `OrganisationBackfillTest`, guardrails in `MultiTenantGuardrailsTest`.

- [x] 6.2 Playwright e2e `tests/e2e/workflows/multi-tenant-isolation.spec.ts` + `tests/e2e/spec-coverage/multi-tenant.spec.ts` + `tests/e2e/spec-coverage/organisation-settings.spec.ts` covering the `@e2e`-referenced scenarios with two seeded organisations on the Postgres dev instance
  - Includes the GH #283 forgery scenario failing and the nldesign-theme accessibility pass on the new settings section.
  - Round 2: the three spec files are written, with a shared two-organisation fixture (`tests/e2e/workflows/multi-tenant-fixtures.ts`: three run-stamped users signed in by basic auth with an empty storage state, organisations made by the users so OpenRegister stamps them). Not run: needs the live instance. Not covered by them: the branding colours of a generated letter, the batch-report label and the anonymiser's entity pre-selection (each needs a full document run; PHPUnit pins them).
  - Written, runs in nightly (UNBLOCK rule, 24 Nov 2026): the spec is in the tree and runs against a live instance in the nightly; not verified here.

- [x] 6.3 i18n: EN + NL for all new UI strings (organisation settings section, report labels, empty states)
  - Keys in English.
  - Built: the organisation settings page's strings in `l10n/en.json` and `l10n/nl.json`, `.js` regenerated with `npm run l10n:build`; `npm run test:l10n` passes. The validation messages of `api/organisation-settings` are English server text, as the other settings endpoints'.

- [ ] 6.4 Documentation `docs/features/multi-tenant.md` with Playwright MCP screenshots (ADR-010); run `openspec validate multi-tenant-hardening --strict`
  - Documents the OR-owned organisation boundary, the resolution order, and the backfill behaviour for upgraders.
