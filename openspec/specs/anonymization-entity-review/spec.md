## ADDED Requirements

### Requirement: Consolidated entity list endpoint
<!-- @e2e exclude REST endpoint behaviour (GET batch entities — dedup, confidence sort, WOO-profile inclusion flag, 409 on incomplete batch); data-layer contract verified by Newman docudesk-api batch-entities collection + PHPUnit. The interactive review surface it feeds is covered by this spec's UI tests. -->
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
