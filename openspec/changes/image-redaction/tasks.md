# Tasks: image-redaction

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 18.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [x] 1.1 Add additive `anonymizationLink` fields `burnedRegionCount` (int) and `imageRedactionPending` (bool) to `lib/Settings/filinq_register.json` — union-merge only, never hand-pick (REQ-DDIMR-006)
- [x] 1.2 Seed one scanned Demostad sample letter (printed BSN + handwritten-style signature block) under `tests/sample-documents/` for overlay/burn demos on a clean install

## 2. Cross-app (OpenRegister)

- [ ] 2.1 File the OR issue + PR for the image seam: `detectImage()` (entities + normalised boxes) and `redactImage()` (burned, re-encoded image) on the anonymisation backend surface, Presidio image-redactor as first backend; plus `ENTITY_TYPE_SIGNATURE` and `supportsImages`/`supportsSignatures` capability flags on the backend state (REQ-DDIMR-001, REQ-DDIMR-003)
  - Cross-app dependency; verify against OR HEAD at apply time — do not assume it landed.

## 3. Backend

- [ ] 3.1 New `ImageRedactionService`: image submission (image MIME as-is; scanned-PDF pages via the existing Imagick raster path at OCR DPI, one shared raster per page; born-digital embedded image XObjects extracted via fpdi/tcpdf — D1, v1), box normalisation, container reassembly after burn, irreversibility verification (REQ-DDIMR-001, REQ-DDIMR-004)
  - Verification rejects overlay-style output and any output still containing the original image stream.
  - Undecodable embedded XObject flags `imageDetectionSkipped` reason `embedded_images_unsupported` (per-file, not a whole-document skip).
- [x] 3.2 Extend `AnonymizationService::extractAndDetectEntities()` with the image-detection leg after the wave-1 OCR fallback; attach `origin`/`boxes` additively; route image entities through proposals/policy-match/risk unchanged (REQ-DDIMR-007)
- [x] 3.3 Fail-flagged degradation: `imageDetectionSkipped` reasons on extract, `imageRedactionPending` on anonymise, derived from the OR backend state (`supportsImages`) and seam availability (REQ-DDIMR-002)
- [ ] 3.4 Burn orchestration in the anonymise commit before the `outputFormat` conversion gate, composing with OR text replacement; burned-region text-layer/chunk redaction; honest `burnedRegionCount` from performed burns only (REQ-DDIMR-004, REQ-DDIMR-006, REQ-DDIMR-008)
  - Built so far (26 Nov 2026): OpenRegister main now burns the decided regions of a DOCX, ODT or PDF inside its own anonymise call (`DocumentProcessingHandler::burnEmbeddedImages()`, REQ-AIS-004), before filinq's `outputFormat` conversion, and refuses the whole run when one cannot be burned. Filinq reads that instead of orchestrating a burn: `ImageSeamGateway::burnsInsideAnonymise()` asks the seam's `burnsEmbeddedImages()` (absent on older OpenRegister, which keeps `imageRedactionPending`), and `ImageRedactionService::anonymizeFields()` then reports the decided regions as `burnedRegionCount` with `imageRedactionPending: false`; a document without decided regions stays pending. Pinned by `ImageRedactionServiceTest::testAPdfBurnedByTheAnonymiseCallReportsItsRegions`, `::testAnImageBearingPdfWithoutDecidedRegionsStaysPending`, `ImageSeamGatewayTest::testTheAnonymiseCallBurnsTheImagesInsideAPdf`, `::testAnOlderOrFailingSeamDoesNotBurnInsideTheAnonymise`. Open: the burned-region text-layer/chunk redaction and the born-digital page case (openregister#11367), and a live check on an OpenRegister release that carries the seam; not ticked.

## 4. Frontend

- [x] 4.1 Review-workbench region overlays bound to the shared entity decision model (rows carry `origin: "image"` + boxes; overlay state mirrors table decisions; table stays source of truth) (REQ-DDIMR-005)
- [x] 4.2 `SIGNATURE` entity-type label/badge, signature-detection-unavailable notice, image-not-scanned and pending-redaction warnings; burned-region count in listings (REQ-DDIMR-002, REQ-DDIMR-003, REQ-DDIMR-006)
  - Built: the `SIGNATURE` type with its label (en, nl), the signature-detection-unavailable notice, the image-not-scanned and pending-redaction warnings (`imageExtractionWarning`, `imageRedactionWarning`). The burned-region count shows beside the replacement count in the file's anonymisation result, both right after a run and when an anonymised file is opened again (`burnedRegionLine`, read from the run's `anonymizationLink`); a run that burned nothing shows no line. Pinned by `imageRedaction.spec.js` (`burnedRegionLine`) and the `anonymizedEntry` golden case `link with burned image regions`.

## 5. Quality

- [ ] 5.1 PHPUnit for submission plumbing, degradation flags, burn verification, count derivation and additive response shape — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with OpenRegister + an image-capable backend: upload scan → regions in workbench → burn → output has opaque region and no recoverable value.
  - Coverage measured (26 Nov 2026, pcov over the full unit suite on development 6f987cc): 1108 of 1167 statements, 94.9%, across the 13 lib files that carry this change's `@spec` tag; lowest `DocumentAnonymizeRunner` at 82.6%. The 75% figure is met; the burn-verification tests wait on task 3.1 and the born-digital page burn on openregister#11367.
