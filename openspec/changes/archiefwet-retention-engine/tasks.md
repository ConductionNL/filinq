# Tasks: archiefwet-retention-engine

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `archief` register with `selectielijstEntry`, `destructionList` and `destructionCertificate` schemas to `lib/Settings/filinq_register.json` (REQ-DDARE-001, REQ-DDARE-005)
  - Field contracts exactly per design.md D2; `hardValidation: true`; `archive.defaultNominatie: bewaren` on the two workflow schemas; register version bump.
  - Unit drift-pin: every field OR's `lookupSelectielijstEntry()` reads exists on `selectielijstEntry`; verify against OR HEAD, not against this spec's snapshot.

- [ ] 1.2 Seed representative VNG selectielijst 2020 entries (design.md Seed Data)
  - `TODO-*` placeholder category codes during authoring only; one `bewaren` and at least one `vernietigen` entry; validates on import. Placeholders MUST be replaced with real codes before apply — see task 1.4 (apply-blocker).

- [ ] 1.4 APPLY-BLOCKER — replace every `TODO-*` placeholder with a real VNG selectielijst-manager-approved category number before apply/done (REQ-DDARE-009)
  - Obtain the real category codes with matching `archiefnominatie` + `bewaartermijn` from the responsible selectielijst-manager (records-appraisal sign-off); this change MUST NOT be marked done while any `TODO-*` categorie remains. Add a PHPUnit seed-lint test that FAILS on any `TODO-` categorie so the gate enforces it (production-enablement gate, same posture as flow-operations E3 retention).

- [ ] 1.3 Add `archive` configuration to the record schemas `correspondence`, `generatedDocument`, `publicationRecord` (REQ-DDARE-020) and remove `x-openregister-archival` from record classes; rewrite the `batchCorrespondenceJob` annotation to `{"retention": {"default": "P1Y"}}` (REQ-DDARE-008, REQ-DREG-01/02)
  - Import-pin unit test proves the `archive` key survives `ConfigurationService::importFromApp()`; file an OpenRegister issue if it is dropped (degradation: OR schema UI configuration, never Filinq-side retention code).

## 2. Backend

- [ ] 2.1 Implement `lib/Service/RetentionSurfaceService.php` (REQ-DDARE-003, REQ-DDARE-007)
  - Read-only retention state for UI; destruction-date propagation with the D6 precedence matrix (bridge-supplied date > archiefactiedatum > empty, never blocking); appends `destruction_date_propagated` to `publicationLogEntry`.
  - No date arithmetic, no eligibility scanning, no destruction execution anywhere in `lib/` (architecture test).

- [ ] 2.2 Verify the per-object classificatie override surface against OR HEAD (design.md D4 uncertainty)
  - If OR exposes no per-object override endpoint, file the OR issue and ship schema-level categories + `extendArchiefactiedatum` only; record the decision on tracking issue #231.

## 3. Frontend

- [ ] 3.1 Archiefbeheer admin settings section with "Koppel archiefregister" (REQ-DDARE-002)
  - Reads/writes OR `GET/PUT /api/settings/archival` directly; shows current owner; never writes without the explicit admin action (no repair step, no boot hook).

- [ ] 3.2 Vernietigingslijsten index + detail with approve/partial/reject (REQ-DDARE-004)
  - `CnIndexPage`/`CnDataTable`/`CnFormDialog`; frontend calls OR `/api/archival/destruction-lists*` directly — zero Filinq pass-through controllers; mandatory reasons for exclusion and rejection; modals in `src/modals/`.

- [ ] 3.3 Certificates view + overbrenging view + retention block on document detail (REQ-DDARE-005, REQ-DDARE-006)
  - Certificates from OR `/api/archival/certificates`; bewaren records past actiedatum listed for transfer; `overgebracht` records rendered read-only with a transfer indicator; NC CSS variables only (ADR-003).

## 4. Quality

- [ ] 4.1 PHPUnit unit tests — minimum 75% coverage on new code (ADR-009)
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Includes: register/seed import pins, selectielijst drift-pin, archive-key import pin, propagation precedence matrix, register-lint for REQ-DDARE-008 (no annotation on record classes, object shape elsewhere), architecture test for "no retention arithmetic", and the REQ-DDARE-009 seed-lint that fails on any `TODO-` categorie.

- [ ] 4.2 Playwright e2e `tests/e2e/workflows/archiefwet-retention.spec.ts` covering the `@e2e`-referenced scenarios
  - Wire settings, review/partial-approve/reject a vernietigingslijst, certificates list, overbrenging list; verify on the Postgres dev instance (port 8080); test through the UI.

- [ ] 4.3 i18n: EN + NL translations for all new UI strings (ADR-005)
  - Keys in English; NL terms (vernietigingslijst, verklaring van vernietiging, overbrenging, waardering) preserved as domain vocabulary in both locales.

- [ ] 4.4 Documentation `docs/features/archiefwet-retention.md` with Playwright MCP screenshots (ADR-010)
  - Covers selectielijst maintenance, the admin wiring step, the archivist workflow and the production checklist (replace `TODO-*` categories before enabling destruction).

- [ ] 4.5 Gates + validation
  - `composer check:strict` zero new violations; `openspec validate archiefwet-retention-engine --strict` exits 0; the REQ-DDARE-009 seed-lint passes (no `TODO-*` categorie remains); fix pre-existing quality issues encountered on touched files.
