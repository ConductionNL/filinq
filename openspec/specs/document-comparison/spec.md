# document-comparison Specification

## Purpose
TBD - created by archiving change document-comparison. Update Purpose after archive.
## Requirements
### Requirement: The comparison endpoint MUST accept two subjects resolved from NC Files without re-storing content

`POST /api/comparison/compare` MUST accept a JSON payload with `left` and `right` subjects, each of shape `{fileId: int, versionTimestamp?: int}`. A subject without `versionTimestamp` resolves to the file's current content; with `versionTimestamp`, to that version via the `files_versions` integration. Each subject MUST be resolved through the requesting user's folder (`IRootFolder::getUserFolder()`); a file that cannot be resolved for this user MUST yield HTTP 404 (no distinction between "does not exist" and "no access"). The service MUST NOT copy, persist, or index either subject's content.

@e2e exclude Backend subject resolution, version lookup, IDOR-safe 404, and files_versions-disabled 422 — controller/service behaviour with no isolated UI assertion. Covered by PHPUnit (DocumentComparisonServiceTest) and Newman.

#### Scenario: Compare two versions of the same file

- **GIVEN** a file with id 42 that has a prior version at timestamp T1
- **AND** the requesting user can read the file
- **WHEN** the endpoint is called with `left: {fileId: 42, versionTimestamp: T1}`, `right: {fileId: 42}`
- **THEN** the response is HTTP 200 with a structured diff of version T1 against the current content

#### Scenario: Compare two distinct files

- **GIVEN** two files with ids 42 and 77, both readable by the requesting user
- **WHEN** the endpoint is called with `left: {fileId: 42}`, `right: {fileId: 77}`
- **THEN** the response is HTTP 200 with a structured diff of file 42's content against file 77's content

#### Scenario: Inaccessible subject yields 404

- **GIVEN** file 99 exists but is not readable by the requesting user
- **WHEN** the endpoint is called with `left: {fileId: 42}`, `right: {fileId: 99}`
- **THEN** the response is HTTP 404
- **AND** the body does not reveal whether file 99 exists

#### Scenario: Unknown version yields 404

- **GIVEN** file 42 has no version at timestamp T9
- **WHEN** the endpoint is called with a subject `{fileId: 42, versionTimestamp: T9}`
- **THEN** the response is HTTP 404

#### Scenario: files_versions disabled

- **GIVEN** the `files_versions` app is disabled on the instance
- **WHEN** the endpoint is called with a subject carrying `versionTimestamp`
- **THEN** the response is HTTP 422 with a machine-readable reason `versions-unavailable`
- **AND** comparisons of current contents (no `versionTimestamp`) remain unaffected

### Requirement: The diff MUST be a server-computed word-level structured diff

The service MUST extract text from both subjects via the existing `DocumentTextExtractor`, normalise whitespace, and compute a word-level diff returned as ordered hunks. Each hunk MUST have shape `{type: "unchanged"|"added"|"removed"|"changed", left: {offset, length}|null, right: {offset, length}|null, leftText?: string, rightText?: string}`. Extracted text per subject is capped at app config `docudesk.comparison.max_text_bytes` (default 5242880); exceeding the cap MUST yield HTTP 413. A subject whose format the extractor does not support MUST yield HTTP 415 naming the unsupported subject. When the two subjects have different source formats, the response MUST set `crossFormat: true` so the UI can warn about layout-derived noise.

@e2e exclude LCS diff engine, whitespace normalisation, hunk shape, size cap (413), unsupported-format (415) and cross-format flagging — pure server computation. Covered by PHPUnit (DocumentComparisonServiceTest).

#### Scenario: Structured hunks for a changed document

- **GIVEN** two readable subjects whose extracted texts differ in one sentence
- **WHEN** the comparison runs
- **THEN** the response contains `hunks[]` with at least one `changed`/`added`/`removed` hunk covering that sentence
- **AND** every hunk carries left/right offsets per the contract shape

#### Scenario: Identical documents produce no change hunks

- **GIVEN** two subjects with identical extracted text
- **WHEN** the comparison runs
- **THEN** the response is HTTP 200
- **AND** `hunks[]` contains only `unchanged` hunks
- **AND** the response's `summary.changedHunks` equals 0

#### Scenario: Unsupported format yields 415

- **GIVEN** a right subject whose mime type the `DocumentTextExtractor` cannot handle
- **WHEN** the endpoint is called
- **THEN** the response is HTTP 415
- **AND** the body names which subject (`right`) is unsupported

#### Scenario: Oversize subject yields 413

- **GIVEN** a subject whose extracted text exceeds `docudesk.comparison.max_text_bytes`
- **WHEN** the endpoint is called
- **THEN** the response is HTTP 413

#### Scenario: Cross-format pair is flagged

- **GIVEN** a DOCX left subject and a PDF right subject
- **WHEN** the comparison runs
- **THEN** the response sets `crossFormat: true`

### Requirement: Original-vs-anonymised pairs MUST be annotated with redaction metadata

When the right subject is the anonymised output of the left subject (linked via the anonymisation result/report for the source file), the service MUST annotate change hunks whose inserted text maps to an anonymisation replacement: first by replacement-key mapping (REQ-ANON-06 keys / placeholders recorded on the anonymisation result), falling back to matching removed-span text against `Entity` canonical values for the source file. An annotated hunk carries `redaction: {entityId: int, entityType: string, matchedBy: "key"|"value"}`. OR mappers MUST be resolved lazily (REQ-ANON-05); when OpenRegister is unavailable or the pair is unrelated, the comparison MUST still succeed as a plain diff with no `redaction` annotations. The annotation pass MUST NOT re-run entity detection.

