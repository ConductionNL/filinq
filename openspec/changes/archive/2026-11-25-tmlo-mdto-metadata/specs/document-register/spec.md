# document-register Specification (delta)

## ADDED Requirements

### Requirement: MdtoSupplement Schema in Document Register (REQ-DDTMM-020)

The document register MUST include the `mdtoSupplement` schema with full
`required`, `properties` and `hardValidation: true` (OR Adoption Decision
3), holding the fields of REQ-DDTMM-002. It MUST NOT duplicate a
retention-owned core attribute, and MUST NOT carry the
`x-openregister-archival` annotation: a supplement lives as long as its
record and goes with it through the vernietigingslijst path, never by its
own sweep. The register version MUST be bumped.

#### Scenario: Schema present after version bump

- GIVEN a fresh installation after `ConfigurationService::importFromApp()` runs
- WHEN the document register's schemas are listed via `objectService->getSchemas(register: 'document')`
- THEN `mdtoSupplement` is included with `hardValidation: true`
- @e2e exclude boot-time register import with no UI surface — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: Supplement carries no retention-owned attribute

- GIVEN the shipped `mdtoSupplement` schema definition
- WHEN its properties are checked against the retention-owned core attribute names
- THEN none of waardering/bewaartermijn/archiefactiedatum/archiefstatus/classificatie appears as a schema property
- AND the schema declares no `x-openregister-archival` annotation
- @e2e exclude declarative register-content rule — covered by a PHPUnit register-lint test
