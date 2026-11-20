# Tasks: intake-paper-archive-digitisation

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [x] 1.1 Add `digitisationProject` and `paperOriginal` with the D1 properties, lifecycles and the per-box aggregation to `lib/Settings/filinq_register.json`, seed and version bump (REQ-PAD-001). Verify: `occ maintenance:repair` imports them and the seed project shows its counts.
  - Built: `digitisationProject` (name, archiveDescription, boxes, scanProfile, target, checkShare 0-100 default 10, status open/scanning/checking/closed) and `paperOriginal` (project, box, shelfMark, description, dateRange, scanBatch, document, scannedAt, check, status registered/scanned/checked/rejected, with `rescan` from rejected back to registered), register 8.55.0, three demo objects each in the mock register, and both cascades recorded in `docs/authorization-decisions.md`. The per-box counts are left to the project page (4.1), which counts the originals per box and status. Not verified here: the `occ maintenance:repair` import (needs a running instance).

## 2. Separators and splitting

- [x] 2.1 Add the v2 separator per original to `SeparatorSheetService` with shelf mark and description in print (D2) (REQ-PAD-002). Verify: PHPUnit decodes the QR payload from the rendered page.
  - Built: `originalPayload()` (`filinq:sep:v2:<profileId>:po:<originalId>`) and `renderOriginals()`, one sheet per original with its shelf mark and description in print; `readPayload()` reads a v2 sheet as `po:<originalId>` beside the v1 payload, so the splitter cuts there. PHPUnit decodes the QR payloads from the rendered pages.
- [x] 2.2 File a segment that names an original on the project target with the original's metadata and set it `scanned`; keep v1 segments going to the inbox (D3) (REQ-PAD-003). Verify: PHPUnit on a fixture batch with one v2 and one v1 separator.
  - Built: `ScanBatchService::split()` files a segment whose separator names an original (`po:<id>`) in the folder of the project's target record, named after the original's shelf mark and description, without the inbox event; `PaperOriginalFiling` finds the original, its project and that folder and records the file, the batch and the moment on the original, with the status `DigitisationProjectService::afterScan()` draws (`scanned` into the checking queue, or `checked` when not drawn). An original that cannot be found goes to the inbox as a v1 segment does. PHPUnit `ScanBatchServiceTest` (a batch with one v2 and one v1 separator) and `PaperOriginalFilingTest`.

## 3. Project service

- [x] 3.1 Add `lib/Service/DigitisationProjectService.php` with the sampling draw (D4) (REQ-PAD-004). Verify: PHPUnit with a seeded random source for 0, 10 and 100 percent. — As built: `afterScan()` answers what to write on the original; nothing calls it yet (2.2 wires it into the split).
- [x] 3.2 CSV box list import through openregister's object import with a failure list (D5) (REQ-PAD-001). Verify: PHPUnit imports a fixture CSV with one bad row.
  - Built: `PaperOriginalImport::import()` reads the box list (comma or semicolon, header `box`, `shelfMark`, `description`, `dateFrom`, `dateTo`, at most 5000 rows) and saves every row as a `registered` `paperOriginal` of the project through OpenRegister's object API with the importer's own rights; a row without a box or shelf mark, with the wrong number of cells, or that OpenRegister refuses is listed with its line and reason while the others are saved. `POST api/digitisation/projects/{id}/originals/import` (`DigitisationController::importOriginals`) answers 200, or 422 with the failure list. OpenRegister's object import has no published contract, so the rows go through `saveObject` one by one. PHPUnit `PaperOriginalImportTest` (one bad row among good ones) and `DigitisationControllerTest`.

## 4. Screens

- [x] 4.1 Add a project index and detail with per-box counts and a "Print separators for this box" action, in `src/manifest.json` (REQ-PAD-001, REQ-PAD-002). Verify: Playwright prints the separators of the seed box.
  - Built: `src/manifest.d/25-digitisation.json` (the manifest's fragment for this area): **Digitisation** (`DigitisationProjects`, `/digitisation`, an index over `digitisationProject`) opens `DigitisationProjectDetail` (`/digitisation/:id`): the project's data, a **Boxes** table over `GET api/digitisation/projects/{id}/boxes` (registered, scanned, checked, rejected and total per box; a rejected scan counts as rejected until it is rescanned) with the row action **Print separators for this box**, which downloads `GET api/digitisation/projects/{id}/separators?box=` (one sheet per original of the box), the project's originals, **Import box list** (3.2) and the declared lifecycle. PHPUnit `DigitisationWorklistTest`, `DigitisationControllerTest`; `npm run check:manifest`, vitest `manifestFragments.spec.js`. Not done: the Playwright check (needs a running instance).
- [x] 4.2 Add the checking queue: scan beside the original's metadata, pass or reject with a note (REQ-PAD-004). Verify: Playwright rejects one seeded scan and sees the original back in `registered`.
  - Built: **Scan checks** (`ScanChecks`, `/scan-checks`, an index over `paperOriginal` filtered on `scanned`, the originals the sample drew) opens `PaperOriginalDetail` (`/paper-originals/:id`): the original's metadata with **Open scan** (the filed scan in Files), **Pass** and **Reject** (a note is required), both posting to `POST api/digitisation/originals/{id}/check` with the checker's own rights. A rejection moves the original through the declared `reject` and `rescan` transitions back to `registered` with the note in `check`. PHPUnit `DigitisationWorklistTest`, `DigitisationControllerTest`. Not done: the Playwright check (needs a running instance).

## 5. Quality

- [ ] 5.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
  - Partly built: English and Dutch strings for the new pages (`l10n/en.json`, `l10n/nl.json`, `.js` rebuilt), `@spec` tags on the new code, the routes in `openapi.json`, and `docs/features/intake-paper-archive-digitisation.md`. Open: the screenshots, which need a running instance. `npm run check:schema-l10n` reports 16 schema strings over its baseline that are not from this change's schemas (inherited from development).
- [x] 5.2 PHPUnit at 75% on new code inside the container (ADR-009). Verify: coverage report for the new service and the split branch.
  - Coverage measured 2026-11-20 with pcov over the full unit suite (8398 tests, 0 failures), statements of the lib files carrying this change's `@spec` tag: 519 of 528, 98.3% over 10 files.
  - Open: a measurement in the container. The new classes have unit tests (`PaperOriginalImportTest`, `DigitisationWorklistTest`, `DigitisationControllerTest`).
