---
status: draft
---

# Anonymisation Grondslagen Summary

## Purpose

Defines the rendering side of the grondslag-tracking pipeline: a per-document summary page appended to anonymised PDFs (operator opt-in), and a stand-alone per-dossier summary PDF (on-demand endpoint + auto-regen on dossier `checkedOn` review). Both surfaces use the existing `pdf-generation` Twig + mPDF subsystem to produce PDF/A-3b output. Data is read from `EntityRelation.bases` (OpenRegister); base UUIDs are resolved against DocuDesk's `dossier` register's `base` schema.

## ADDED Requirements

### Requirement: A `GrondslagenSummaryService` MUST exist

The service MUST expose two public methods:

- `appendSummaryToPdf(File $anonymisedFile, int $sourceFileId): File` — renders the per-document summary and appends it to the anonymised PDF. Returns the modified file.
- `renderDossierSummary(int $dossierId): File` — renders the per-dossier summary PDF and writes it to the dossier's destination location. Returns the file.

The service MUST resolve base UUIDs to human-readable names by querying DocuDesk's `dossier` register's `base` schema. It MUST handle unresolvable UUIDs and `null` bases gracefully per the documented label patterns.

#### Scenario: Service resolves base UUIDs to names

- **GIVEN** an `EntityRelation` row with `bases: ["uuid-persoonsgegevens"]` where that UUID resolves to a `base` object with `name: "persoonsgegevens"`
- **WHEN** the per-document summary is rendered for that file
- **THEN** the summary template displays the literal string "persoonsgegevens" for that entity's grondslag

#### Scenario: Unresolvable UUID degrades to a labelled placeholder

- **GIVEN** an `EntityRelation` row with `bases: ["uuid-deleted-base"]` where the UUID does not resolve in the current `base` schema
- **WHEN** the summary is rendered
- **THEN** the displayed string is `⟨grondslag verwijderd: <short-uuid>⟩` (or equivalent localised pattern)
- **AND** the audit log records the unresolved UUID for diagnostics

#### Scenario: Null bases displays a labelled placeholder

- **GIVEN** an `EntityRelation` row with `bases: null` (e.g. anonymised before `entity-relation-grondslagen` landed)
- **WHEN** the summary is rendered
- **THEN** the displayed string is `⟨geen grondslag vastgelegd⟩`

### Requirement: The per-document anonymise endpoint MUST accept an optional `appendBasisSummary` field

