# Tasks: image-redaction

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add additive `anonymizationLink` fields `burnedRegionCount` (int) and `imageRedactionPending` (bool) to `lib/Settings/docudesk_register.json` — union-merge only, never hand-pick (REQ-DDIMR-006)
- [ ] 1.2 Seed one scanned Demostad sample letter (printed BSN + handwritten-style signature block) under `tests/sample-documents/` for overlay/burn demos on a clean install

## 2. Cross-app (OpenRegister)

- [ ] 2.1 File the OR issue + PR for the image seam: `detectImage()` (entities + normalised boxes) and `redactImage()` (burned, re-encoded image) on the anonymisation backend surface, Presidio image-redactor as first backend; plus `ENTITY_TYPE_SIGNATURE` and `supportsImages`/`supportsSignatures` capability flags on the backend state (REQ-DDIMR-001, REQ-DDIMR-003)
  - Cross-app dependency; verify against OR HEAD at apply time — do not assume it landed.

## 3. Backend

- [ ] 3.1 New `ImageRedactionService`: image submission (image MIME as-is; scanned-PDF pages via the existing Imagick raster path at OCR DPI, one shared raster per page), box normalisation, container reassembly after burn, irreversibility verification (REQ-DDIMR-001, REQ-DDIMR-004)
  - Verification rejects overlay-style output and any output still containing the original image stream.
- [ ] 3.2 Extend `AnonymizationService::extractAndDetectEntities()` with the image-detection leg after the wave-1 OCR fallback; attach `origin`/`boxes` additively; route image entities through proposals/policy-match/risk unchanged (REQ-DDIMR-007)
- [ ] 3.3 Fail-flagged degradation: `imageDetectionSkipped` reasons on extract, `imageRedactionPending` on anonymise, derived from the OR backend state (`supportsImages`) and seam availability (REQ-DDIMR-002)
- [ ] 3.4 Burn orchestration in the anonymise commit before the `outputFormat` conversion gate, composing with OR text replacement; burned-region text-layer/chunk redaction; honest `burnedRegionCount` from performed burns only (REQ-DDIMR-004, REQ-DDIMR-006, REQ-DDIMR-008)

## 4. Frontend

- [ ] 4.1 Review-workbench region overlays bound to the shared entity decision model (rows carry `origin: "image"` + boxes; overlay state mirrors table decisions; table stays source of truth) (REQ-DDIMR-005)
- [ ] 4.2 `SIGNATURE` entity-type label/badge, signature-detection-unavailable notice, image-not-scanned and pending-redaction warnings; burned-region count in listings (REQ-DDIMR-002, REQ-DDIMR-003, REQ-DDIMR-006)

## 5. Quality

- [ ] 5.1 PHPUnit for submission plumbing, degradation flags, burn verification, count derivation and additive response shape — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with OpenRegister + an image-capable backend: upload scan → regions in workbench → burn → output has opaque region and no recoverable value.
- [ ] 5.2 Playwright spec `tests/e2e/spec-coverage/image-redaction.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 5.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 5.4 Docs: `docs/features/image-redaction.md` with Playwright screenshots (overlays, SIGNATURE badge, degradation warnings, burned output) and the DocuDesk↔OpenRegister image division of labour (ADR-010)
- [ ] 5.5 Validate: `openspec validate image-redaction --type change --strict` passes; hydra gates green

## Quality checklist

- GDPR: no stored field or report contains region pixels or entity values (AVG Art. 5(1)(c)); processing stays 100% local.
- Entity taxonomy documented: PERSON, ORGANIZATION, EMAIL, IBAN, …, SIGNATURE (OR-side constants).
- OR services used: `AnonymisationBackendService` (state + image seam), `TextExtractionService` (wave-1 seam, unchanged), `EntityRelationMapper` (reads), `FileService::anonymizeDocument` (text-side replacement).
- Review-workbench and ocr-trigger-surface specs are referenced, not modified.
