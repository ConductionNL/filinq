## 1. Output layout helper

- [ ] 1.1 Create `lib/Service/Conversion/OutputLayoutResolver.php` (or extend an existing helper). One method: `resolveBatchDestination(Folder $sourceFolder, string $sourceBaseName, string $extension): string` returning the absolute NC path to the destination. Strips trailing `_anonymized` from the base name (regex `s/_anonymized$//`). Reads the configured subfolder name from `IAppConfig`.
- [ ] 1.2 Constructor takes `IAppConfig` for reading `docudesk.anonymisation.output_subfolder_name` (default `anonymised`).
- [ ] 1.3 Validate the configured subfolder name at read time — single path segment, lowercase letters / digits / hyphen / underscore only, non-empty. If invalid (operator bypassed admin validation somehow), fall back to the default and log a warning.

## 2. Admin settings UI for subfolder name

- [ ] 2.1 Surface `docudesk.anonymisation.output_subfolder_name` in the admin settings UI (or document it as a server-side config key in v1, with admin UI as a follow-up). Use the existing settings pattern for DocuDesk admin keys.
- [ ] 2.2 Implement save-time validation: regex `/^[a-z0-9_-]+$/`, non-empty. Reject invalid input with a clear error message naming the disallowed characters.
- [ ] 2.3 If a tenant changes the subfolder name post-deploy, surface a non-blocking warning: "Existing `<old-name>/` folders are not renamed automatically. Future runs will use `<new-name>/`."

## 3. Batch / folder service integration

- [ ] 3.1 In `lib/Service/BatchAnonymizeService.php`, after each file's anonymisation completes (post Change A's PDF conversion if applicable), invoke `OutputLayoutResolver::resolveBatchDestination()` to compute the target path. Move the file from OR's output location (`<source>/<base>_anonymized.<ext>`) to the target. Use `\OCP\Files\IRootFolder` API for atomic move-or-rename.
- [ ] 3.2 In `lib/Service/FolderBatchService.php`, apply the same post-process logic for the folder-driven flow.
- [ ] 3.3 In `lib/BackgroundJob/FolderExtractionJob.php`, apply the layout when the background job's anonymisation step writes outputs.
- [ ] 3.4 In each of the three call sites, if the move fails: preserve the file at OR's original output path; attach a `warning` to the per-file result; log the failure with details (source path, target path, error message). Do NOT discard the anonymised file.
- [ ] 3.5 Update each service's recorded `anonymizedFilePath` (in batch state, response, etc.) to reflect the new location. When move failed, the path is the legacy location with the warning indicating the situation.

## 4. Source-discovery filter

- [ ] 4.1 In the folder-driven source-discovery code (`FolderBatchService` and `FolderExtractionJob`), exclude files whose base name ends with `_anonymized`. These are legacy outputs from pre-change runs and should not be re-anonymised.
- [ ] 4.2 Confirm the same filter applies to whatever batch source-list assembly path exists (`BatchExtractionService` or equivalent). One filter, two call sites.
- [ ] 4.3 Edge case: a source file that's INTENDED to be anonymised but happens to end in `_anonymized` (very rare). Documentation note: rename the source first, or operators can use the per-file anonymise endpoint which doesn't apply this filter.

## 5. Single-file anonymise unchanged

- [ ] 5.1 Verify by inspection (and test) that the per-document anonymise endpoint and `AnonymizationService::anonymizeDocument` are NOT touched by this change. Single-file outputs continue to land at `<source>/<base>_anonymized.<ext>` in the source folder.
- [ ] 5.2 Add a unit test confirming the single-file flow's output path is unchanged post-this-change (regression guard).

## 6. Grondslagen summary destination

- [ ] 6.1 In `GrondslagenSummaryService::renderDossierSummary` (per Change B), use `OutputLayoutResolver` to compute the summary destination: `<source>/<subfolder>/grondslagen.pdf`.
- [ ] 6.2 If Change B has not yet shipped, this task is a no-op — Change B's apply phase will pick up the helper and use it. If Change B has shipped using the fallback path (`<source>/grondslagen.pdf`), this change's apply MAY migrate existing summary files to the subfolder (one-time move per dossier on next regen). Decide at apply time based on Change B's deployment state.

## 7. Unit tests

- [ ] 7.1 `tests/unit/Service/Conversion/OutputLayoutResolverTest.php` — clean filename → no suffix stripped; legacy `_anonymized` suffix → stripped; `_anonymized` mid-name → preserved; invalid configured subfolder name → fallback to default with warning.
- [ ] 7.2 `tests/unit/Service/BatchAnonymizeServiceTest.php` — extension covering: post-process move places file at expected path; subfolder created on first run; existing subfolder reused on second run; collision overwrites; move failure preserves file at legacy path with warning.
- [ ] 7.3 `tests/unit/Service/FolderBatchServiceTest.php` — same as 7.2 for folder-driven flow.
- [ ] 7.4 Source-discovery filter test — files with `_anonymized` suffix are excluded from the source set.
- [ ] 7.5 Single-file regression test — `AnonymizationService::anonymizeDocument` output remains at `<source>/<base>_anonymized.<ext>` (unchanged).

## 8. Integration tests

- [ ] 8.1 Newman / Postman: batch anonymise on a folder with three files — verify all three redacted outputs land in the configured subfolder with clean filenames.
- [ ] 8.2 Newman: re-run the same batch — verify subfolder is reused and outputs overwritten.
- [ ] 8.3 Newman: configure `output_subfolder_name` to a non-default value (e.g. `redacted`) — verify outputs land in `<source>/redacted/`.
- [ ] 8.4 Newman: source folder containing legacy `_anonymized`-suffixed files — verify they are NOT included in the source set; only true sources are anonymised.
- [ ] 8.5 Manual: configure a permissions-blocked subfolder; trigger a batch — verify the API returns success with `warning` and the file is at the legacy path.

## 9. Frontend coordination

- [ ] 9.1 Notify the frontend team of the layout change. Provide screenshots of expected file structure pre / post change. Coordinate timing of merge.
- [ ] 9.2 Confirm the frontend file-listing UI reads `anonymizedFilePath` from API responses (rather than computing the path client-side). If client-side computation exists, schedule its removal in the frontend's PR.

## 10. Documentation

- [ ] 10.1 Update `docs/features/anonymization.md` (or similar) describing the new layout: subfolder under source, configurable name, single-file flow unchanged. Include a small ASCII diagram showing before/after.
- [ ] 10.2 Document the `docudesk.anonymisation.output_subfolder_name` config key — default, validation rules, change semantics (existing folders are not renamed when the name changes).
- [ ] 10.3 CHANGELOG entry under "Behavior changes": batch / folder / dossier anonymise outputs move to a `<source>/anonymised/` subfolder; single-file flow unchanged. Frontend file-listing components updated. Operators with legacy `_anonymized`-suffixed files in source folders can leave them in place or clean up manually.
- [ ] 10.4 Cross-link from the `anonymisation-grondslagen-summary` doc to the layout doc — the summary's location depends on this change.

## 11. Quality and verification

- [ ] 11.1 Run `composer check:strict` — clean. Fix any pre-existing issues in touched files per the workflow rule.
- [ ] 11.2 Manual smoke against a live stack: trigger a batch anonymise on a multi-file folder; verify the subfolder layout. Configure a non-default subfolder name; verify the change is honoured. Trigger a re-run; verify overwrite-by-filename behaviour.
- [ ] 11.3 Run `openspec validate anonymisation-output-folder-layout` — clean.
