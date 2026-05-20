---
status: draft
---

# Anonymization Entity Review

This capability introduces the entity review step into the DocuDesk anonymisation pipeline. After batch extraction completes, operators can inspect all detected entities in a consolidated, deduplicated list, apply a confidence threshold, search and filter by type, toggle individual entities, and bulk-manage inclusion — before triggering the final anonymise call with only the approved set.

## ADDED Requirements

### REQ-ERV-001: Consolidated entity list endpoint

The system SHALL provide `GET /api/anonymization/batch/{batchId}/entities` that returns all unique entities detected across all files in the batch. Entities SHALL be deduplicated by value (case-insensitive). Each entity in the response SHALL include:

- `type` — entity type string (PERSON, ORGANIZATION, EMAIL, PHONE, BSN, IBAN, ADDRESS, LOCATION, DATE, or OTHER)
- `value` — canonical entity value (from the highest-confidence occurrence)
- `highestConfidence` — maximum confidence score across all files containing this entity (float, 0.0–1.0)
- `fileCount` — number of distinct files in the batch containing this entity (integer ≥ 1)
- `included` — boolean; pre-set based on the active WOO anonymise profile (true if the entity type appears in the anonymise list, false if in the keep list)

The endpoint SHALL use OpenRegister's `EntityRelationMapper` to retrieve per-file entity data.

The response array SHALL be sorted by `highestConfidence` descending by default.

#### Scenario: Retrieve consolidated entities for a batch in review status

- **GIVEN** a batch in "review" status containing three files, each with overlapping detected entities
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called
- **THEN** the response is HTTP 200 with a JSON array of deduplicated entities
- **AND** each entity has `type`, `value`, `highestConfidence`, `fileCount`, and `included` fields
- **AND** "gemeente utrecht" and "Gemeente Utrecht" appear as a single entity (deduplicated case-insensitively)
- **AND** entities are sorted by `highestConfidence` descending

#### Scenario: WOO anonymise profile sets included defaults

- **GIVEN** the active WOO profile lists PERSON and BSN in its anonymise list and LOCATION in its keep list
- **WHEN** the consolidated-entities endpoint is called for a batch containing entities of those types
- **THEN** PERSON entities have `included: true`
- **AND** BSN entities have `included: true`
- **AND** LOCATION entities have `included: false`

#### Scenario: Entities endpoint returns 409 for a batch not yet in review status

