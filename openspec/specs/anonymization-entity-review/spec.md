## Purpose

@e2e exclude entity review UI for batch anonymization is not yet shipped in the current DocuDesk release (v0.0.34) — batch entity consolidation endpoint and review table are unbuilt; covered by PHPUnit and API contract tests when implemented

## ADDED Requirements

### Requirement: Consolidated entity list endpoint
The system SHALL provide `GET /api/anonymization/batch/{batchId}/entities` that returns all unique entities detected across all files in the batch. Entities SHALL be deduplicated by value (case-insensitive). Each entity SHALL include: type, value, highestConfidence (maximum confidence across all files), fileCount (number of files containing this entity), and included (boolean, pre-set based on active WOO profile). Uses OpenRegister's EntityRelationMapper for entity data.

#### Scenario: Retrieve consolidated entities for review
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called for a batch in "review" status
- **THEN** the response includes a deduplicated entities array sorted by confidence descending
- **AND** each entity has type, value, highestConfidence, fileCount, and included fields
- **AND** entities matching the WOO anonymize profile have included=true
- **AND** entities matching the WOO keep profile have included=false

#### Scenario: Entities endpoint for non-review batch
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called for a batch still in "extracting" status
- **THEN** the system returns HTTP 409 with error "Batch extraction is not yet complete"

### Requirement: Entity toggle in review
The system SHALL allow users to toggle individual entities on/off via the frontend entity review table. The frontend SHALL send the final reviewed entity list (with included=true entities only) to `POST /api/anonymization/batch/{batchId}/anonymize`. Entity toggling is a frontend-only concern — the backend receives the final list.

#### Scenario: User excludes an entity from anonymization
- **WHEN** a user unchecks "Gemeente Utrecht" (type: ORGANIZATION) in the entity review table
- **THEN** the entity's included flag is set to false in the frontend state
- **AND** when anonymization is triggered, "Gemeente Utrecht" is NOT included in the entities array sent to the backend

#### Scenario: User includes a previously excluded entity
- **WHEN** a user checks a previously unchecked entity
- **THEN** the entity's included flag is set to true
- **AND** it will be included in the anonymization request

### Requirement: Confidence threshold filter
The system SHALL support a configurable confidence threshold. The `GET /api/anonymization/batch/{batchId}/entities` endpoint SHALL accept an optional `minConfidence` query parameter (float 0.0-1.0, default 0.0). Entities below the threshold SHALL have included=false and SHALL be visually flagged as "low confidence" in the frontend. This supports GDPR Article 5(1)(d) accuracy principle — avoiding false-positive anonymization.

