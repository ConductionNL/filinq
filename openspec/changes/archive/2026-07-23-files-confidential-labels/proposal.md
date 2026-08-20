---
kind: code
---

# Proposal: files-confidential-labels

## Why

When a file already carries an organisation's confidentiality classification,
DocuDesk should treat that as a sensitivity signal rather than pretend it does
not exist. Nextcloud's `files_confidential` app assigns TSCP/BAILS
confidentiality labels to files (as system tags derived from document content /
metadata markers) and is flagged in the ecosystem map as **INTEGRATE + partial
OVERLAP**: *"Overlaps DocuDesk's PII/sensitivity classification; consume its
labels as an input signal rather than re-detect"* (R4-ecosystem-leaves.md §A,
Confidential files row) — and it is called out as an explicit unspecced leaf
opportunity: *"files_confidential label consumption — ingest existing TSCP/BAILS
confidentiality tags as a PII/sensitivity signal into anonymisation appraisal
(avoid re-classifying already-labelled files)"* (R4 §E.3).

The strategic frame is DocuDesk-as-integrated-compliance-suite over fragmented
NC pieces (R4 §A category-fit: "the pieces are fragmented across LibreSign,
workflow_ocr, files_retention, **files_confidential**"). Consuming an existing
classification is a cheap trust signal for the anonymisation reviewer: a file the
organisation already marked "Confidential/BAILS" deserves visible prominence in
the entity-review context and can be prioritised in batch/folder analysis, with
**no new classification engine** and no policy machinery.

This is deliberately a "could": a small signal-ingestion-and-surfacing change,
availability-guarded because `files_confidential` may be absent.

## What Changes

- **Read the label (availability-guarded)**: a new
  `lib/Service/ConfidentialityLabelService.php` returns a file's confidentiality
  label + normalised level when `files_confidential` is installed and the file
  carries a label; returns `null` (never throws) when the app is absent, the file
  has no label, or the tag manager is unavailable. It mirrors DocuDesk's existing
  optional-dependency pattern — `MetadataService::getObjectService()` guards OR
  consumption with `IAppManager::getInstalledApps()` + lazy container resolution
  (verified `lib/Service/MetadataService.php` L76–84); the label service applies
  the same guard and reads the file's Nextcloud system tags via
  `ISystemTagManager` / `ISystemTagObjectMapper`, matching them against an
  admin-configured confidentiality-label vocabulary.
- **Surface in the document report + entity review context**: the extract/detect
  result already returned by `AnonymizationService::extractAndDetectEntities()`
  (verified: returns `['entities' => …, 'riskLevel' => …]`,
  `lib/Service/AnonymizationService.php` L269) gains
  `confidentialityLabel` / `confidentialityLevel` fields so the reviewer sees the
  pre-existing classification alongside detected entities and risk.
- **Optional analysis-priority suggestion**: an admin config flag
  (`docudesk.confidentiality.prioritise_analysis`, default off) lets a higher
  confidentiality level raise a file's suggested analysis priority in
  batch/folder analysis ordering. This is a *suggestion signal only* — it
  reorders, it does not gate, block, redact or enforce anything.

## Capabilities

### New Capabilities

- `files-confidential-labels`: consume `files_confidential` TSCP/BAILS
  confidentiality labels as a sensitivity signal — surfaced in the document
  report + entity-review context and optionally used to suggest batch/folder
  analysis priority — availability-guarded, with no classification or policy
  engine of DocuDesk's own.

### Modified Capabilities

<!-- none re-owned. AnonymizationService's result shape is DocuDesk's; adding two
     optional fields is additive. No OR spec, no files_confidential internals are
     modified — labels are read through NC's public system-tag API. -->

## Impact

- New `lib/Service/ConfidentialityLabelService.php` — availability-guarded label
  read (`IAppManager` + `ISystemTagManager`/`ISystemTagObjectMapper`), vocabulary
  matching, level normalisation. **The testable unit seam.**
- `lib/Service/AnonymizationService.php` (L269 result assembly): merge
  `confidentialityLabel`/`confidentialityLevel` when the label service returns a
  value (both omitted/`null` otherwise).
- Batch/folder analysis ordering (`FolderBatchService` / `BatchAnonymizeService`
  scheduling): when `docudesk.confidentiality.prioritise_analysis` is on, use the
  normalised level as a tie-breaking priority hint. (No priority ordering exists
  today — verified: `grep priority` in those services is empty — so this is
  additive and off by default.)
- Admin settings: `docudesk.confidentiality.label_vocabulary` (map of
  tag/label name → normalised level) and `docudesk.confidentiality.prioritise_analysis`.
- `lib/Settings/docudesk_register.json`: OPTIONAL — if the document report is
  persisted as an OR object, add nullable `confidentialityLabel`/`Level`
  properties (register version bump); if the report is transient (returned by the
  controller only), no schema change. Design.md D2 records which.
- Consumes (unchanged): NC `ISystemTagManager`/`ISystemTagObjectMapper` (public
  API), `files_confidential` labels (read-only, via tags — no coupling to its
  internals). DocuDesk loads and functions unchanged when `files_confidential` is
  absent.
- **Non-overlap note**: this is a *signal*, distinct from DocuDesk's own PII
  detection (`EntityDetectionService`) and from `entity-publication-policies` /
  prohibition gates — it neither detects nor enforces; it surfaces an existing
  external label. No active change covers it (dd-coverage-baseline.md).
- Evidence: R4 §A (Confidential files INTEGRATE+overlap), §B, §E.3; MetadataService
  optional-dependency pattern (L76–84); AnonymizationService result shape (L269).
