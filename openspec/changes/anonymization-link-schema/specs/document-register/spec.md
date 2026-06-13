## ADDED Requirements

### Requirement: AnonymizationLink Schema in Document Register (REQ-DREG-ALINK-01)

The `document` register SHALL include the `anonymizationLink` schema. The schema SHALL declare full `required`, `properties`, and `hardValidation: true` per OR Adoption Decision 3. The schema SHALL carry `x-openregister-archival` with `retention: P7Y` aligned with the anonymisation audit-trail obligation under GDPR Art. 5(2) (accountability principle). The `x-openregister-archival.category` SHALL be shipped as an explicit placeholder (e.g. `"TODO: confirm Archiefwet 1995 selectielijst category with selectielijst manager"`) — the precise selectielijst classification is to be confirmed by the organisation's selectielijst manager before this change is archived.

#### Scenario: Schema present in document register after version bump

- **WHEN** `SettingsInitializer::initialize()` runs against a fresh installation with `info.version "5.3.0"`
- **THEN** the `document` register SHALL expose `anonymizationLink` in its `schemas` array
- **AND** `objectService->getSchemas(register: 'document')` SHALL include `anonymizationLink`

#### Scenario: AnonymizationLink archival after 7 years

- **GIVEN** `x-openregister-archival.retention: P7Y` is declared on the `anonymizationLink` schema
- **WHEN** OR's archival background job runs
- **THEN** `anonymizationLink` records older than 7 years SHALL be eligible for archival
- **AND** this traces to GDPR Art. 5(2) accountability (selectielijst category placeholder pending sign-off)

#### Scenario: Document register version is 5.3.0 after config update

- **GIVEN** the current stored `configuration_version` is `5.2.0`
- **WHEN** `SettingsInitializer::initialize()` detects `info.version "5.3.0"`
- **THEN** `version_compare("5.3.0", "5.2.0", ">")` SHALL return `true`
- **AND** `ConfigurationService::importFromApp()` SHALL be called once
- **AND** the stored `configuration_version` SHALL be updated to `5.3.0`

## MODIFIED Requirements

### Requirement: Correspondence Schema — Full JSON Schema with Archival (REQ-DREG-01)

The `correspondence` schema tracks individual generated documents. It declares full JSON Schema validation and a P7Y archival retention.

**Note**: This requirement is unchanged. Listed here to confirm the `document` register now contains four active schemas: `correspondence`, `huisstijl`, `batchCorrespondenceJob`, and `anonymizationLink`.

#### Scenario: Correspondence record validates strictly

- **GIVEN** the correspondence schema has `hardValidation: true`
- **WHEN** a controller writes a record with an unknown field (e.g., `foo: "bar"`)
- **THEN** OR's validator SHALL reject the write with a validation error
- **AND** no record SHALL be persisted

#### Scenario: Correspondence archival after 7 years

- **GIVEN** `x-openregister-archival.retention: P7Y` is declared on the correspondence schema
- **WHEN** OR's archival background job runs
- **THEN** correspondence records older than 7 years SHALL be eligible for archival
- **AND** this traces to Archiefwet 1995 selectielijst cat. 3.2 (zakelijke correspondentie)

#### Scenario: Generated correspondence lifecycle

- **GIVEN** a correspondence record is created with status `generated`
- **WHEN** the record is queried
- **THEN** the status field SHALL equal `generated` (terminal success state)
- **AND** no further status transitions are expected for individual correspondence records

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-001 | Correspondence schema declares `required` + `properties` + `hardValidation: true` | MUST | Implementing |
| DREG-002 | Correspondence schema carries `x-openregister-archival.retention: P7Y` | MUST | Implementing |
| DREG-003 | Status field has enum `[generated, failed]` — preserved per Decision 5 | MUST | Implementing |
| DREG-004 | x-openregister-notifications: `correspondenceFailed` keyed on creation event | MUST | Implemented |
| DREG-050 | `anonymizationLink` schema added to `document` register schemas array | MUST | Implementing |
| DREG-051 | `info.version` bumped `5.2.0` → `5.3.0` | MUST | Implementing |
| DREG-052 | `anonymizationLink` carries `x-openregister-archival.retention` in OBJECT form `{default: P7Y}` (OR ArchivalAnnotationValidator rejects the string form); `category` is a placeholder pending selectielijst sign-off | MUST | Implementing |
| DREG-056 | `document` register `version` bumped `1.0.0` → `1.1.0` so OR re-links the register's schemas array (register import is version-gated) | MUST | Implementing |
| DREG-053 | `anonymizationLink.sourceFileId` and `anonymizationLink.anonymizedFileId` are `facetable: true` | MUST | Implementing |
| DREG-054 | `anonymizationLink` has `hardValidation: true` | MUST | Implementing |
| DREG-055 | Seed objects for `anonymizationLink` added to `components.objects` | MUST | Implementing |
