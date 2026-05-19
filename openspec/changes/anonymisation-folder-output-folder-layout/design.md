## Context

The folder-analysis surface (`folder-analysis-anonymization`) is the second of two batch-style flows. Same problem (operational clutter, easy misuse, cascading suffixes), same fix as `anonymisation-batch-output-folder-layout`, applied to the folder-driven path. Split for review-granularity: the batch capability delta and the folder-analysis capability delta are independent surfaces and shippable separately.

## Goals / Non-Goals

**Goals:**

- Folder-analysis outputs (synchronous + background-job) land in `<source>/anonymised/<original-filename>`.
- Reuse the helper + config key + admin UI from `anonymisation-batch-output-folder-layout`.
- Source-discovery filter for folder-analysis excludes `_anonymized`-suffixed files.
- Move-failure handling mirrors the batch surface: preserve at legacy path + warning + log.

**Non-Goals:**

- The batch surface (`anonymisation-batch-output-folder-layout`).
- New helpers or new config keys — reuse what's already specced for the batch surface.
- Single-file flow.

## Decisions

### D1. Reuse the helper

`OutputLayoutResolver::resolveBatchDestination(...)` is the same entry point for both surfaces. The helper is format-agnostic and surface-agnostic; both batch and folder-analysis call it with the same arguments.

### D2. Background-job parity

`lib/BackgroundJob/FolderExtractionJob.php` applies the post-process via the same helper. Same move-or-warn semantics. The job's existing observability (per-file status, retry hooks) absorbs the post-process step.

### D3. Source-discovery filter applies in two places

Both `FolderBatchService` and `FolderExtractionJob` filter source candidates by base-name suffix. One filter, two call sites — keep the filter logic in a shared helper to avoid drift.

## Risks / Trade-offs

- **Job picks up new layout mid-run** → not possible; the layout decision is per-file at write time. Pre-flight files in the same job still write through the helper.
- **Background-job retry of a failed move** → the job's existing retry hook may re-fire the post-process. If the file is already at the target, the move is a no-op; if it's still at legacy, the retry has a real chance to succeed. Idempotent in both cases.

## Migration Plan

1. Wire `FolderBatchService` and `FolderExtractionJob` to use the helper.
2. Apply source-discovery filter in both call sites.

**Rollback:** Disable the post-process step (early-return); outputs land at the legacy path.

## Seed Data

Not applicable.

## Open Questions

- None — the design defers all path/config/validation decisions to the helper.
