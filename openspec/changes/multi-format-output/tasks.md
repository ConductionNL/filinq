# Tasks: multi-format-output

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 15.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & data model

- [ ] 1.1 Extend `lib/Settings/filinq_register.json`: `generatedDocument.format` enum gains `docx`; new optional `outputs` array (`{format, fileId, status, error?}`); bump the document register `2.3.0` → `2.4.0` (additive). Apply order is pinned: `guided-document-wizard` applies FIRST (`2.2.0` → `2.3.0`), this change SECOND (`2.3.0` → `2.4.0`) — no rebase-on-whichever-lands-second
  - `tests/validate-manifest.js` and register import on boot both pass

## 2. Backend

- [ ] 2.1 Extract `lib/Service/Conversion/HtmlToDocxConverter` from `CorrespondenceService::produceOutput()`'s private docx case (ADR-011 reuse, D3); `CorrespondenceService` delegates to it with behaviour pinned by its existing tests; converter uses the cascade's LibreOffice serialization lock and temp-dir hygiene

- [ ] 2.2 `PdfConversionService::getCapabilities()`: public, non-throwing per-backend `{name, available, supports}` report reusing the `ConversionFailedException` report shape (REQ-DDMFO-005)

- [ ] 2.3 `FormatMatrixService`: instance matrix + per-template matrix (templateType row intersection per design.md D1/D4); reason strings shared with the 503 failure path so matrix and failure never disagree (REQ-DDMFO-002)

- [ ] 2.4 Multi-format pipeline in `DocumentService`: `options.formats` validation (array, dedupe, 400 when combined with `options.format`), render-once to the canonical intermediate (HTML for twig / filled DOCX for office), per-format conversion with per-output `status`/`error` (render failure aborts, conversion failure doesn't), outputs written to the output folder via `OutputLayoutResolver` conventions (REQ-DDMFO-001)

- [ ] 2.5 `docx` on the document path: shared converter for twig templates, filled-DOCX passthrough for office templates; forced-unavailable → 503 with matrix reason; single-`format` requests byte-identical to today (REQ-DDMFO-003)

- [ ] 2.7 `docx` → `html` for office templates: new `lib/Service/Conversion/DocxToHtmlConverter` (`soffice --headless --convert-to html` on the filled DOCX, reusing the cascade soffice serialization lock + temp-dir hygiene + timeout); office `html` gated on LibreOffice availability in the matrix, forced-unavailable → 503 with matrix reason; twig `html` passthrough unchanged (REQ-DDMFO-007, C1 — full format parity)

- [ ] 2.6 Audit logging: one `generatedDocument` per render with `outputs` array for multi-format jobs; scalar `format` = first requested format; single-format generations unchanged (REQ-DDMFO-006)

## 3. Routes & controller

- [ ] 3.1 `GET api/documents/formats` + `GET api/templates/{id}/formats` (Cache-Control: no-store) and the JSON manifest response branch in `DocumentController::generate()`; explicit auth attributes; routes in `appinfo/routes.php`; route-reachability/route-auth gates pass

## 4. Frontend (ADR-012)

- [ ] 4.1 `CorrespondenceIndex.vue`: replace the hardcoded `formats` array with the template matrix — unavailable formats disabled with the reported reason; generate/wizard review step consumes the same endpoint (REQ-DDMFO-004)
  - NL Design tokens only; no new modal, or any new dialog lives in `src/modals/`

## 5. Quality, i18n, docs

- [ ] 5.1 Unit tests (≥75% coverage on new code): formats validation, render-once/convert-N with partial failure, docx passthrough vs converted, office DOCX→HTML converter + office-html-gated-on-LibreOffice (REQ-DDMFO-007), capability report shape, matrix/503 reason equality, register-drift pin for `outputs`; run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/multi-format-output.spec.ts`: generate `["pdf","docx"]` from the seeded template → both files in the output folder, DOCX opens editable (content assertion via download); office template `html` output via DOCX→HTML with resolved data; matrix-driven disabled state in correspondence view; verify on Postgres (8080), test with nldesign theme enabled

- [ ] 5.3 i18n: all new UI strings (disabled-format reasons, manifest labels) with English source keys + NL translations (ADR-005)

- [ ] 5.4 Docs in `docs/features/multi-format-output.md` (formats, fidelity note per design.md risk, inter-municipal DOCX exchange) with Playwright MCP screenshots (ADR-010); `openspec validate multi-format-output --strict` passes

## Quality checklist

- No sed/awk/scripted code edits; Edit tool or full-file writes only
- `composer check:strict` green; hydra gates (spdx, route-auth, spec-coverage, manifest-validation 28/30/51/52) pass
- No silent format downgrade anywhere; matrix and failure reasons come from one source
- End-to-end verified against OpenRegister on the Postgres dev instance, not SQLite