- **GIVEN** a batch still in "extracting" status
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities` is called
- **THEN** the response is HTTP 409
- **AND** the response body contains `{ "error": "Batch extraction is not yet complete" }`

#### Scenario: fileCount reflects the number of distinct files containing the entity

- **GIVEN** a batch of five files where "Jan de Vries" (PERSON) is detected in three of them
- **WHEN** the consolidated-entities endpoint is called
- **THEN** the entity entry for "Jan de Vries" has `fileCount: 3`

#### Scenario: highestConfidence is the maximum score across all files

- **GIVEN** "Gemeente Utrecht" detected in two files with confidence scores 0.72 and 0.91
- **WHEN** the consolidated-entities endpoint is called
- **THEN** the entity entry for "Gemeente Utrecht" has `highestConfidence: 0.91`

---

### REQ-ERV-002: Entity toggle in review (frontend)

The frontend entity review table SHALL allow users to toggle individual entities on or off by clicking the checkbox in the `included` column. Entity toggle state SHALL be maintained in frontend store state only — no backend call is made per toggle.

When the operator triggers anonymisation, the frontend SHALL send only entities with `included: true` to `POST /api/anonymization/batch/{batchId}/anonymize`. Entities with `included: false` SHALL NOT appear in the anonymise request payload.

#### Scenario: User excludes an entity from anonymisation

- **GIVEN** the entity review table is displayed with "Gemeente Utrecht" (ORGANIZATION) marked `included: true`
- **WHEN** the user unchecks the checkbox for "Gemeente Utrecht"
- **THEN** the entity's `included` flag is set to `false` in the frontend store
- **AND** the summary bar updates to reflect the reduced included count

#### Scenario: Excluded entity is absent from the anonymise payload

- **GIVEN** "Gemeente Utrecht" has been unchecked (included=false) in the review table
- **WHEN** the user clicks the Anonymise button
- **THEN** the POST request to the anonymise endpoint does NOT include "Gemeente Utrecht" in the `entities[]` array

#### Scenario: User re-includes a previously excluded entity

- **GIVEN** "Gemeente Utrecht" is currently unchecked (included=false)
- **WHEN** the user checks the checkbox for "Gemeente Utrecht"
- **THEN** the entity's `included` flag is set to `true` in the frontend store
- **AND** it will be included in the next anonymise request

---

### REQ-ERV-003: Confidence threshold filter

The `GET /api/anonymization/batch/{batchId}/entities` endpoint SHALL accept an optional query parameter `minConfidence` (float, 0.0–1.0, default 0.0). Entities whose `highestConfidence` is below the `minConfidence` threshold SHALL have `included: false` regardless of WOO profile.

In the frontend, entities below the operator's selected confidence threshold SHALL be visually flagged with a warning icon. The frontend SHALL support a threshold selector that re-fetches or re-filters the entity list.

This requirement implements GDPR Article 5(1)(d) accuracy principle — avoiding false-positive anonymisation by allowing operators to suppress low-confidence detections.

#### Scenario: Apply confidence threshold — entities above threshold keep profile defaults

- **GIVEN** the active WOO profile includes PERSON in the anonymise list
- **WHEN** `GET /api/anonymization/batch/{batchId}/entities?minConfidence=0.7` is called
- **THEN** PERSON entities with `highestConfidence >= 0.7` have `included: true`
- **AND** PERSON entities with `highestConfidence < 0.7` have `included: false`

#### Scenario: Threshold overrides WOO profile for low-confidence entities

- **GIVEN** an entity of type BSN (in the anonymise list) with `highestConfidence: 0.55`
- **AND** `minConfidence=0.7` is specified
- **WHEN** the consolidated-entities endpoint is called
- **THEN** the BSN entity has `included: false` (threshold overrides profile)

#### Scenario: Default threshold (0.0) includes all entities per WOO profile

- **GIVEN** no `minConfidence` parameter is provided
- **WHEN** the consolidated-entities endpoint is called
- **THEN** entity `included` values are determined solely by the WOO profile, with no confidence filtering applied

#### Scenario: Low-confidence entities are visually flagged in the frontend

- **GIVEN** a threshold of 0.7 is applied in the frontend
- **AND** the entity list contains entries with `highestConfidence` below 0.7
- **WHEN** the entity review table renders
- **THEN** low-confidence entities display a warning icon in their row
- **AND** they are shown with muted / de-emphasised styling

---

### REQ-ERV-004: Entity search and filter in UI

The frontend entity review table SHALL support:

- **Text search**: filtering entities by value substring (case-insensitive). The search box SHALL filter the visible rows in real-time as the operator types.
- **Type filter**: a dropdown that filters by entity type. Available options SHALL be: PERSON, ORGANIZATION, EMAIL, PHONE, BSN, IBAN, ADDRESS, LOCATION, DATE, OTHER, and an "All types" reset option.
- **Combined filtering**: text search and type filter SHALL be combinable. Applying both filters shows only entities that match both conditions simultaneously.
- **Filter count**: the table SHALL display the count of entities matching the current filter (e.g. "3 of 45 entities").

#### Scenario: Search entities by value substring

- **GIVEN** the entity review table is displayed with 45 entities
- **AND** the operator types "Utrecht" in the search box
- **THEN** only entities whose value contains "Utrecht" (case-insensitive) are visible
- **AND** the filter count reads "3 of 45 entities" (example count)

#### Scenario: Filter entities by type

- **GIVEN** the entity list contains PERSON, ORGANIZATION, and EMAIL entities
- **WHEN** the operator selects "PERSON" from the type dropdown
- **THEN** only PERSON entities are visible
- **AND** the filter count updates to show the number of PERSON entities out of the total

#### Scenario: Combined search and type filter

- **GIVEN** the entity list contains PERSON entities "Jan de Vries", "Jan Janssen", and "Pieter Bakker"
- **WHEN** the operator types "Jan" in the search box AND selects "PERSON" from the type dropdown
- **THEN** only "Jan de Vries" and "Jan Janssen" are visible (PERSON entities whose value contains "Jan")
- **AND** "Pieter Bakker" is hidden (PERSON but value does not match)

#### Scenario: Filter count reflects current filter state

- **GIVEN** a filter is active showing 8 of 45 entities
- **WHEN** the operator clears the search box
- **THEN** the filter count updates to reflect the new matching count

---

### REQ-ERV-005: Bulk entity actions

The frontend entity review table SHALL provide "Select All Visible" and "Deselect All Visible" actions in the table toolbar. These actions SHALL toggle the `included` flag for all entities currently visible (i.e. matching the active search and type filter). Entities hidden by the current filter SHALL NOT be affected.

#### Scenario: Select All Visible on a filtered set

- **GIVEN** the type filter is set to "PERSON" and 12 PERSON entities are visible
- **AND** some PERSON entities have `included: false`
- **WHEN** the operator clicks "Select All Visible"
- **THEN** all 12 visible PERSON entities have `included: true`
- **AND** non-PERSON entities (hidden by the filter) retain their current `included` state

#### Scenario: Deselect All Visible on an unfiltered list

- **GIVEN** no filter is active and 45 entities are visible, all with `included: true`
- **WHEN** the operator clicks "Deselect All Visible"
- **THEN** all 45 entities have `included: false`
- **AND** the summary bar updates to "0 of 45 entities selected"

#### Scenario: Bulk action does not affect hidden entities

- **GIVEN** the type filter is set to "BSN" showing 3 BSN entities
- **AND** 42 other entities are hidden by the filter
- **WHEN** the operator clicks "Deselect All Visible"
- **THEN** only the 3 visible BSN entities have `included: false`
- **AND** the 42 hidden entities retain their previous `included` state

---

### REQ-ERV-006: Entity review UI layout

The frontend entity review table SHALL render the following columns:

| Column | Content | Notes |
|---|---|---|
| Checkbox | `included` toggle | Checked = entity will be anonymised |
| Type | Entity type string, rendered as a badge | Colour-coded by type if possible |
| Value | Entity value text | Full text; truncate with ellipsis if overflows |
| Confidence | `highestConfidence` as a percentage (e.g. "91%") | — |
| Files | `fileCount` as integer | Number of files in batch containing this entity |

The table SHALL be sortable by any column — clicking the column header toggles between descending and ascending order, with a sort-direction chevron indicator.

Entities below the active confidence threshold SHALL display a warning icon (alongside or replacing the Type badge) to indicate "low confidence".

The review step SHALL display a summary bar with the text: **"X of Y entities selected for anonymization across Z files"** where:
- X = count of entities with `included: true`
- Y = total entity count in the (possibly filtered) list
- Z = sum of `fileCount` for `included: true` entities (unique files)

#### Scenario: Display entity review table with all columns

- **GIVEN** a batch in "review" status with consolidated entities loaded
- **WHEN** the operator views the Review step
- **THEN** a table is displayed with columns: checkbox, Type, Value, Confidence, Files
- **AND** each row shows the entity's checkbox state, type badge, value text, confidence percentage, and file count
- **AND** a summary bar below the table shows the selected/total counts and file count

#### Scenario: Sort entities by confidence descending (default)

- **GIVEN** the entity review table is loaded for the first time
- **THEN** entities are sorted by Confidence column descending (highest confidence first) by default

#### Scenario: Re-sort by column header click

- **GIVEN** entities are displayed sorted by Confidence descending
- **WHEN** the operator clicks the "Value" column header
- **THEN** entities are sorted alphabetically by value ascending
- **AND** clicking "Value" again sorts alphabetically descending

#### Scenario: Low-confidence entity has warning indicator

- **GIVEN** a confidence threshold of 0.7 is active
- **AND** an entity "073-1234567" (PHONE) has `highestConfidence: 0.48`
- **WHEN** the entity review table renders
- **THEN** the row for "073-1234567" displays a warning icon
- **AND** the row is styled to visually distinguish it from high-confidence entities

#### Scenario: Summary bar reflects current included state

- **GIVEN** 45 entities are loaded, 30 have `included: true` spanning 8 unique files
- **WHEN** the operator views the summary bar
- **THEN** the summary bar reads "30 of 45 entities selected for anonymization across 8 files"
