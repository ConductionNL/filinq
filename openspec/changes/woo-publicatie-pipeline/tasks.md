# Tasks: woo-publicatie-pipeline

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 15.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add `publicationRecord` and `publicationLogEntry` schemas to the `document` register in `lib/Settings/docudesk_register.json` (REQ-DDWPP-001, REQ-DDWPP-003)
  - All properties per design.md D1; `x-openregister-lifecycle` on `publicationRecord` with canonical `initial: draft` and the REQ-DDWPP-003 transitions guarded on the readiness booleans; log schema immutable; register version bump with changelog entry.

- [ ] 1.2 Add seed data: one `published` record with full log trail, one `draft` record blocked on an open objection window (design.md Seed Data)
  - Placeholder identifiers only (nil UUID / `seed-*`); validates on boot import.

## 2. Backend

- [ ] 2.1 Add the consent-clearance query `ConsentService::isDocumentConsentClear()` (REQ-DDWPP-020)
  - Pure read-only; verdict + per-record reasons; exhaustive PHPUnit table test over all `consentStatus` × `publicationDecision` × deadline combinations; `ObjectionDeadlineChecker` untouched.

- [ ] 2.2 Implement `lib/Service/PublicationPipelineService.php`: readiness evaluation (REQ-DDWPP-002) + re-evaluation/demotion on handoff (REQ-DDWPP-003)
  - Entities-reviewed check from anonymisation review state (+ dossier `checkedOn`); prohibitions via existing `PolicyMatchService`; snapshot stores verdicts + UUIDs only, never entity text.

- [ ] 2.3 Implement handoff to OpenCatalogi (REQ-DDWPP-005)
  - OR `saveObject()` addressed to register slug `publication` / schema `publication`; field map in one service constant; redacted derivative attached per OpenCatalogi's publication-attachment convention (verify `publication-attachment-defaults` at apply); endpoint-absent → disabled state; OR 403 surfaced.

- [ ] 2.4 Implement de-publication (REQ-DDWPP-006) and destruction-date propagation (REQ-DDWPP-007)
  - Mandatory reason; `depublicatiedatum` set on the endpoint object, never deleted; `retentionExpiresAt` + `retentionNote` per RET-003; log entries for every step.

- [ ] 2.5 Implement append-only logging + `lib/Controller/PublicationController.php` with `api/publications/*` routes (REQ-DDWPP-008)
  - No update/delete route for log entries (PHPUnit route-table assertion); every controller method carries explicit auth attributes and guards.

- [ ] 2.6 Add an OpenCatalogi schema drift-pin unit test
  - Fixture of OpenCatalogi's `publication` schema properties; handoff field map validated against it so endpoint renames fail the suite, not production.

## 3. Frontend

- [ ] 3.1 Publications index + detail manifest pages (REQ-DDWPP-004, REQ-DDWPP-008)
  - `CnIndexPage`/`CnDataTable` with status chips; detail: readiness checklist with reasons, DiWoo form (`CnFormDialog`, wooCategory select limited to the 17 TOOI informatiecategorieën sourced from OpenCatalogi's value list), log timeline, withdraw + destruction-date actions; manifest schema refs use slugs.

- [ ] 3.2 Publish wizard entries on MyDocuments document detail and dossier context (REQ-DDWPP-009)
  - Stepped anonymize → consent → publish view; deep-links only, no capability reimplementation; NL Design tokens via NC CSS variables.

## 4. Spec maintenance

- [ ] 4.1 Update `openspec/specs/publication-consent/spec.md`: set Status in-progress and append this change to its OpenSpec changes list
  - Delta REQ-DDWPP-020 merges into the canonical spec at archive time.

## 5. Quality

- [ ] 5.1 PHPUnit unit tests for pipeline service, clearance query, controller and lifecycle guards — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Consent transitions validated against the GDPR/WOO lifecycle; PUT-semantic saves verified (a non-changed field survives).

- [ ] 5.2 Playwright e2e specs `tests/e2e/workflows/woo-publicatie-pipeline.spec.ts` + `tests/e2e/spec-coverage/woo-publications.spec.ts` covering the `@e2e`-referenced scenarios end-to-end with OpenRegister + OpenCatalogi on the Postgres dev instance
  - Includes the nldesign-theme accessibility pass on the new views.

- [ ] 5.3 i18n: EN + NL for all new UI strings (readiness reasons, DiWoo labels, wizard steps, log actions)
  - Keys in English.

- [ ] 5.4 Documentation `docs/features/woo-publicatie-pipeline.md` with Playwright MCP screenshots (ADR-010); run `openspec validate woo-publicatie-pipeline --strict`
  - Documents the OpenCatalogi endpoint boundary and the readiness gate semantics.
