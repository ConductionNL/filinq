## ADDED Requirements

### Requirement: Entity-type to grondslag mapping configuration
The system SHALL persist an instance-global mapping from entity type to grondslag, stored as a JSON object in DocuDesk app configuration under the key `docudesk.grondslagen.entity_type_bases`. Each property key is an entity type identifier (e.g. `PERSON`, `BSN`); each value is an array of `base` (grondslag) slugs containing zero or more entries. The mapping SHALL be readable and writable through the DocuDesk settings service.

#### Scenario: Save an entity-type mapping
- **GIVEN** an administrator on the DocuDesk settings page
- **WHEN** they map `PERSON` to the base `uitvoering-publiekrechtelijke-taak` and save
- **THEN** `docudesk.grondslagen.entity_type_bases` contains `{"PERSON":["uitvoering-publiekrechtelijke-taak"]}`
- **AND** the value is returned on the next settings read

#### Scenario: Multiple bases for one type
- **WHEN** an administrator selects two bases for `BSN`
- **THEN** the mapping value for `BSN` is an array containing both base slugs
- **AND** the order selected is preserved

#### Scenario: Unconfigured type has no entry
- **WHEN** no base has been configured for `EMAIL`
- **THEN** the mapping contains no `EMAIL` key
- **AND** reading the mapping for `EMAIL` yields an empty result

### Requirement: Grondslag selector in admin settings
The DocuDesk admin settings panel SHALL present, for each available entity type, a control to select zero or more `base` records as the proposed grondslag for that type. The list of selectable `base` records SHALL be loaded from the `base` register so that operator-added bases appear automatically. The control SHALL allow multiple selections, defaulting to a single selection. Saving the selector SHALL persist to the entity-type mapping configuration.

#### Scenario: Available bases include operator-added ones
- **GIVEN** a municipality has added a custom `base` record `gemeentelijke-verordening`
- **WHEN** the administrator opens the grondslag selector for any entity type
- **THEN** `gemeentelijke-verordening` appears among the selectable bases
- **AND** it can be chosen as the proposed grondslag for that type

#### Scenario: Selection persists to the mapping
- **WHEN** the administrator selects a base for `LOCATION` and saves the settings
- **THEN** the entity-type mapping configuration reflects the `LOCATION` selection

### Requirement: Selectable entity types from a curated list
The set of entity types offered in the selector SHALL come from a curated list maintained within DocuDesk, seeded from the entity types the configured anonymiser backend is known to emit. The list SHALL be available without contacting the backend. (Sourcing the list live from the backend is a planned enhancement, dependent on the backend exposing a supported-types endpoint; see design.md.)

#### Scenario: Selector lists the curated entity types
- **WHEN** the administrator opens the grondslag settings
- **THEN** the curated entity types (e.g. PERSON, LOCATION, ORGANIZATION, EMAIL, BSN) are listed for mapping
- **AND** rendering the list requires no call to the anonymiser backend

#### Scenario: Detected type absent from the curated list
- **GIVEN** a detected entity whose type is not present in the curated list
- **WHEN** its relation is created
- **THEN** no mapping exists for that type
- **AND** its `bases` is left empty per the unmapped-type rule

### Requirement: Proposed grondslag pre-filled at detection time
When entities are detected and their `EntityRelation` records are created or normalized, the system SHALL populate each relation's `bases` from the entity-type mapping for that entity's type, but ONLY when the relation's `bases` is empty. The system SHALL NOT modify a relation whose `bases` already contains one or more entries.

#### Scenario: Empty bases is pre-filled from the mapping
- **GIVEN** `PERSON` is mapped to `uitvoering-publiekrechtelijke-taak`
- **AND** a detected `PERSON` entity's relation has empty `bases`
- **WHEN** the entity relation is created at detection time
- **THEN** its `bases` is set to `[uitvoering-publiekrechtelijke-taak]`

#### Scenario: Operator-assigned bases are not clobbered
- **GIVEN** a detected entity's relation already has a non-empty `bases` (assigned by the operator)
- **WHEN** detection runs again over the same entity
- **THEN** its `bases` is left unchanged

#### Scenario: All mapped bases are applied
- **GIVEN** `BSN` is mapped to two bases
- **AND** a detected `BSN` entity's relation has empty `bases`
- **WHEN** the relation is created at detection time
- **THEN** its `bases` contains both mapped bases

### Requirement: Unmapped entity types leave bases empty
When a detected entity's type has no entry in the mapping, or maps to an empty array, the system SHALL leave that relation's `bases` empty and SHALL NOT apply any default or catch-all grondslag.

#### Scenario: No mapping leaves the relation empty
- **GIVEN** no base is configured for `IBAN`
- **WHEN** a `IBAN` entity is detected and its relation created
- **THEN** the relation's `bases` is empty
- **AND** no fallback grondslag is applied

### Requirement: No retroactive re-proposal
Changing the entity-type mapping SHALL NOT alter `bases` on existing entity relations. A subsequent analysis run SHALL re-propose only onto relations whose `bases` are empty; relations that already have `bases` SHALL remain unchanged.

#### Scenario: Mapping change does not touch existing relations
- **GIVEN** a relation whose `bases` was pre-filled under an earlier mapping
- **WHEN** the administrator changes the mapping for that entity type
- **THEN** the existing relation's `bases` is unchanged

#### Scenario: Re-analysis only fills empty relations
- **GIVEN** one relation with empty `bases` and one with operator-assigned `bases`
- **WHEN** analysis is re-run with a mapping configured for that type
- **THEN** the empty relation receives the proposed bases
- **AND** the operator-assigned relation is unchanged

### Requirement: Proposed bases reuse the existing grondslag record
Proposed bases SHALL be stored in the same `EntityRelation.bases` field used for operator-assigned grondslag, so the existing grondslagen summary renders them without modification. The system SHALL NOT record or expose a distinction between system-proposed and operator-confirmed bases.

#### Scenario: Proposed bases appear in the grondslagen summary
- **GIVEN** a relation whose `bases` was pre-filled at detection time
- **WHEN** the grondslagen summary is generated for the document
- **THEN** the proposed bases are listed identically to manually assigned bases
- **AND** no "proposed" versus "confirmed" indicator is shown
