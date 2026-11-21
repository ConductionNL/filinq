# Tasks: archiefwet-retention-engine

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [x] 1.1 Add the `archief` register with `selectielijstEntry`, `destructionList` and `destructionCertificate` schemas to `lib/Settings/filinq_register.json` (REQ-DDARE-001, REQ-DDARE-005)
  - Field contracts exactly per design.md D2; `hardValidation: true`; `archive.defaultNominatie: bewaren` on the two workflow schemas; register version bump.
  - Unit drift-pin: every field OR's `lookupSelectielijstEntry()` reads exists on `selectielijstEntry`; verify against OR HEAD, not against this spec's snapshot.
  - Built (2026-11-21): the three schemas are in the consolidated `filinq` register (register 8.99.0), not in a separate `archief` register, which `DossierRegisterConfigTest` refuses (design D2, amended). Read on OpenRegister `development`: `selectielijstEntry` holds `categorie`, `archiefnominatie`, `bewaartermijn` (ISO-8601 pattern), `bron`, `toelichting`, `omschrijving` and `selectielijstVersie`, the key the import writes, and stays open for further imported columns; `destructionList` knows all seven statuses and every key `DestructionService`, `DestructionReviewService` and `DestructionListRun` write, and the certificate's fields, because OpenRegister stores the certificate in the destruction-list schema; `destructionCertificate` holds the certificate on its own. Both carry `archive.defaultNominatie: bewaren`. Three demo rows each in the mock register (`DEMO-` categories, not real ones). Pinned by `ArchiefRegisterSchemaTest`, which validates OpenRegister's payloads closed and, with an OpenRegister checkout beside the app, reads the import's keys from its source.

- [ ] 1.2 Seed representative VNG selectielijst 2020 entries (design.md Seed Data)
  - `TODO-*` placeholder category codes during authoring only; one `bewaren` and at least one `vernietigen` entry; validates on import. Placeholders MUST be replaced with real codes before apply — see task 1.4 (apply-blocker).

- [ ] 1.4 APPLY-BLOCKER — replace every `TODO-*` placeholder with a real VNG selectielijst-manager-approved category number before apply/done (REQ-DDARE-009)
  - Obtain the real category codes with matching `archiefnominatie` + `bewaartermijn` from the responsible selectielijst-manager (records-appraisal sign-off); this change MUST NOT be marked done while any `TODO-*` categorie remains. Add a PHPUnit seed-lint test that FAILS on any `TODO-` categorie so the gate enforces it (production-enablement gate, same posture as flow-operations E3 retention).

- [x] 1.3 Add `archive` configuration to the record schemas `correspondence`, `generatedDocument`, `publicationRecord` (REQ-DDARE-020) and remove `x-openregister-archival` from record classes; rewrite the `batchCorrespondenceJob` annotation to `{"retention": {"default": "P1Y"}}` (REQ-DDARE-008, REQ-DREG-01/02)
  - Import-pin unit test proves the `archive` key survives `ConfigurationService::importFromApp()`; file an OpenRegister issue if it is dropped (degradation: OR schema UI configuration, never Filinq-side retention code).
  - Built (2026-11-21): `correspondence` 1.5.0, `generatedDocument` 1.12.0 and `publicationRecord` 1.5.0 carry `archive` in the keys OpenRegister `development` reads (`enabled`, `classification` — not `classificatie` —, `afleidingswijze` with `closureField: generatedAt` or `bronEigenschap: publicatiedatum`); correspondence keeps its term as `defaultNominatie: vernietigen`, `defaultBewaartermijn: P7Y` and loses `x-openregister-archival`, so it leaves only through an approved destruction list. The categories are `TODO-` placeholders for task 1.4. `batchCorrespondenceJob` already had the object shape `{"retention": {"default": "P1Y"}}`. Register 8.100.0. Pinned by `RecordArchiveConfigurationTest` (the archive blocks, the REQ-DDARE-008 lint over every schema, and, with an OpenRegister checkout beside the app, that `Schema` has the `archive` column `hydrate()` fills on import and the calculator's trigger keys). The import was read in OpenRegister's source, not run; no issue needed.

## 2. Backend

- [x] 2.1 Implement `lib/Service/RetentionSurfaceService.php` (REQ-DDARE-003, REQ-DDARE-007)
  - Read-only retention state for UI; destruction-date propagation with the D6 precedence matrix (bridge-supplied date > archiefactiedatum > empty, never blocking); appends `destruction_date_propagated` to `publicationLogEntry`.
  - No date arithmetic, no eligibility scanning, no destruction execution anywhere in `lib/` (architecture test).
  - Built (2026-11-21): `RetentionSurfaceService::destructionDateFor()` picks the date by D6: the `vernietigingsdatum` on the bridge's `externalDocument` for the file (new optional date field, `externalDocument` 1.1.0, register 8.101.0), named after the `bridgeSource` (name, else vendor, else "zaaksysteem"); else `retention.archiefactiedatum` of the record whose object folder holds the file when it is appraised `vernietigen`, named "Archiefwet 1995, selectielijst categorie {classificatie}"; else none. Every unreadable source is logged and reads as no date. `PublicationPipelineService::handoff()` hands the date on through `setDestructionDate()` (so OpenCatalogi's `retentionExpiresAt`/`retentionNote` and the `destruction_date_propagated` log entry), and skips an unchanged date. The read for display needs no filinq method: the retention block on the document detail (task 3.3) reads the block OpenRegister renders on the object (D8). Pinned by `RetentionSurfaceServiceTest` (the precedence matrix), `PublicationPipelineServiceTest::testAHandoffCarriesTheDestructionDateTheRetentionSurfaceFinds`, `::testAHandoffWithoutADestructionDateIsNotBlocked`, `::testAnUnchangedDestructionDateIsNotPropagatedAgain` and `tests/unit/Architecture/RetentionArchitectureTest.php` (no `archiefactiedatum` written, no bewaartermijn arithmetic, no destruction machinery called in `lib/`).