- [x] 5.2 Playwright spec `tests/e2e/spec-coverage/image-redaction.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 5.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 5.4 Docs: `docs/features/image-redaction.md` with Playwright screenshots (overlays, SIGNATURE badge, degradation warnings, burned output) and the Filinq↔OpenRegister image division of labour (ADR-010)
- [ ] 5.5 Validate: `openspec validate image-redaction --type change --strict` passes; hydra gates green

## Quality checklist

- GDPR: no stored field or report contains region pixels or entity values (AVG Art. 5(1)(c)); processing stays 100% local.
- Entity taxonomy documented: PERSON, ORGANIZATION, EMAIL, IBAN, …, SIGNATURE (OR-side constants).
- OR services used: `AnonymisationBackendService` (state + image seam), `TextExtractionService` (wave-1 seam, unchanged), `EntityRelationMapper` (reads), `FileService::anonymizeDocument` (text-side replacement).
- Review-workbench and ocr-trigger-surface specs are referenced, not modified.

## 6. Amendment 2026-10-05: Woo row 4.24

Build after `openregister/anonymisation-image-seam` merges. Task 2.1 is met by that change; task 3.1
delegates the burn and the reassembly to it (decision D2). Every test named here fails on
`development` today: no scope decision exists. Acceptance lines are plain bullets so the checkbox cap
of 20 holds.

- [x] 6.1 The scope decision in the model (REQ-DDIMR-009). Files: `lib/Settings/filinq_register.json` (`anonymizationLink.withheld`, `withheldBases`; a `scopeDecisions` list on `documentReview`; register version bump), `lib/Service/Review/ScopeRedactionService.php` (validate and store a decision).
  - GIVEN a 4-page document WHEN pages 3 to 6 are entered THEN refused naming the page count.
  - GIVEN a decision without a ground WHEN saved THEN refused.
  - Test: PHPUnit `ScopeRedactionServiceTest::testARangeBeyondTheLastPageIsRefused`, `::testAScopeNeedsAGround`. Show `testARangeBeyondTheLastPageIsRefused` failing on `development` first; paste the line in the PR body.
- [ ] 6.2 Commit through the seam (REQ-DDIMR-009). Files: `lib/Service/Review/ScopeRedactionService.php`, the anonymise commit in `AnonymizationService` and the batch path.
  - GIVEN pages 3 to 5 WHEN committed THEN three full-page regions reach OpenRegister's seam and the output verification of REQ-DDIMR-004 runs.
  - GIVEN a whole-document decision WHEN committed THEN no output file and `withheld: true` on the link; `PublicationReadiness` lists it as not for publication.
  - GIVEN no seam WHEN a page scope is committed THEN refused, nothing written.
  - Precondition, stop if unmet: read OpenRegister's seam on `development` and name in the PR body the method that persists a region and the one that burns it. If a full-page region on a page with no image cannot be burned as a raster there, open an OpenRegister issue and stop; do not write a burner in filinq.
  - Test: PHPUnit `ScopeRedactionServiceTest::testPagesBecomeFullPageRegions`, `::testAWholeDocumentIsWithheldWithoutOutput`, `::testPageScopesNeedTheSeam`; `PublicationReadinessTest::testAWithheldDocumentIsNotForPublication`. Double OpenRegister's seam with `environmentAwareDouble` from its real signature.
  - Built (26 Nov 2026): the scope commit (`ScopeRedactionService::commit()` through `ImageSeamGateway::burn()` onto OpenRegister's `ImageRedactionService::redactImage()`, which persists nothing itself: the regions are passed as `ImageRegion` values), the four tests above, and the withhold on the usual commit: `AnonymizationService::runAnonymize()` asks the image leg (`Review\ImageRedactionService::withheldOutcome()`), which finds a stored `document` decision, records the withhold and writes nothing, so the single anonymise, the batch and the folder job all honour it (`AnonymizationServiceWithholdTest`, `BatchAnonymizeServiceTest::testAWithheldDocumentIsAFinishedEntryWithoutOutput`).
  - STOPPED on the precondition for the page half: OpenRegister's seam burns a PDF page only through its one page image (`ImageBurner::burnPdfPages()`), so a full-page region on a born-digital page cannot be burned as a raster there. Filed ConductionNL/openregister#11367; no burner written in filinq. Open until it lands: born-digital page scopes, and burning page scopes inside the usual anonymise run.
- [ ] 6.3 One action in the workbench (REQ-DDIMR-009). Files: the review workbench toolbar, a dialog in its own file under `src/dialogs/` (scope, pages, grounds through the grounds picker), `l10n/en.json`, `l10n/nl.json`.
  - GIVEN a 7-page PDF WHEN the reviewer redacts pages 3 to 5 THEN one row appears in the decision table and the preview marks the three pages.
  - Test: Playwright `tests/e2e/spec-coverage/image-redaction.spec.ts` (redact pages 3 to 5, commit, open the output and check pages 3 to 5 are fill and carry the notice).
- [x] 6.4 Docs. Files: `docs/features/image-redaction.md` (the three scopes, why a withheld document has no output, and that OpenRegister does the pixel work).
  - Test: the docs build.
  - Done (26 Nov 2026): the page covers the three scopes, why a withheld document has no output (also on the usual and batch commit), and the division of labour with OpenRegister, including what its seam cannot burn yet (openregister#11367). `npm run build` in `docs/` passes; it needed two pre-existing `{vendor}` MDX expressions escaped in `authorization-decisions.md` and `features/zgw-document-bridge.md`.

Verification for section 6 (plain bullets on purpose). The building agent follows `openspec/woo-build-rules.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate image-redaction --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green. Row 4.24 is `production` only once a store release carries it.
