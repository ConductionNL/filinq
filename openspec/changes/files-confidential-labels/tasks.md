# Tasks: files-confidential-labels

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Label read service (the unit seam)

- [x] 1.1 Implement `lib/Service/ConfidentialityLabelService.php` (REQ-DDFCL-001)
  - `getLabelForFile(int $fileId): ?ConfidentialityLabel`; availability-guard on `files_confidential` via `IAppManager::getInstalledApps()` (MetadataService L76–84 pattern); read file system tags via `ISystemTagObjectMapper`/`ISystemTagManager`; match names against `docudesk.confidentiality.label_vocabulary`; return highest-level match or null; catch tag-API exceptions → null (never throw).

## 2. Surface the signal

- [x] 2.1 Add `confidentialityLabel`/`confidentialityLevel` to the entity-review result (REQ-DDFCL-002)
  - Merge into `AnonymizationService::extractAndDetectEntities()` result (L269) when the label service returns non-null; omit/null otherwise; transient report — no OR schema change (design.md D2).
- [x] 2.2 Render a read-only confidentiality chip in the entity-review context (REQ-DDFCL-002)
  - Chip beside the existing risk chip; NL Design tokens (no hardcoded colour); informational only, no action; hidden when no label.

## 3. Optional priority suggestion

- [x] 3.1 Add `docudesk.confidentiality.prioritise_analysis` (default off) as a batch/folder ordering hint (REQ-DDFCL-003)
  - When on, use normalised level as a secondary (tie-breaking) sort key in batch/folder analysis enumeration; unlabelled → level 0; with flag off, ordering is identical to today; never skips/blocks/redacts.

## 4. Config

- [x] 4.1 Admin settings: `label_vocabulary` (name→level map, seeded Public/Internal/Confidential/Secret) + `prioritise_analysis` toggle (REQ-DDFCL-001, REQ-DDFCL-003)
  - `IAppConfig`-backed; explicit auth on any settings route; documented default vocabulary; unmatched tags ignored.

## 5. Quality

- [x] 5.1 PHPUnit unit tests for `ConfidentialityLabelService` (REQ-DDFCL-001) — min 75% on new code
  - Mock tag mappers + app manager + config: label+level for a tagged file; null for untagged; null when `files_confidential` absent; null on tag-API exception; highest-wins on multiple matches; run in the nextcloud:34 container (host PHP too old).
- [x] 5.2 PHPUnit test that the result merge and priority hint are additive (REQ-DDFCL-002, REQ-DDFCL-003)
  - Assert result fields present only when a label resolves; assert batch ordering unchanged when `prioritise_analysis` is off and level-descending as secondary key when on.
- [x] 5.3 i18n EN + NL for the chip label and settings strings (keys English) + docs `docs/features/files-confidential-labels.md`; run `openspec validate files-confidential-labels --strict` (REQ-DDFCL-002)
  - Documents the availability guard, read-only signal (no policy engine), the vocabulary config and the off-by-default priority hint; MCP screenshot of the confidentiality chip DEFERRED — no dev instance with `files_confidential` installed (ADR-010).
