# Tasks: multi-tenant-hardening

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `organisationSettings` schema to the `document` register in `lib/Settings/docudesk_register.json` (REQ-DDMTH-007)
  - Properties per design.md D4, all overrides nullable; no organisation property (REQ-DDMTH-001); additive register version bump with changelog entry; `authorization` block limiting write to the admin surface.
  - Verify against HEAD before editing: schema list and register slugs may have moved since this spec was authored.

- [ ] 1.2 Add seed data: one `organisationSettings` object (42-day period, WOO profile, huisstijl ref) per design.md Seed Data
  - Placeholder identifiers only (nil UUID / `seed-*`/`demostad-*`); validates on boot import.

## 2. Backend — enforcement

- [ ] 2.1 Implement the system-context seam (REQ-DDMTH-002)
  - Single named wrapper, literal reason string required, every use logged with reason + caller; unreachable from request parameters; PHPUnit covers logging + guard.

- [ ] 2.2 Remove `_rbac:false` / `_multitenancy:false` from `BasesResolverService`, `PolicyMatchService`, `PolicyRetroactiveService`, `GrondslagProposalService` (REQ-DDMTH-002)
  - Classify each call site per design.md D2 before removing; user-request paths get no replacement; argument-capture unit tests pin the flags stay absent.

- [ ] 2.3 Remove `_rbac:false` / `_multitenancy:false` from `ConsentService`, `PolicyCrudService`, `GrondslagenSummaryService`; route the objection-deadline sweep through the seam (REQ-DDMTH-002, REQ-DDMTH-003)
  - Re-verify `ConsentUpdateHandler` / `ConsentCrudService` call paths named in GH #283; do not absorb the CSRF/field-whitelist fixes owned by the security wave (GH #282–#304).

- [ ] 2.4 Add the grep-shaped guardrail test: zero bypass flags in `lib/Service/**` outside the seam (REQ-DDMTH-002)
  - PHPUnit scans the source tree; failure message names the offending file/line.

- [ ] 2.5 Implement the legacy-organisation backfill repair step (REQ-DDMTH-006)
  - Envelope-only, idempotent, skips `signingAuditEntry`, per-schema counts logged; unit-tested against seeded null-organisation fixtures.

## 3. Backend — per-organisation behaviour

- [ ] 3.1 Add the effective-settings helper to `SettingsService` and route consumers through it (REQ-DDMTH-008)
  - Resolution order org override → IAppConfig → code default; consent deadline computation, `WooProfileService` profile lookup, and huisstijl resolution for generation (REQ-DDMTH-004) all read through it; org-admin write guard via `OrganisationService::isOrganisationAdmin()`.

- [ ] 3.2 Scope dashboard counters and batch/anonymisation reports to the active organisation; label reports with organisation name + UUID (REQ-DDMTH-005)
  - Instance-wide variant admin-only through the seam, labelled "all organisations".

## 4. Frontend

- [ ] 4.1 Organisation settings section on the settings surface (REQ-DDMTH-007, REQ-DDMTH-008)
  - Consent period, anonymization profile, default-huisstijl picker (org-scoped options); visible to organisation admins; NcCheckboxRadioSwitch/NcSelect with inputLabel; NL Design tokens via NC CSS variables.

- [ ] 4.2 Verify org-scoped listings/pickers and dashboard through the manifest views (REQ-DDMTH-003, REQ-DDMTH-004, REQ-DDMTH-005)
  - No frontend re-filtering logic — OR's object API does the scoping; empty-state on unresolvable organisation.

## 5. Spec maintenance

- [ ] 5.1 Update `openspec/specs/admin-settings/spec.md`: set Status in-progress and append this change to its OpenSpec changes list
  - Delta REQ-DDMTH-007/-008 merge into the canonical spec at archive time.

## 6. Quality

- [ ] 6.1 PHPUnit unit tests for seam, flag removal, resolution helper, backfill, fail-closed paths — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 6.2 Playwright e2e `tests/e2e/workflows/multi-tenant-isolation.spec.ts` + `tests/e2e/spec-coverage/multi-tenant.spec.ts` + `tests/e2e/spec-coverage/organisation-settings.spec.ts` covering the `@e2e`-referenced scenarios with two seeded organisations on the Postgres dev instance
  - Includes the GH #283 forgery scenario failing and the nldesign-theme accessibility pass on the new settings section.

- [ ] 6.3 i18n: EN + NL for all new UI strings (organisation settings section, report labels, empty states)
  - Keys in English.

- [ ] 6.4 Documentation `docs/features/multi-tenant.md` with Playwright MCP screenshots (ADR-010); run `openspec validate multi-tenant-hardening --strict`
  - Documents the OR-owned organisation boundary, the resolution order, and the backfill behaviour for upgraders.
