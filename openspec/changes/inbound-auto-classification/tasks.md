# Tasks: inbound-auto-classification

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `classificationResult` schema (fileId idempotency key, suggestedDocumentType enum, documentTypeConfidence, method, suggestedCorrespondent, suggestedDossier, status, confirmed* fields) to `lib/Settings/docudesk_register.json` with `x-openregister-archival` `P1Y` placeholder — additive, union-merge only; re-validate JSON after merge (REQ-DDIAC-005)
- [ ] 1.2 Seed the two `classificationResult` fixtures from design.md (one suggested factuur, one confirmed-with-correction besluit) so the pending list renders on a clean install

## 2. Backend

- [ ] 2.1 Add `lib/Service/DocumentTypeClassifier.php` — stateless, TYPE_KEYWORDS vocabularies + structural cues per Dutch intake type, normalised 0–1 confidence, below-threshold ⇒ `overig` (REQ-DDIAC-001)
  - Boundary discipline per REQ-META-11: vocabularies/scoring live only here; consumers use DI; `LanguageClassifier` untouched.
- [ ] 2.2 Add `lib/Service/InboundClassificationService.php` — orchestrates type classification, correspondent ranking over existing OR-detected entities (position/frequency/suffix heuristics, `correspondentPending` when detection absent), conservative dossier matching, suggestion persistence with supersede semantics (REQ-DDIAC-001..002, REQ-DDIAC-004..005)
- [ ] 2.3 Hook classification into the enrichment path (`EnrichmentRunner` + on-demand enrich API) behind `enable_inbound_classification`; suggestions only — zero writes to enriched-object fields (REQ-DDIAC-006)
- [ ] 2.4 Add `ClassificationController` — `GET /api/classification/pending`, `POST /api/classification/{fileId}/confirm`, `POST /api/classification/{fileId}/reject`; confirm applies canonical documentType/correspondent and dossier filing via the dossier folder binding, reject is a no-op close (REQ-DDIAC-003..004)
  - Auth attributes on every method (route-auth gate); files resolved via the requesting user's access only (no-admin-idor gate); confirm/reject record `confirmedBy`/`confirmedAt`.
- [ ] 2.5 Admin settings: `enable_inbound_classification` toggle in the existing DocuDesk admin section (REQ-DDIAC-001)

## 3. Frontend

- [ ] 3.1 Pending-classifications list (type + confidence + correspondent + dossier chips, confirm/correct/reject actions, bulk confirm over visible rows) using standard Cn/Nc components (REQ-DDIAC-003, ADR-012)
- [ ] 3.2 Classification card on the document detail (suggestion state, confirm-with-correction pickers for type and dossier) (REQ-DDIAC-003..004)

## 4. Quality

- [ ] 4.1 PHPUnit for classifier scoring/threshold, correspondent ranking + pending flag, supersede idempotency, no-silent-write guards (enrichment path writes nothing canonical), confirm/correct/reject transitions, dossier match precision, data-minimisation shape — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with OpenRegister: upload an invoice-like PDF → suggestion appears → confirm → documentType set + file in dossier folder.
- [ ] 4.2 Playwright spec `tests/e2e/spec-coverage/inbound-classification.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 4.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 4.4 Docs: `docs/features/inbound-classification.md` with Playwright screenshots (pending list, confirm-with-correction, dossier filing) including the human-oversight posture and the deferred-learning note (ADR-010)
- [ ] 4.5 Validate: `openspec validate inbound-auto-classification --strict` passes; hydra gates green
