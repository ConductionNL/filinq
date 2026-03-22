## MODIFIED Requirements

### Requirement: Data Resolution
The system SHALL resolve merge data from OpenRegister objects by register + schema + object UUID. The resolution SHALL support nested references up to 3 levels deep and accept ad-hoc data context merged with resolved data. Data resolution SHALL be implemented in a dedicated `DataResolverService` class at `OCA\DocuDesk\Service\DataResolverService` (extracted from the planned DocumentService) so it can be reused by both the document-creatie-sjablonen workflow and the correspondence generation workflow.

#### Scenario: Resolve object data from OpenRegister
- **WHEN** `DataResolverService::resolve(array $dataRefs)` is called with `[{"register": "brp", "schema": "ingeschreven-persoon", "id": "<uuid>"}]`
- **THEN** the object is fetched from OpenRegister via `ObjectService::find()`
- **AND** the object data is returned as an associative array keyed by schema name

#### Scenario: Nested reference resolution
- **WHEN** a resolved object contains a field referencing another OpenRegister object (e.g., `adres` field contains a UUID)
- **THEN** the referenced object is resolved recursively
- **AND** resolution stops at 3 levels deep to prevent infinite loops

#### Scenario: Ad-hoc data merged with resolved data
- **WHEN** `resolve()` is called with both object references and an `adHocData` array
- **THEN** the ad-hoc data is merged into the resolved context
- **AND** ad-hoc values override resolved values when keys conflict

#### Scenario: Resolution failure returns descriptive error
- **WHEN** an object reference points to a non-existent object
- **THEN** the error array includes the specific reference that failed with register, schema, and ID
- **AND** other references are still resolved successfully
