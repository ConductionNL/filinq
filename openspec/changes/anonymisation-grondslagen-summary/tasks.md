## 1. GrondslagenSummaryService skeleton

- [ ] 1.1 Create `lib/Service/GrondslagenSummaryService.php`. Constructor injects `PdfService` (for Twig + mPDF rendering), the entity-relation mapper from OR (`OCA\OpenRegister\Db\EntityRelationMapper`), the OR object service for resolving `base` schema records, the dossier mapper, the file service, and the logger.
- [ ] 1.2 Define two public methods: `appendSummaryToPdf(File $anonymisedFile, int $sourceFileId): File` and `renderDossierSummary(int $dossierId): File`.
- [ ] 1.3 Define a private helper `resolveBaseLabels(array $baseUuids): array` returning an associative array `[uuid => name]` with placeholder strings (`⟨grondslag verwijderd: <short-uuid>⟩`) for unresolved UUIDs.
- [ ] 1.4 Define a private helper `loadAnonymisedEntitiesForFile(int $fileId): array` that calls `EntityRelationMapper::findEntitiesForFile($fileId)`, filters to `anonymized = true`, and returns rows with bases-resolved labels attached.

## 2. Twig templates

- [ ] 2.1 Create `lib/Resources/templates/grondslagen/summary_per_doc.twig`. Layout: header (filename, anonymised-at, operator, tool); table per anonymised entity (entityText, entityType, anonymizedValue, bases as comma-joined names); footer (entity count, distinct bases count). NL labels.
- [ ] 2.2 Create `lib/Resources/templates/grondslagen/summary_per_dossier.twig`. Layout: header (dossier name, description, checkedOn, generatedAt); per-document table; per-grondslag table; footer with aggregate totals. NL labels.
- [ ] 2.3 Verify the templates render through `PdfService`'s sandbox (no blocked Twig directives). If the sandbox blocks anything needed, expand the safe-tag list narrowly.
- [ ] 2.4 Configure mPDF for PDF/A-3b output for both templates (mirroring `print-preview`'s PDF/A-3b config).

## 3. Per-document append flow

- [ ] 3.1 Implement `appendSummaryToPdf` for the `outputFormat: "pdf"` case: render summary via Twig + PdfService → temp PDF; open the anonymised PDF via mPDF + FPDI; iterate source pages with `setSourceFile` + `importPage` + `useTemplate`; append a new page with the summary content; output to a temp file; replace the anonymised file in NC atomically.
- [ ] 3.2 Implement the `outputFormat: "preserve"` separate-PDF path: render summary → save as `<original-base>_anonymized_grondslagen.pdf` in the same folder as the anonymised file. Return both file references.
- [ ] 3.3 Add error handling per spec: if rendering or append throws, the anonymised file is preserved as-is; the controller returns 200 with a `warning` field. The summary file is not partially written.
- [ ] 3.4 Verify that mPDF's `setSourceFile` / `importPage` / `useTemplate` paths work against PDF/A-3b inputs (the common case post Change A). Spike during apply if needed.

## 4. AnonymizationController integration

- [ ] 4.1 Update `lib/Controller/AnonymizationController::anonymize` to read optional top-level `appendBasisSummary` (boolean, default false). Validate type; reject malformed input with 400.
- [ ] 4.2 In `lib/Service/AnonymizationService::anonymizeDocument`, after the anonymised file has been written (post Change A's conversion), if `appendBasisSummary` is true: call `GrondslagenSummaryService::appendSummaryToPdf()` (or the separate-PDF path).
- [ ] 4.3 If the summary call throws: log the error, attach a structured `warning` to the response, return HTTP 200 with the anonymised file metadata. The anonymised file is preserved as-is.
- [ ] 4.4 Update `lib/Service/BatchAnonymizeService` to honour the flag per file in the batch. Per-file warnings if individual summaries fail; batch overall succeeds.
- [ ] 4.5 Update `lib/Controller/BatchAnonymizationController` similarly.

## 5. Per-dossier summary endpoint

- [ ] 5.1 Add `lib/Controller/DossierController.php` (or extend an existing dossier controller) with route `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf`. Authenticated; accepts no body.
- [ ] 5.2 Resolve the dossier object via OR. Read its `@self.folder` for the destination location. Default destination: `<dossier-folder>/anonymised/grondslagen.pdf` (per Change C); fallback if Change C hasn't shipped yet: `<dossier-folder>/grondslagen.pdf`.
- [ ] 5.3 Walk all files under the dossier's folder. For each, call `loadAnonymisedEntitiesForFile`. Aggregate per-document and per-grondslag.
- [ ] 5.4 Render `summary_per_dossier.twig` via PdfService. Save to the destination path (overwrite if exists). Update the dossier object's `configuration.grondslagen.fileId` and `configuration.grondslagen.lastGeneratedAt` via OR.
- [ ] 5.5 Return HTTP 200 with file metadata for the generated PDF.
- [ ] 5.6 Empty-dossier case: still generate a near-empty PDF (header + empty tables + zero totals); save and return.

## 6. Auto-regen on `checkedOn` update

- [ ] 6.1 Subscribe a listener to OpenRegister's object-changed events for the `dossier` register. On a write that updates `dossier.checkedOn`, check `dossier.configuration.grondslagen.autoRegenOnReview` (default true); if true, invoke `GrondslagenSummaryService::renderDossierSummary($dossierId)` synchronously within the same transaction.
- [ ] 6.2 If the regen throws, log the error but do NOT roll back the dossier update. The review must succeed even if summary rendering fails.
- [ ] 6.3 Confirm by inspection: a `checkedOn` update from a normal review flow triggers exactly one regen (no duplicate or runaway invocations).

## 7. Dossier `configuration.grondslagen` fields

- [ ] 7.1 The dossier object's `configuration` JSON gains the keys: `grondslagen.fileId` (int|null), `grondslagen.lastGeneratedAt` (ISO-8601 string|null), `grondslagen.autoRegenOnReview` (bool, default true). No schema migration required (`configuration` is already a free-form JSON field per `add-dossier-schema`).
- [ ] 7.2 The on-demand and auto-regen paths both write these fields after a successful regen.
- [ ] 7.3 Document the convention in the dossier-register documentation (extend `docs/features/dossier-register.md` per `add-dossier-schema` task 8.1 once that doc lands).

## 8. Unit tests

- [ ] 8.1 `tests/unit/Service/GrondslagenSummaryServiceTest.php` — base resolution (resolved, unresolved, null bases); per-doc append with mock anonymised PDF; per-dossier render with multi-file aggregation; empty-dossier case; rendering failure surfaces as exception (caller decides what to do).
- [ ] 8.2 `tests/unit/Service/AnonymizationServiceTest.php` extension — flag default false skips summary; flag true with PDF output appends; flag true with preserve mode produces separate file; summary failure preserves anonymised file and emits warning.
- [ ] 8.3 `tests/unit/Controller/DossierControllerTest.php` — endpoint returns file metadata; file is written at expected path; configuration fields updated; empty dossier handled.
- [ ] 8.4 Listener test for `checkedOn` update — auto-regen fires when `autoRegenOnReview` is true; skipped when false; failure logged but dossier update succeeds.

## 9. Integration tests

- [ ] 9.1 Newman / Postman: per-document anonymise with `appendBasisSummary: true` and PDF output — verify the resulting file has an extra page with the rendered summary content (assertion on PDF text or page count).
- [ ] 9.2 Newman: per-document anonymise with `outputFormat: "preserve"` and `appendBasisSummary: true` — verify two files exist in the target folder (the anonymised native-format file and a separate `_grondslagen.pdf`).
- [ ] 9.3 Newman: `POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf` — verify response shape, file written at destination path, dossier `configuration.grondslagen` updated.
- [ ] 9.4 Newman: Update a dossier's `checkedOn` via PUT — verify the auto-regen fires (re-fetch the dossier; `lastGeneratedAt` is updated; the file on disk is overwritten).
- [ ] 9.5 Newman: Set `autoRegenOnReview: false` on a dossier; update `checkedOn` — verify NO regen happens (`lastGeneratedAt` unchanged from previous value).

## 10. Documentation

- [ ] 10.1 Add `docs/features/grondslagen-summary.md` describing both surfaces, the opt-in mechanics, the destination path conventions (with the Change C dependency note), the auto-regen behaviour, and the `configuration.grondslagen.*` dossier fields.
- [ ] 10.2 CHANGELOG entry under "Added": `appendBasisSummary` flag on the per-document anonymise endpoint; new per-dossier summary endpoint; auto-regen on dossier `checkedOn` review.
- [ ] 10.3 CHANGELOG entry noting cross-change deps: requires `entity-relation-grondslagen` (OR) and `add-dossier-schema` to be applied before this change is functionally useful; soft-deps on Change A (`anonymise-output-as-pdf-by-default`) and Change C (`anonymisation-output-folder-layout`) for cleanest behaviour.
- [ ] 10.4 Note the NL-only template limitation; EN follows `register-i18n`.

## 11. Quality and verification

- [ ] 11.1 Run `composer check:strict` — clean. Fix any pre-existing issues in touched files per the workflow rule.
- [ ] 11.2 Manual smoke against a live stack: configure a dossier; anonymise files within it with `appendBasisSummary: true`; verify the appended summary page; trigger the per-dossier endpoint; verify the dossier-folder summary PDF exists and has the right content; update `checkedOn` and verify auto-regen.
- [ ] 11.3 Run `openspec validate anonymisation-grondslagen-summary` — clean.
- [ ] 11.4 Manual PDF/A-3b conformance check on both summary outputs (via `pdfinfo` or `verapdf`).
