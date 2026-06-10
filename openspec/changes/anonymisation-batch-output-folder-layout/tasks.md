## Tasks

- [x] 1. **Output layout helper** — implemented as `lib/Service/Conversion/OutputLayoutResolver.php`. `resolveBatchDestination()` accepts the source folder path + base name + extension and returns the canonical destination. Trailing `_anonymized` is stripped via `LEGACY_SUFFIX_REGEX`. Constructor injects `IAppConfig` + `LoggerInterface`. `getSubfolderName()` reads `docudesk.anonymisation.output_subfolder_name` (default `anonymised`), validates against `/^[a-z0-9_-]+$/`, and falls back to the default with a warning when invalid. `isLegacyAnonymizedOutput()` exposes the same discrimination for source-discovery filters.
- [~] 2. **Admin settings UI for subfolder name** — DEFERRED with Vue admin surface: the config key + validation regex are in place server-side (resolver validates on read with a warning). Surfacing as an admin-settings field ships alongside the upcoming admin-settings overhaul.
- [~] 3. **BatchAnonymizeService integration** — DEFERRED: the helper is the bedrock contract; the actual move-from-OR-output orchestration in `BatchAnonymizeService` ships as a focused follow-up so the move-failure + warning + path-recording surface (tasks 4-5) can be reviewed as one cohesive PR.
- [~] 4. **Move-failure handling** — DEFERRED with task 3.
- [~] 5. **Path-recording update** — DEFERRED with task 3.
- [~] 6. **Source-discovery filter** — DEFERRED: the `isLegacyAnonymizedOutput()` discriminator is exposed on the helper; the call-site wiring in `BatchExtractionService` ships with task 3.
- [~] 7. **Single-file regression guard** — verified by inspection that the helper is NOT consumed by `AnonymizationService::anonymizeDocument` (the per-document path remains untouched); the regression test ships alongside the integration PR.
- [~] 8. **Grondslagen summary destination handoff** — DEFERRED: `GrondslagenSummaryService::renderDossierSummary` will consume the same helper once the sibling `anonymisation-grondslagen-summary-rendering` change ships its integration PR.
- [x] 9. **Helper unit tests** — `tests/unit/Service/Conversion/OutputLayoutResolverTest.php` covers all four corners (clean filename unchanged, trailing `_anonymized` stripped, mid-name `_anonymized` preserved, invalid configured subfolder falls back with warning) plus the source-discovery `isLegacyAnonymizedOutput` discriminator and trailing-slash path normalisation.
- [~] 10. **BatchAnonymizeService unit tests** — DEFERRED with task 3 (move orchestration ships with its tests).
- [~] 11. **Integration tests — Newman** — DEFERRED with task 3.
- [~] 12. **Frontend coordination + documentation** — DEFERRED with task 3.
- [~] 13. **Quality + verification** — DEFERRED with task 3.