The endpoint payload MUST accept a top-level optional `appendBasisSummary` field, boolean, default `false`. When `true`, after the anonymised file has been written to Nextcloud Files (post Change A's PDF conversion if applicable), the controller MUST invoke `GrondslagenSummaryService::appendSummaryToPdf()` (or the separate-PDF path for `outputFormat: "preserve"`).

The flag is per-call only in v1. There is no dossier-level or tenant-level default. (Future evolution may add these; see Open Questions in design.md.)

#### Scenario: Flag default is false

- **GIVEN** an anonymise request payload with no `appendBasisSummary` field
- **WHEN** the request completes
- **THEN** no summary page is appended
- **AND** no separate summary PDF is generated
- **AND** behaviour is identical to pre-change

#### Scenario: Flag true with outputFormat=pdf appends a page to the anonymised PDF

- **GIVEN** an anonymise request with `outputFormat: "pdf"` (or default) and `appendBasisSummary: true`
- **WHEN** the request completes
- **THEN** the resulting file is a PDF/A-3b
- **AND** the file's last page is the rendered summary
- **AND** the summary page lists every entity whose `EntityRelation.anonymized` is true and `bases` is non-null

#### Scenario: Flag true with outputFormat=preserve produces a separate summary PDF

- **GIVEN** an anonymise request with `outputFormat: "preserve"` and `appendBasisSummary: true` and an input DOCX
- **WHEN** the request completes
- **THEN** the anonymised file at `foo_anonymized.docx` is the native-format anonymised file (Change A's preserve path)
- **AND** a separate file at `foo_anonymized_grondslagen.pdf` exists alongside, containing the rendered summary
- **AND** both files are in the same parent folder

### Requirement: The per-document summary MUST list anonymised entities and their bases

The summary template MUST display, for each entity where `EntityRelation.anonymized = true` AND `bases IS NOT NULL`, the rows: `entityText` (or canonical entity name), `entityType` (PERSON/ORGANIZATION), `anonymizedValue` (replacement placeholder), and the resolved `base.name` values for the entity's bases.

The summary MUST NOT display entities released via `acknowledgedOverrides` from the prohibition gate. The summary's scope is "what was redacted under what grondslag", not "all decisions about all detected entities".

The summary MUST include in its header: filename, anonymisation timestamp, operator identifier (Nextcloud user ID), anonymisation tool name ("OpenAnonymiser via OpenRegister"). The summary MUST include in its footer: total count of entities anonymised, count of distinct bases used.

#### Scenario: Summary lists only anonymised + with-bases entities

- **GIVEN** a file with three detected entities — A: anonymised with bases populated; B: anonymised with bases null (legacy); C: detected but released via override (not anonymised)
- **WHEN** the summary is rendered
- **THEN** only entity A appears with its bases
- **AND** entity B appears with the `⟨geen grondslag vastgelegd⟩` placeholder (it WAS anonymised — visible)
- **AND** entity C does NOT appear in the summary at all

Wait — the previous statement contradicts the requirement. Let me restate clearly:

A row appears in the summary IF AND ONLY IF `EntityRelation.anonymized = true`. The `bases` value drives the displayed grondslag (resolved name, placeholder, or `⟨geen grondslag vastgelegd⟩`). Entities not anonymised do NOT appear.

#### Scenario: Restated — summary includes all anonymised entities, regardless of bases value

- **GIVEN** a file with anonymised entities, some with bases populated and some with `bases: null`
- **WHEN** the summary is rendered
- **THEN** every anonymised entity appears
- **AND** entities with bases show resolved `base.name` values
- **AND** entities with `bases: null` show the `⟨geen grondslag vastgelegd⟩` placeholder

#### Scenario: Released-via-override entities are excluded

- **GIVEN** a file with a detected entity released via `acknowledgedOverrides` (not anonymised)
- **WHEN** the summary is rendered
- **THEN** that entity does NOT appear
- **AND** the summary's footer count does NOT include it

#### Scenario: Header includes provenance

- **GIVEN** an anonymisation completed by user `alice` at `2026-05-06T11:00:00Z`
- **WHEN** the summary is rendered
- **THEN** the header shows the original filename, the timestamp, `alice` (or her display name), and "OpenAnonymiser via OpenRegister"

### Requirement: A per-dossier summary endpoint MUST exist

`POST /api/anonymization/dossier/{dossierId}/grondslagen-pdf` MUST trigger generation of a per-dossier summary PDF. The endpoint MUST resolve all files under the dossier's `@self.folder`, query `EntityRelationMapper::findEntitiesForFile` for each, aggregate, and write the rendered PDF to the destination path.

The destination path MUST be `<dossier-folder>/anonymised/grondslagen.pdf` once Change C (`anonymisation-output-folder-layout`) lands, and `<dossier-folder>/grondslagen.pdf` until then. The dossier object's `configuration.grondslagen.fileId` MUST be updated to reference the resulting NC file. `configuration.grondslagen.lastGeneratedAt` MUST be set to the generation timestamp.

#### Scenario: On-demand regen succeeds

- **GIVEN** a dossier with three anonymised files containing entities with bases
- **WHEN** an authenticated user POSTs to `/api/anonymization/dossier/{dossierId}/grondslagen-pdf`
- **THEN** the response is HTTP 200
- **AND** the response body contains the file metadata for the generated PDF
- **AND** the file exists at the destination path
- **AND** `dossier.configuration.grondslagen.fileId` references this file
- **AND** `dossier.configuration.grondslagen.lastGeneratedAt` is set to a timestamp within the last few seconds

#### Scenario: Regen overwrites existing summary in place

- **GIVEN** a dossier whose summary PDF was previously generated
- **WHEN** the regen endpoint is called again
- **THEN** the file at the destination path is overwritten
- **AND** `configuration.grondslagen.fileId` may or may not change (depends on NC file-id semantics on overwrite — accept either)
- **AND** `configuration.grondslagen.lastGeneratedAt` is updated

#### Scenario: Empty dossier produces a near-empty summary

- **GIVEN** a dossier with no anonymised files yet
- **WHEN** the regen endpoint is called
- **THEN** the response is HTTP 200
- **AND** the generated PDF contains the dossier header but empty per-document and per-grondslag tables, with footer counts of zero
- **AND** the file is still saved at the destination

### Requirement: The per-dossier summary MUST be regenerated automatically on `checkedOn` update

When the dossier register's mutation listener detects a write that updates `dossier.checkedOn`, the listener MUST invoke `GrondslagenSummaryService::renderDossierSummary($dossierId)`. The regen MUST run synchronously as part of the dossier update transaction; however, if the regen fails (e.g. rendering throws), the dossier update transaction MUST still succeed (the failure is logged but does not roll back the review).

The auto-regen behaviour MUST honour `dossier.configuration.grondslagen.autoRegenOnReview` (boolean, default `true`). When `false`, the listener does NOT regen — operators must use the on-demand endpoint.

#### Scenario: checkedOn update triggers regen

- **GIVEN** a dossier whose `configuration.grondslagen.autoRegenOnReview` is `true` (or unset)
- **WHEN** an admin updates `checkedOn`
- **THEN** the listener invokes `renderDossierSummary`
- **AND** the new summary is at the destination path
- **AND** `lastGeneratedAt` updates

#### Scenario: autoRegenOnReview false skips auto-regen

- **GIVEN** a dossier with `configuration.grondslagen.autoRegenOnReview: false`
- **WHEN** an admin updates `checkedOn`
- **THEN** the listener does NOT invoke `renderDossierSummary`
- **AND** the existing summary on disk (if any) is left as-is

#### Scenario: Regen failure does not block the review

- **GIVEN** a dossier review that triggers regen
- **AND** the rendering throws (e.g. mPDF runs out of memory)
- **WHEN** the dossier update commits
- **THEN** the dossier `checkedOn` update succeeds
- **AND** an error is logged
- **AND** the existing summary on disk (if any) is unchanged
- **AND** the operator can use the on-demand endpoint to retry

### Requirement: The per-dossier summary MUST aggregate per-document and per-grondslag

The per-dossier summary template MUST display two tables:

1. **Per document**: one row per anonymised file in the dossier, with columns: filename, count of anonymised entities, list of bases used (with per-basis count in parentheses).
2. **Per grondslag**: one row per distinct base used across the dossier, with columns: `base.name`, count of files that used the basis, total count of entities anonymised under the basis.

#### Scenario: Per-document table lists each anonymised file once

- **GIVEN** a dossier with two anonymised files
- **WHEN** the summary is rendered
- **THEN** the per-document table has exactly two rows
- **AND** each row shows the filename, entity count, and bases-with-counts

#### Scenario: Per-grondslag table aggregates correctly across files

- **GIVEN** a dossier where two files used `persoonsgegevens` (5 entities in file A, 3 in file B) and one file used `bedrijfs-fabricage` (2 entities)
- **WHEN** the summary is rendered
- **THEN** the per-grondslag table has two rows: `persoonsgegevens (2 files, 8 entities)` and `bedrijfs-fabricage (1 file, 2 entities)`

### Requirement: Both summary surfaces MUST produce PDF/A-3b

The output of `appendSummaryToPdf` and `renderDossierSummary` MUST be PDF/A-3b. The Twig templates and mPDF configuration MUST follow the same PDF/A-3b path used by `print-preview`.

#### Scenario: Output declares PDF/A-3b conformance

- **WHEN** either summary is rendered
- **THEN** the resulting PDF declares PDF/A-3b conformance in its metadata (verified via `pdfinfo` or equivalent)

### Requirement: The change MUST be additive and non-breaking

Pre-change clients that don't pass `appendBasisSummary` and don't call the dossier-summary endpoint MUST see identical behaviour to before this change.

#### Scenario: Pre-change clients are unaffected

- **GIVEN** a pre-change client that ignores the new flag and never calls the new endpoint
- **WHEN** the client performs anonymisation
- **THEN** behaviour matches pre-change exactly
- **AND** no summary PDFs are generated
- **AND** no dossier `configuration.grondslagen` fields are written