@e2e exclude Key-based and value-based redaction annotation, lazy OR resolution, and graceful plain-diff degradation — service annotation logic against OR EntityRelation rows, no isolated UI assertion. Covered by PHPUnit (DocumentComparisonServiceTest).

#### Scenario: Original vs anonymised output is annotated

- **GIVEN** file 42 was anonymised producing output file 88, with `EntityRelation` rows and a replacement-key mapping recorded for file 42
- **WHEN** the endpoint compares `left: {fileId: 42}` against `right: {fileId: 88}`
- **THEN** each change hunk corresponding to a redacted entity carries `redaction.entityId`, `redaction.entityType`, and `matchedBy: "key"`

#### Scenario: Legacy output without key mapping falls back to value matching

- **GIVEN** an anonymised output produced before replacement-key mappings were recorded
- **AND** `Entity` rows with canonical values exist for the source file
- **WHEN** the pair is compared
- **THEN** hunks whose removed text matches an entity canonical value carry `redaction` with `matchedBy: "value"`

#### Scenario: Unrelated pair carries no redaction annotations

- **GIVEN** two files with no anonymisation link between them
- **WHEN** the pair is compared
- **THEN** the response contains hunks but no `redaction` annotations
- **AND** the response is HTTP 200

#### Scenario: OpenRegister unavailable degrades to plain diff

- **GIVEN** OpenRegister services cannot be resolved
- **WHEN** an original/anonymised pair is compared
- **THEN** the comparison succeeds as a plain structured diff
- **AND** the response sets `redactionAnnotation: "unavailable"`

### Requirement: Original-vs-anonymised responses MUST carry a redaction-completeness signal

For an annotated original/anonymised pair, the response MUST include `unredactedEntities[]`: every `EntityRelation` row for the source file that was part of the anonymise set (not flagged `skipAnonymization`) and matched zero change hunks. Entries are reported as `{entityId, entityName}` using the OR `Entity` canonical name — never the literal detected document text. Skip-flagged relations (operator-released overrides per `anonymisation-prohibition-gate`) MUST NOT appear. The signal is advisory: it MUST NOT block, fail, or alter the comparison response status.

@e2e exclude unredactedEntities computation, skip-flag exclusion, and canonical-name-only reporting — server signal computation. Covered by PHPUnit (DocumentComparisonServiceTest).

#### Scenario: A missed redaction is surfaced

- **GIVEN** file 42's anonymise set contained three `EntityRelation` rows
- **AND** the anonymised output shows change hunks for only two of them
- **WHEN** the pair is compared
- **THEN** `unredactedEntities[]` contains exactly the third relation's `{entityId, entityName}`
- **AND** the response status remains HTTP 200

#### Scenario: Skip-flagged relations are excluded from the signal

- **GIVEN** a relation with `skipAnonymization = true` (released via an acknowledged override)
- **AND** the anonymised output unsurprisingly contains no hunk for it
- **WHEN** the pair is compared
- **THEN** that relation does NOT appear in `unredactedEntities[]`

#### Scenario: Signal reports canonical names, not document text

- **GIVEN** an unredacted entity whose literal document text is "P. Jansen" and whose OR Entity canonical name is "Pieter Jansen"
- **WHEN** the signal is computed
- **THEN** the entry's `entityName` is "Pieter Jansen"
- **AND** the literal text "P. Jansen" appears nowhere in `unredactedEntities[]`

### Requirement: Comparison MUST be ephemeral

The comparison MUST NOT create, modify, or delete any OR object, NC file, or other persistent record. Repeated identical requests recompute the result. Application logs for a comparison MUST contain at most the two subjects' file IDs and version timestamps — no extracted text, no hunk content, no entity values.

@e2e exclude No-persistence side effects and identifier-only logging — backend invariants verified by PHPUnit and Newman (object-count assertions), not browser-observable.

#### Scenario: No persistence side effects

- **GIVEN** any successful comparison request
- **WHEN** object and file counts are inspected before and after
- **THEN** no OR objects, NC files, or versions were created or modified

#### Scenario: Logs contain identifiers only

- **GIVEN** a comparison request that is logged
- **WHEN** the log entry is inspected
- **THEN** it contains file IDs and version timestamps at most
- **AND** it contains no document text or entity values

### Requirement: The UI MUST provide a side-by-side comparison view

The frontend MUST offer a "Compare…" entry on the document detail surface and an original-vs-anonymised shortcut on anonymisation results. The comparison view MUST render both subjects side by side with synchronized scrolling, highlight change hunks, render redaction badges (entity type) on annotated hunks, present `unredactedEntities[]` as an advisory "verify manually" panel, and show a notice when `crossFormat` is true. Subject selection uses file and version pickers. All strings are translated (English source keys, NL translation).

#### Scenario: Operator compares original and anonymised output from the UI

- **GIVEN** a document with an anonymised output and the operator on its detail view
- **WHEN** the operator activates the original-vs-anonymised comparison entry
- **THEN** the side-by-side view opens with the pair preselected
- **AND** redacted spans are highlighted with entity-type badges

#### Scenario: Operator picks two versions

- **GIVEN** the comparison view is open for a file with multiple versions
- **WHEN** the operator selects version T1 on the left and current on the right
- **THEN** the view rerenders with the diff of those subjects

#### Scenario: Advisory panel for unredacted entities

- **GIVEN** a comparison response with one entry in `unredactedEntities[]`
- **WHEN** the view renders
- **THEN** an advisory panel lists the entity's canonical name with "verify manually" guidance
- **AND** the panel does not block any interaction

