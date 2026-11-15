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
  - Built: `BasesResolverService::loadDossierBasesForFolder()` (user request, dossier read is open to every user) and both `PolicyRetroactiveService` sites (run in the request of the policy admin who changed the prohibition) pass no bypass flag; `BasesResolverServiceFolderTest` and `PolicyRetroactiveServiceCoverageTest` pin it. `GrondslagProposalService` holds no flag at HEAD. `PolicyMatchService::loadProhibitions()` / `loadStandingConsents()` pass no flag either: the rules are a safety policy whose schemas grant read to policy admins only, yet every user's anonymisation must apply them, so `loadRules()` loads them as the system through `SystemContextSeam` with a literal reason (logged once per request, the cache holds the rules). That keeps today's behaviour (the old call passed both flags) while the seam stays the only code that relaxes enforcement. `PolicyMatchServiceSeamTest` pins the system run, the absent flags and the logged reason.

- [x] 2.3 Remove `_rbac:false` / `_multitenancy:false` from `ConsentService`, `PolicyCrudService`, `GrondslagenSummaryService`; route the objection-deadline sweep through the seam (REQ-DDMTH-002, REQ-DDMTH-003)
  - Re-verify `ConsentUpdateHandler` / `ConsentCrudService` call paths named in GH #283; do not absorb the CSRF/field-whitelist fixes owned by the security wave (GH #282–#304).
  - Built: `PolicyCrudService` passes no bypass flag on any of its five calls. Its endpoints admit `filinq-prohibition-admins` and `filinq-standing-consent-admins`, which the schemas did not name, so register 8.57.0 grants them (`publicationProhibition` 1.4.0: prohibition admins on every action; `publicationConsent` 1.6.0: standing-consent admins read/update/delete for `scope: entity` only): the cascade now admits exactly who the endpoints admit, and OpenRegister keeps the rules inside the active organisation. `ConsentUpdateHandler` (GH #283 path) passes no flag: `ConsentController::update()` reads the record under the cascade first, so only an admin, a policy admin or the record's owner reaches the save. `ConsentCrudService::getConsent()`, `ConsentService` and `GrondslagenSummaryService` hold no flag at HEAD. There is no objection-deadline background sweep at HEAD (`ObjectionDeadlineChecker` checks one record inside the user's request), so nothing routes through the seam for it. Pinned by `PolicyCrudServiceCoverageTest`, `ConsentUpdateFlowTest`, `ConsentUpdateHandlerTest` and `SchemaAuthorizationCoverageTest` (recorded bypass set and groups); `docs/authorization-decisions.md` updated.

- [ ] 2.4 Add the grep-shaped guardrail test: zero bypass flags in `lib/Service/**` outside the seam (REQ-DDMTH-002)
  - PHPUnit scans the source tree; failure message names the offending file/line.

- [x] 2.5 Implement the legacy-organisation backfill repair step (REQ-DDMTH-006)
  - Envelope-only, idempotent, skips `signingAuditEntry`, per-schema counts logged; unit-tested against seeded null-organisation fixtures.

## 3. Backend — per-organisation behaviour

- [ ] 3.1 Add the effective-settings helper to `SettingsService` and route consumers through it (REQ-DDMTH-008)
  - Resolution order org override → IAppConfig → code default; consent deadline computation, `WooProfileService` profile lookup, and huisstijl resolution for generation (REQ-DDMTH-004) all read through it; org-admin write guard via `OrganisationService::isOrganisationAdmin()`.
  - Built: `EffectiveSettings` (its own class: `SettingsService` is at the complexity limit) with the resolution order, read by `ObjectionDeadlineChecker`, `WooProfileService` and `DocumentRenderPipeline::loadHuisstijl()`; `EffectiveSettingsTest`, `DocumentRenderPipelineHuisstijlTest`. Left: the organisation-admin write guard, which belongs with the settings section of 4.1.

- [ ] 3.2 Scope dashboard counters and batch/anonymisation reports to the active organisation; label reports with organisation name + UUID (REQ-DDMTH-005)
  - Instance-wide variant admin-only through the seam, labelled "all organisations".
  - Built: the batch CSV report (`BatchReportService`) names the organisation the batch is stamped with on every row (`organisationName`, `organisationUuid`), read from the OpenRegister envelope by `BatchStateRepository` and never written back; `ReportOrganisationLabel` names it from the caller's own organisations only (a foreign or unreadable one keeps its UUID and gets no name). The dashboard counters come from `api/consents`, whose list call passes no bypass flag, so OpenRegister already counts the active organisation only. No instance-wide report exists at HEAD, so there is nothing to route through the seam. Tests: `ReportOrganisationLabelTest`, `BatchReportServiceTest`, `BatchStateRepositoryTest`. Left: the batch report reads its batch past the organisation filter (`BatchStateRepository`, recorded bypass with an owner check), so "computed over the active organisation" holds by ownership, not by OpenRegister; moving it under the filter belongs with 2.4.

## 4. Frontend

- [ ] 4.1 Organisation settings section on the settings surface (REQ-DDMTH-007, REQ-DDMTH-008)
  - Consent period, anonymization profile, default-huisstijl picker (org-scoped options); visible to organisation admins; NcCheckboxRadioSwitch/NcSelect with inputLabel; NL Design tokens via NC CSS variables.

- [ ] 4.2 Verify org-scoped listings/pickers and dashboard through the manifest views (REQ-DDMTH-003, REQ-DDMTH-004, REQ-DDMTH-005)
  - No frontend re-filtering logic — OR's object API does the scoping; empty-state on unresolvable organisation.

## 5. Spec maintenance

- [x] 5.1 Update `openspec/specs/admin-settings/spec.md`: set Status in-progress and append this change to its OpenSpec changes list
  - Delta REQ-DDMTH-007/-008 merge into the canonical spec at archive time.

## 6. Quality

- [ ] 6.1 PHPUnit unit tests for seam, flag removal, resolution helper, backfill, fail-closed paths — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 6.2 Playwright e2e `tests/e2e/workflows/multi-tenant-isolation.spec.ts` + `tests/e2e/spec-coverage/multi-tenant.spec.ts` + `tests/e2e/spec-coverage/organisation-settings.spec.ts` covering the `@e2e`-referenced scenarios with two seeded organisations on the Postgres dev instance
  - Includes the GH #283 forgery scenario failing and the nldesign-theme accessibility pass on the new settings section.

- [ ] 6.3 i18n: EN + NL for all new UI strings (organisation settings section, report labels, empty states)
  - Keys in English.

- [ ] 6.4 Documentation `docs/features/multi-tenant.md` with Playwright MCP screenshots (ADR-010); run `openspec validate multi-tenant-hardening --strict`
  - Documents the OR-owned organisation boundary, the resolution order, and the backfill behaviour for upgraders.
