## ADDED Requirements

### Requirement: KENTEKEN MUST be a curated, toggleable entity type

`KENTEKEN` (Dutch vehicle registration / license plate) MUST be part of DocuDesk's curated entity-type vocabulary: it MUST appear as a toggle in the Settings entity-type selector, MUST be enabled by default (all-on), and MUST be carried in the entity-type whitelist handed to the detector when a subset is active — so OpenAnonymiser (which recognises license plates) detects it. No OpenRegister change is required: unmapped whitelist types pass through to the detector unchanged.

#### Scenario: KENTEKEN is selectable in Settings

- **WHEN** the selectable entity types are requested for the Settings selector
- **THEN** the list includes `KENTEKEN`

#### Scenario: KENTEKEN is enabled by default

- **GIVEN** no stored entity-type selection
- **WHEN** the enabled entity types are resolved
- **THEN** the result includes `KENTEKEN` (the full curated set)

#### Scenario: KENTEKEN is carried in a subset whitelist

- **GIVEN** the operator enables a subset of entity types that includes `KENTEKEN`
- **WHEN** the whitelist is handed to the detector
- **THEN** `KENTEKEN` is present in the whitelist and reaches OpenAnonymiser unchanged