#### Scenario: Apply confidence threshold
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities?minConfidence=0.7` is called
- **THEN** entities with highestConfidence >= 0.7 have included=true (subject to WOO profile)
- **AND** entities with highestConfidence < 0.7 have included=false regardless of WOO profile

#### Scenario: Default threshold includes all entities
- **WHEN** no minConfidence parameter is provided
- **THEN** all entities are evaluated against the WOO profile only (no confidence filtering)

### Requirement: Entity search and filter in UI
The frontend entity review table SHALL support text search (filtering entities by value substring) and type filter (dropdown to filter by entity type: PERSON, ORGANIZATION, EMAIL, PHONE, BSN, IBAN, ADDRESS, LOCATION, DATE, OTHER). Filters SHALL be combinable (search + type filter). The table SHALL show entity count matching the current filter.

#### Scenario: Search entities by value
- **WHEN** a user types "Utrecht" in the entity search box
- **THEN** only entities whose value contains "Utrecht" (case-insensitive) are shown
- **AND** the filtered count is displayed (e.g., "3 of 45 entities")

#### Scenario: Filter entities by type
- **WHEN** a user selects "PERSON" from the type dropdown
- **THEN** only entities with type "PERSON" are shown
- **AND** the filtered count updates accordingly

#### Scenario: Combined search and type filter
- **WHEN** a user searches "Jan" AND selects type "PERSON"
- **THEN** only PERSON entities whose value contains "Jan" are shown

### Requirement: Bulk entity actions
The frontend SHALL support "Select All Visible" and "Deselect All Visible" actions that toggle the included flag for all currently visible (filtered) entities. This enables efficient review of large entity lists.

#### Scenario: Select all visible entities
- **WHEN** a user has filtered to show only PERSON entities and clicks "Select All Visible"
- **THEN** all visible PERSON entities have included=true
- **AND** entities hidden by the filter are not affected

#### Scenario: Deselect all visible entities
- **WHEN** a user clicks "Deselect All Visible" with no filter active
- **THEN** all entities have included=false

### Requirement: Entity review UI layout
The frontend SHALL display the entity review as a table with columns: checkbox (included toggle), Type (badge), Value (text), Confidence (percentage), Files (count). The table SHALL be sortable by any column. Low-confidence entities (below threshold) SHALL have a visual indicator (warning icon). The review step SHALL show a summary bar: "X of Y entities selected for anonymization across Z files".

#### Scenario: Display entity review table
- **WHEN** a batch enters "review" status and the user views the entity review
- **THEN** a table is shown with all consolidated entities
- **AND** each row has a checkbox, type badge, value, confidence percentage, and file count
- **AND** a summary bar shows selected/total entity counts and file count

#### Scenario: Sort entities by confidence
- **WHEN** a user clicks the "Confidence" column header
- **THEN** entities are sorted by confidence descending (highest first)
- **AND** clicking again sorts ascending
## Requirements
### Requirement: Consolidated entity list endpoint
The system SHALL deduplicate entities across files.

#### Scenario: Retrieve consolidated entities
- **WHEN** GET /api/anonymization/batch/{batchId}/entities is called
- **THEN** deduplicated entities are returned with included flags

### Requirement: Confidence threshold filter
The system SHALL support configurable confidence thresholds.

#### Scenario: Apply confidence threshold
- **WHEN** minConfidence=0.7 parameter is provided
- **THEN** entities below 0.7 have included=false

### Requirement: The consolidated-entities endpoint response MUST include `prohibitionMatch` per entity

The endpoint `GET /api/anonymization/batch/{batchId}/entities` (per the existing capability) MUST include a `prohibitionMatch` field on every entity entry. The field follows the same shape as defined in the `anonymization` delta:

- `null` — no prohibition rule matches the entity, OR
- `{ ruleId, ruleName, highConfidence }` — a prohibition rule matched.

The matcher consulted at this endpoint MUST be the same `PolicyMatchService` used by the extract endpoint and the gate. Confidence used for `highConfidence` SHOULD be the entity's `highestConfidence` across the batch (already exposed by the existing endpoint).

#### Scenario: Entity with no prohibition match returns null

- **GIVEN** a batch with an entity that no prohibition rule matches
- **WHEN** the consolidated-entities endpoint is called
- **THEN** the entity entry has `prohibitionMatch: null`

#### Scenario: High-confidence prohibition match is reported

- **GIVEN** a batch entity with `highestConfidence: 0.93` matching prohibition rule `R-X` (primaryName "Beschermde Getuige A")
- **WHEN** the endpoint is called
- **THEN** the entity entry has `prohibitionMatch: {ruleId: "R-X", ruleName: "Beschermde Getuige A", highConfidence: true}`

#### Scenario: Highest-confidence reading is used across the batch

- **GIVEN** an entity detected in three files within a batch at confidences 0.62, 0.78, 0.91
- **AND** the entity matches a prohibition rule
- **AND** the configured threshold is 0.85
- **WHEN** the endpoint is called
- **THEN** `prohibitionMatch.highConfidence` is `true` (because the highest confidence — 0.91 — is above threshold)

### Requirement: The consolidated-entities endpoint response MUST include `suggestedBases[]` per entity

Each entity entry MUST include a `suggestedBases` field — an array of UUIDs auto-derived from the `bases[]` of the dossier the batch's files belong to. If a file does not belong to a dossier, or the dossier has empty `bases[]`, `suggestedBases` MUST be an empty array.

The field is a hint for the review UI's grondslag picker. The actual `bases[]` chosen by the operator is sent on the anonymise request and may differ from `suggestedBases`.

#### Scenario: Dossier-bound files inherit the dossier's bases

- **GIVEN** a batch whose files all belong to a dossier with `bases: ["uuid-base-a", "uuid-base-b"]`
- **WHEN** the consolidated-entities endpoint is called
- **THEN** every entity entry has `suggestedBases: ["uuid-base-a", "uuid-base-b"]`

#### Scenario: Files not in a dossier yield empty suggestedBases

- **GIVEN** a batch whose files do not belong to any dossier
- **WHEN** the endpoint is called
- **THEN** every entity entry has `suggestedBases: []`

#### Scenario: Dossier with empty bases yields empty suggestedBases

- **GIVEN** a batch whose files belong to a dossier with `bases: []` (e.g. a draft dossier)
- **WHEN** the endpoint is called
- **THEN** every entity entry has `suggestedBases: []`

#### Scenario: Files spread across dossiers — union of dossier bases

- **GIVEN** a batch whose files belong to two different dossiers, with bases `["A"]` and `["B", "C"]` respectively
- **WHEN** the endpoint is called
- **THEN** every entity entry has `suggestedBases: ["A", "B", "C"]` (union, deduplicated)

### Requirement: The change MUST be additive and non-breaking

Pre-change clients reading only the existing fields (type, value, highestConfidence, fileCount, included) MUST continue to work without modification.

#### Scenario: Pre-change client continues to work

- **GIVEN** a pre-change client reading the consolidated-entities response
- **WHEN** the client receives a response with new `prohibitionMatch` and `suggestedBases` fields
- **THEN** the client's existing code reading the unchanged fields works without modification
- **AND** the response is a strict superset of the pre-change shape

