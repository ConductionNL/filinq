# Tasks: verapdf-validation

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `conformanceReport` schema (`fileId` idempotency key, `flavour`, `compliant`, `failedRuleCount`, `failedRules`, `fontsNotEmbedded`, `validatorVersion`, `validatedAt`, `trigger`) to `lib/Settings/docudesk_register.json` — additive, union-merge only; references and font names only, never content (REQ-DDVPV-004)
- [ ] 1.2 Commit fixture PDFs under `tests/sample-documents/`: genuinely conformant 3b, marker-claiming-but-rule-failing, and a non-embedded-font import wrapped by `convertExistingPdf()` (generated content, no personal data)

## 2. Backend

- [ ] 2.1 New `VeraPdfService`: probe (`isAvailable()`, version) via `docudesk.verapdf.binary_path`/`enabled`, CLI invocation with `docudesk.verapdf.max_seconds` wall-clock budget, JSON result parsing pinned to the read fields, typed `VeraPdfException`; a validator error never yields `compliant` (REQ-DDVPV-001, REQ-DDVPV-002)
- [ ] 2.2 Font-embedding results + remediation-guidance classification (DocuDesk-rendered → regenerate; imported/uploaded → re-convert from source, honest retroactive limitation) (REQ-DDVPV-003)
- [ ] 2.3 `POST /api/validation/conformance/{fileId}` — auth attribute, user-folder resolution (404, no existence disclosure), persists/updates the `conformanceReport` keyed by fileId (REQ-DDVPV-004)
- [ ] 2.4 `archival` check category in `DocumentValidationService`: `pdfa-conformance-failed`, `pdfa-font-not-embedded`, `archival-validator-unavailable`; PDF-only, profile-gated; shipped defaults `off` for archival checks (amended defaults sentence); existing aggregation/422 gate reused (REQ-DDVPV-005)
- [ ] 2.5 `Pdfa3ConversionService` post-output verification: `X-Docudesk-Pdfa3-Verified` header, `trigger: "conversion"` report, report-mode default, `docudesk.pdfa3.strict_verify` → `Pdfa3ConversionException` reason `output_validation_failed`; marker guard untouched (REQ-DDVPV-006)

## 3. Frontend

- [ ] 3.1 `archival` group in the category-grouped findings panel + conformance card on document detail (flavour, verdict, fonts, guidance) (REQ-DDVPV-003, REQ-DDVPV-004, REQ-DDVPV-005)
- [ ] 3.2 Admin-settings validator status row (available/version/disabled) beside the soffice/Tesseract rows (REQ-DDVPV-001)

## 4. Quality

- [ ] 4.1 PHPUnit for probe/degradation, parsing, timeout, guidance classification, report idempotency, archival checks, defaults, and both conversion modes — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with veraPDF installed in the container: conformance check from document detail; convert an unembedded-font PDF → `Verified: false` + guidance; flip strict → 422/exception path.
- [ ] 4.2 Playwright spec `tests/e2e/spec-coverage/verapdf-validation.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 4.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 4.4 Docs: `docs/features/verapdf-validation.md` with Playwright screenshots (admin row, conformance card, archival findings) + install note for the veraPDF binary; update `docs/features/eml-pdf-assembly.md` to point at the integrated check instead of only the manual command (ADR-010)
- [ ] 4.5 Validate: `openspec validate verapdf-validation --type change --strict` passes; hydra gates green

## Quality checklist

- GDPR: reports carry rule references and font names only — no document content (AVG Art. 5(1)(c)); documents never leave the instance.
- No composer/npm dependency added — veraPDF is an admin-installed local binary (design D1 justifies the choice over PDFBox/JHOVE).
- OR services used: ObjectService/AppHost persistence for `conformanceReport`; no other OR surface touched.
- `pdfua-accessible-output` heuristics untouched; category naming aligned (`archival` beside `accessibility`); PDF/UA validation is a named follow-up, not smuggled in.
- Tracking issue: GitHub #315 (primary; mirrors the original Codeberg #182, kept for analysis history).
