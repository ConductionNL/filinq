# Tasks: pdfua-accessible-output

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Accessible generation backend

- [ ] 1.1 Extend `LibreOfficeHeadlessBackend` with an accessible-export mode (`PDFUACompliance=true` + `UseTaggedPDF=true` filter options) selected via the per-call `$opts` of `PdfConversionService::convertToPdf()`; keep PDF/A when both requested (REQ-DDPUA-001)
  - Fail-closed with structured attempt records when soffice is unavailable; never silent mPDF fallback for `accessible: true`

- [ ] 1.2 Thread the `accessible` option through `DocumentService`/`PdfService` generation paths: Twig/HTML → temp `.html` → LO accessible mode; office templates → filled DOCX → same path (REQ-DDPUA-001)

- [ ] 1.3 Mandatory metadata: `/Lang` resolution (explicit option → template language variant → instance locale, else descriptive failure) and title metadata (option → template name); semantic structure preserved (REQ-DDPUA-002)

## 2. Validation checks

- [ ] 2.1 Add the four accessibility checks to `lib/Service/DocumentValidationService.php` (`pdf-not-tagged`, `pdf-language-missing`, `pdf-title-missing`, `pdfua-identifier-missing` with suppression rule), byte-level heuristics, `category: "accessibility"` on findings (existing findings default `document`), riding the existing profile/severity mechanism, default `warning` (REQ-DDPUA-003)
  - No new parsing dependency; findings never embed content; `aggregate()` unchanged

- [ ] 2.2 Build the PDF fixture set in `tests/sample-documents/` (tagged PDF/UA, untagged, tagged-no-lang, no-title) per design.md Seed Data — generated content only, no personal data

## 3. UI surfaces (ADR-012)

- [ ] 3.1 Validation findings UI: category grouping with an "Accessibility" section and localised messages in `ValidationResultModal`/findings panel; "checks passed" wording — never "PDF/UA certified" (REQ-DDPUA-004)

- [ ] 3.2 Publication-readiness warning: helper deriving "open accessibility findings" from stored findings; warning + link on publication-facing actions; no new gating mechanism (REQ-DDPUA-005)

- [ ] 3.3 Template preview lint: lint pass in `TemplatePreviewService` (missing alt, heading-order jump, unresolvable language, table without headers) returned with the preview; non-blocking checklist panel in the template editor with positional references (REQ-DDPUA-006)

## 4. Quality, i18n, docs

- [ ] 4.1 Unit tests ≥75% on new code: filter-option selection, fail-closed path, lang/title resolution matrix, all four checks against the fixture set incl. suppression rule and blocking escalation, lint permutations; run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`

- [ ] 4.2 Playwright e2e `tests/e2e/spec-coverage/pdfua-accessible-output.spec.ts` (accessible generation → findings grouping → publication warning → template lint); verify on Postgres (8080); test with nldesign theme enabled — new UI itself WCAG AA

- [ ] 4.3 i18n: EN source keys + NL translations for findings messages, warning banner, lint checklist

- [ ] 4.4 Docs in `docs/features/` (accessible output, accessibility checks incl. "presence heuristics, not certified PDF/UA validation" framing, DigiToegankelijk/EN 301 549 context) with Playwright screenshots (ADR-010)

- [ ] 4.5 `openspec validate pdfua-accessible-output --strict` passes; run gates on the touched capability specs

## Quality checklist

- No schema/register change in this change — verify `filinq_register.json` untouched in the diff
- Honesty invariants mutation-tested: untagged output never labelled accessible; `accessible: true` never silently downgraded
- `composer check:strict` green; hydra gates pass; verified end-to-end against OpenRegister on the Postgres dev instance
