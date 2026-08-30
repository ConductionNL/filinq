# Tasks: document-sanitization

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `sanitizationRecord` schema (`fileId`, `sanitizedFileId`, `trigger`, `engine`, `report` category counts, `sanitizedAt`, `sanitizedBy`) to `lib/Settings/filinq_register.json` — additive, union-merge only; counts only, never removed content (REQ-DDSAN-003)
- [ ] 1.2 Seed one DOCX sample with comments + track changes + author metadata ("Concept besluit Demostad", tests/sample-documents) so the report panel demos non-zero counts

## 2. Cross-app (OpenRegister)

- [ ] 2.1 File the OR issue + PR for the standalone PDF sanitizer in the existing `Sanitizer` family: /Info + XMP scope of `PdfMetadataSanitizer`, annotations, embedded files (D2 exception: preserve only PDF/A-3 attachments declared `/AFRelationship Source|Data` plus their `/AF` association; strip all others), JavaScript/OpenAction/AA, full re-save; public per-file entry point like `OfficeDocumentSanitizer::sanitize()` (REQ-DDSAN-002)
  - Cross-app dependency; verify against OR HEAD at apply time — do not assume it landed.

## 3. Backend

- [ ] 3.1 New `DocumentSanitizationService`: MIME routing (office → OR `OfficeDocumentSanitizer`, PDF → OR seam), derivative naming beside the source, encrypted 422 fail-closed, `sanitizationSkipped`/`pdf_sanitizer_unavailable` degradation, `sanitizationRecord` persistence with the OR-report drift pin (REQ-DDSAN-001, REQ-DDSAN-002, REQ-DDSAN-003)
- [ ] 3.2 New `SanitizationController` + route `POST /api/sanitization/{fileId}` — auth attribute, user-folder resolution (404, no existence disclosure) (REQ-DDSAN-001)
- [ ] 3.3 Persist + surface the anonymise-run office report (`getLastSanitizationReport()` consumption, `trigger: "anonymisation"`, no fabrication for non-sanitisable formats) (REQ-DDSAN-006)
- [ ] 3.4 Additive `sanitize` flag on single + batch anonymise: final-artifact pass after conversion/summary append, tenant default `filinq.sanitization.default`, warning-not-error failure contract (REQ-DDSAN-007)
- [ ] 3.5 Sanitized signal + ordering checks: signal from `sanitizationRecord.sanitizedFileId`, hand-off warning wiring, sealed-artifact warning on sanitize, sanitized-state surfacing on the seal action (REQ-DDSAN-004, REQ-DDSAN-005)

## 4. Frontend

- [ ] 4.1 "Sanitize" action in MyDocuments/file viewer + sanitization report panel as its own modal under `src/modals/` (category counts per run); degradation and encrypted errors surfaced (REQ-DDSAN-001, REQ-DDSAN-003)
- [ ] 4.2 Hand-off warning, seal-order warning and sanitized badge on publication-facing surfaces (REQ-DDSAN-004, REQ-DDSAN-005)

## 5. Quality

- [ ] 5.1 PHPUnit for routing, degradation, record persistence, drift pin, final-artifact pass placement and failure contract — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with OpenRegister: sanitize DOCX → derivative + report; anonymise DOCX → run report visible; `sanitize: true` on pdf output → clean final PDF.
- [ ] 5.2 Playwright spec `tests/e2e/spec-coverage/document-sanitization.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 5.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 5.4 Docs: `docs/features/document-sanitization.md` with Playwright screenshots (sanitize action, report panel, hand-off warning, seal ordering) and the sanitize→seal publication recipe (ADR-010)
- [ ] 5.5 Validate: `openspec validate document-sanitization --type change --strict` passes; hydra gates green (orphaned-capability gate should now pass for `getLastSanitizationReport`)

## Quality checklist

- GDPR: records carry category counts only — no removed content, author names or comment text (AVG Art. 5(1)(c)); record framing = Art. 5(2) accountability evidence.
- OR services used: `OfficeDocumentSanitizer` (office pass), the new PDF sanitizer seam, `FileService`/`DocumentProcessingHandler::getLastSanitizationReport()` (run report), ObjectService/AppHost for `sanitizationRecord`.
- `woo-publicatie-pipeline` and `document-waarmerk-certification` specs are referenced, not modified; no hard publication gate added here.
