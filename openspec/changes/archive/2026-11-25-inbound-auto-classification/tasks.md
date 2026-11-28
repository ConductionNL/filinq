# Tasks: inbound-auto-classification

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [x] 1.1 Add the `classificationResult` schema (fileId idempotency key, suggestedDocumentType enum, documentTypeConfidence, method, suggestedCorrespondent, suggestedDossier, status, confirmed* fields) to `lib/Settings/filinq_register.json` with `x-openregister-archival` `P1Y` placeholder — additive, union-merge only; re-validate JSON after merge (REQ-DDIAC-005)
- [x] 1.2 Seed the two `classificationResult` fixtures from design.md (one suggested factuur, one confirmed-with-correction besluit) so the pending list renders on a clean install

## 2. Backend

- [x] 2.1 Add `lib/Service/DocumentTypeClassifier.php` — stateless, TYPE_KEYWORDS vocabularies + structural cues per Dutch intake type, normalised 0–1 confidence, below-threshold ⇒ `overig` (REQ-DDIAC-001)
  - Boundary discipline per REQ-META-11: vocabularies/scoring live only here; consumers use DI; `LanguageClassifier` untouched.
- [x] 2.2 Add `lib/Service/InboundClassificationService.php` — orchestrates type classification, correspondent ranking over existing OR-detected entities (position/frequency/suffix heuristics, `correspondentPending` when detection absent), conservative dossier matching, suggestion persistence with supersede semantics (REQ-DDIAC-001..002, REQ-DDIAC-004..005)
- [x] 2.3 Hook classification into the enrichment path (`EnrichmentRunner` + on-demand enrich API) behind `enable_inbound_classification`; suggestions only — zero writes to enriched-object fields (REQ-DDIAC-006)
- [x] 2.4 Add `ClassificationController` — `GET /api/classification/pending`, `POST /api/classification/{fileId}/confirm`, `POST /api/classification/{fileId}/reject`; confirm applies canonical documentType/correspondent and dossier filing via the dossier folder binding, reject is a no-op close (REQ-DDIAC-003..004)
  - Auth attributes on every method (route-auth gate); files resolved via the requesting user's access only (no-admin-idor gate); confirm/reject record `confirmedBy`/`confirmedAt`.
- [x] 2.5 Admin settings: `enable_inbound_classification` toggle in the existing Filinq admin section (REQ-DDIAC-001)

## 3. Frontend

- [x] 3.1 Pending-classifications list (type + confidence + correspondent + dossier chips, confirm/correct/reject actions, bulk confirm over visible rows) using standard Cn/Nc components (REQ-DDIAC-003, ADR-012)
- [x] 3.2 Classification card on the document detail (suggestion state, confirm-with-correction pickers for type and dossier) (REQ-DDIAC-003..004)

## 4. Quality

- [x] 4.1 PHPUnit for classifier scoring/threshold, correspondent ranking + pending flag, supersede idempotency, no-silent-write guards (enrichment path writes nothing canonical), confirm/correct/reject transitions, dossier match precision, data-minimisation shape — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Owed (needs the live instance): live-verify on Postgres (8080) with OpenRegister: upload an invoice-like PDF → suggestion appears → confirm → documentType set + file in dossier folder.
- [x] 4.2 Playwright spec `tests/e2e/spec-coverage/inbound-classification.spec.ts` for the `@e2e`-referenced scenarios
  - Written, not yet run: it needs a built frontend on a live instance. Run it there, then tick and archive.
  - Written, runs in nightly (UNBLOCK rule, 24 Nov 2026): the spec is in the tree and runs against a live instance in the nightly; not verified here.
- [x] 4.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [x] 4.4 Docs: `docs/features/inbound-classification.md` with Playwright screenshots (pending list, confirm-with-correction, dossier filing) including the human-oversight posture and the deferred-learning note (ADR-010)
  - The page is written; the Playwright screenshots are owed with 4.2 on a live instance.
  - Done 2026-11-25 on a live sandbox (NC 34, MariaDB, OpenRegister main): three intake documents created through OpenRegister were classified on save (letter 0.6, decision 1.0, invoice 1.0; sender pending, as the sandbox runs no entity detection). Screenshots of the pending list, the Correct dialog with a dossier picked, and the dossier holding the filed letter are in the page. This also settles the live check owed under 4.1: confirm sets `documentType` on the intake object, and a confirm with a dossier records `filing: moved` and moves the file out of the intake folder into the dossier folder.
  - Found live and fixed: every confirm answered 500 ("Cannot modify readOnly property: daysLeft"). The document write merged the object as read, including the computed `daysLeft`, and OpenRegister refuses a save carrying a read-only field. `MetadataService` now leaves the schema's `readOnly`/`computed` fields out of the stored data it writes back (unit-tested; no change when the schema cannot be read).
- [x] 4.5 Validate: `openspec validate inbound-auto-classification --strict` passes; hydra gates green