- [ ] 2.2 Verify the per-object classificatie override surface against OR HEAD (design.md D4 uncertainty)
  - If OR exposes no per-object override endpoint, file the OR issue and ship schema-level categories + `extendArchiefactiedatum` only; record the decision on tracking issue #231.

## 3. Frontend

- [x] 3.1 Archiefbeheer admin settings section with "Koppel archiefregister" (REQ-DDARE-002)
  - Reads/writes OR `GET/PUT /api/settings/archival` directly; shows current owner; never writes without the explicit admin action (no repair step, no boot hook).
  - Built (2026-11-21): the admin-only **Archiefbeheer** section on the settings page (`src/views/settings/ArchivalWiringSettings.vue` over `src/services/archivalWiring.js`) reads OpenRegister's `GET /api/settings/archival` and says whether nothing is wired, filinq's register is wired, or the settings point at another register (named). "Link the archive register" (nl "Koppel archiefregister") asks to confirm, then PUTs the five ids of the `filinq` register and its `selectielijstEntry` and `destructionList` schemas, resolved from the registers the settings page already lists; the other archival settings are sent back as read, because OpenRegister resets every key a write leaves out. Without filinq's schemas imported it offers nothing to link. Strings in en and nl. Pinned by `tests/vitest/archivalWiring.spec.js`, `src/views/settings/ArchivalWiringSettings.spec.js`, the re-recorded settings golden and `RetentionArchitectureTest::testNoServerCodeWritesTheArchivalSettings` (no repair step, boot hook or service writes the settings). The Playwright scenario is task 4.2.

- [x] 3.2 Vernietigingslijsten index + detail with approve/partial/reject (REQ-DDARE-004)
  - `CnIndexPage`/`CnDataTable`/`CnFormDialog`; frontend calls OR `/api/archival/destruction-lists*` directly — zero Filinq pass-through controllers; mandatory reasons for exclusion and rejection; modals in `src/modals/`.
  - Built (2026-11-21): the **Destruction lists** page (`/destruction-lists/:id?`, `src/manifest.d/60-records.json`, `src/views/destructionLists/DestructionLists.vue`) over `src/services/destructionLists.js`, which calls OpenRegister's `GET api/archival/destruction-lists` (status filter), `GET …/{id}`, `POST …/{id}/approve` and `POST …/{id}/reject` and nothing of filinq's. The overview (`CnIndexPage`) lists status, record count and date, and says when OpenRegister has no destruction list register; one list (`CnDataTable`) shows each proposed record's title, schema, register, archiefactiedatum and selectielijst category. "Keep this record" asks a reason in `src/modals/RequiredReasonModal.vue` (`CnFormDialog`, empty reason refused before anything is sent); "Approve list" sends `approve_all`, and with kept records "Approve the rest" sends `approve_partial` with each uuid and its reason; "Reject list" asks a reason in the same modal. A list leaves the decision once it is past `in_review`/`awaiting_second_approval`, and OpenRegister's 403 and 409 are shown as they come. Strings in en and nl. Pinned by `tests/vitest/destructionLists.spec.js`, `src/modals/RequiredReasonModal.spec.js` and `src/views/destructionLists/DestructionLists.spec.js`. The Playwright scenarios are task 4.2.

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

## 5. Moved in from e-discovery-legal-hold

- [ ] 5.1 Name the legal hold case behind each hold-excluded record on the vernietigingslijst detail
  - Moved here from e-discovery-legal-hold task 3.2 (2026-09-29): the Archiefbeheer surface is built by this change. A record whose `retention.legalHold.reason` is `filinq-hold-case:<uuid>` resolves to that case's name through `GET api/legal-holds/{uuid}` for hold authority; everybody else sees "under a legal hold" without the name.
