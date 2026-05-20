---
status: draft
---

# Document Register

## Purpose

Defines the data model for the `document` register used by DocuDesk to store document analysis results. The register is defined in `lib/Settings/document_register.json` (separate from the consent-focused `docudesk_register.json`) and contains four schemas: `report` (analysis results), `template` (document templates, placeholder), `entity` (cross-document entity management, placeholder), and `anonymization` (anonymization operation output). Pre-seeded sample objects demonstrate the anonymization pipeline's output format. All four schemas use `properties: []` and `hardValidation: false` — field definitions are documented here, not schema-enforced.

## FIXED Requirements

### REQ-DREG-07: Register Loading Gap (Priority: Must)

The document_register.json MUST be loaded during application boot alongside docudesk_register.json.

#### Scenario: Boot initialization loads document register

- **GIVEN** the DocuDesk app is installed or upgraded on a Nextcloud instance with OpenRegister enabled
- **WHEN** `RegistersLoader` repair step runs
- **THEN** `ConfigurationService::importFromApp()` is called for `document_register.json`
- **AND** a register with slug `document` and version `0.0.1` is created in OpenRegister
- **AND** all four schemas (`report`, `template`, `entity`, `anonymization`) are created
- **AND** the three seed objects are created

#### Scenario: Import is idempotent on re-run

- **GIVEN** the document register already exists from a previous install
- **WHEN** `RegistersLoader` runs again (e.g. on upgrade)
- **THEN** the existing register, schemas, and seed objects are NOT duplicated
- **AND** `version_compare`-based skip logic prevents re-import of the same version
- **AND** existing report objects stored by the pipeline are preserved

#### Scenario: Register is separately discoverable from consent register

- **GIVEN** both `docudesk_register.json` and `document_register.json` have been imported
- **WHEN** `GET /api/registers` is called
- **THEN** both registers appear as distinct entries
- **AND** the `document` register contains only `report`, `template`, `entity`, `anonymization` schemas
- **AND** the `docudesk` register contains only consent-related schemas

### REQ-DREG-62: Anonymization Schema Missing from Register (Priority: Must)

The `anonymization` schema MUST be declared in `document_register.json` to resolve the inconsistency with sample objects that reference it.

#### Scenario: Anonymization schema is declared

- **GIVEN** `document_register.json` is loaded into OpenRegister
- **WHEN** the register's schema list is inspected
- **THEN** it contains: `report`, `template`, `entity`, AND `anonymization`
- **AND** `anonymization` has `hardValidation: false` and `properties: []`

#### Scenario: Sample objects 2 and 3 reference a valid schema

- **GIVEN** sample object UUID `c04e1fa9-d20c-457d-8afa-011af9a16b7e` uses schema `anonymization`
- **AND** sample object UUID `685c5b5c-1b31-45a3-9b1e-58357dc5896d` uses schema `report`
- **WHEN** the objects are created via the seed import
- **THEN** both objects resolve their schema slugs to valid schema records
- **AND** no "undefined schema" error is raised

## MUST Requirements

### REQ-DREG-01: Document Register Structure (Priority: Must)

A dedicated document register exists with four schemas for storing analysis results, templates, entity tracking, and anonymization output.

#### Scenario: Register creation from JSON

- **GIVEN** the `document_register.json` file exists in `lib/Settings/`
- **WHEN** the register is loaded into OpenRegister via `ConfigurationService::importFromApp()`
- **THEN** a register with slug `"document"` and version `"0.0.1"` is created
- **AND** it contains four schemas: `report`, `template`, `entity`, `anonymization`

#### Scenario: Separate from consent register

- **GIVEN** both `document_register.json` and `docudesk_register.json` have been imported
- **WHEN** the registers are inspected
- **THEN** they are separate registers with different slugs and purposes
- **AND** `docudesk_register.json` handles consent-related schemas
- **AND** `document_register.json` handles analysis, reporting, and anonymization-output schemas

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-001 | A `document` register exists with slug `document`, version `0.0.1` | MUST | Implemented |
| DREG-002 | The register contains four schemas: report, template, entity, anonymization | MUST | Bug → Fixed |
| DREG-003 | The register is defined in `lib/Settings/document_register.json` | MUST | Implemented |
| DREG-004 | The JSON follows OpenAPI-like structure with components | MUST | Implemented |
| DREG-005 | The register is separate from `docudesk_register.json` | MUST | Implemented |
| DREG-060 | `document_register.json` is imported during boot via repair step | MUST | Bug → Fixed |
| DREG-061 | Both registers are imported by `RegistersLoader` | MUST | Bug → Fixed |
| DREG-062 | `anonymization` schema is declared in the register | MUST | Bug → Fixed |

### REQ-DREG-02: Report Schema for Analysis Results (Priority: Must)

The `report` schema stores document analysis results including file metadata, entity detection, risk assessment, and processing status. All fields are ad-hoc (no schema enforcement); the field list below defines the expected pipeline contract.

#### Scenario: Create report for analyzed document

- **GIVEN** a document has been analyzed through the anonymization pipeline
- **WHEN** text extraction and entity detection complete
- **THEN** a report object is created with file metadata, detected entities, and risk score in the `document` register
- **AND** `status` is set to `"completed"`

#### Scenario: Report with critical risk level

- **GIVEN** a document contains 7 detected entities (5 PERSON, 2 ORGANIZATION)
- **WHEN** risk assessment runs
- **THEN** `riskScore` is calculated (e.g., `97.85`)
- **AND** `riskLevel` is set to `"Critical"`

#### Scenario: Report for failed processing

- **GIVEN** a document fails during text extraction
- **WHEN** the error is recorded
- **THEN** `status` is set to `"error"`
- **AND** `errorMessage` contains the failure description
- **AND** file metadata fields are preserved as available

#### Scenario: Schema has no enforced validation

- **GIVEN** the `report` schema has `properties: []` and `hardValidation: false`
- **WHEN** a report object is created with any combination of fields
- **THEN** all fields are accepted as ad-hoc data
- **AND** no schema-level validation error is raised

#### Scenario: File integrity tracking

- **GIVEN** a document is analyzed
- **WHEN** the report is created
- **THEN** `fileHash` contains an MD5 hash of the file content
- **AND** this can be used to verify file integrity between analysis runs

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-010 | Report schema exists with slug `"report"` | MUST | Implemented |
| DREG-010a | Report schema has `properties: []` and `hardValidation: false` | MUST | Implemented |
| DREG-011 | Report objects track file via `nodeId` and `filePath` | MUST | Implemented |
| DREG-012 | Report objects store file metadata: `fileName`, `fileType`, `fileExtension`, `fileSize` | MUST | Implemented |
| DREG-013 | Report objects track `status` and `errorMessage` | MUST | Implemented |
| DREG-014 | Report objects store risk assessment: `riskScore` and `riskLevel` | MUST | Implemented |
| DREG-015 | Report objects store detected entities as array of `{text, score, entityType}` | MUST | Implemented |
| DREG-016 | Report objects store extracted `text` content | MUST | Implemented |
| DREG-017 | Report objects store `fileHash` (MD5) for file integrity | MUST | Implemented |
| DREG-018 | Report objects have `anonymizationResults` field (reserved, empty array) | MUST | Implemented |

### REQ-DREG-04: Template Schema (Priority: Must)

The `template` schema is a placeholder for storing document templates within the document register.

#### Scenario: Template schema exists

- **GIVEN** the document register is loaded
- **WHEN** the `template` schema is inspected
- **THEN** it exists with slug `"template"` and version `"0.0.1"`
- **AND** it has `properties: []` and `hardValidation: false`

#### Scenario: Template schema is distinct from docudesk_register template

- **GIVEN** both registers are loaded
- **WHEN** template management features are used
- **THEN** the active template schema with properties is from `docudesk_register.json`
- **AND** the `document_register.json` template schema remains a placeholder

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-030 | Template schema exists with slug `"template"` | MUST | Implemented |
| DREG-031 | Template schema has `properties: []` | MUST | Implemented |
| DREG-032 | Template schema has `hardValidation: false` | MUST | Implemented |

### REQ-DREG-05: Entity Schema for Cross-Document Tracking (Priority: Must)

The `entity` schema enables tracking detected entities across multiple documents for consistent entity management.

#### Scenario: Entity schema exists

- **GIVEN** the document register is loaded
- **WHEN** the `entity` schema is inspected
- **THEN** it exists with slug `"entity"`
- **AND** its description references cross-document entity management
- **AND** it has `properties: []` and `hardValidation: false`

#### Scenario: Entity schema has no validation

- **GIVEN** the entity schema has `properties: []` and `hardValidation: false`
- **WHEN** entity objects are created with any fields
- **THEN** all fields are accepted as ad-hoc data

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-040 | Entity schema exists with slug `"entity"` | MUST | Implemented |
| DREG-041 | Entity description references cross-document entity management | MUST | Implemented |
| DREG-043 | Entity schema has `properties: []` | MUST | Implemented |
| DREG-044 | Entity schema has `hardValidation: false` | MUST | Implemented |

### REQ-DREG-06: Pre-Seeded Sample Objects (Priority: Must)

Three pre-seeded sample objects demonstrate the anonymization pipeline's output format and MUST be created when the register loads.

#### Scenario: Original document report sample is created on install

- **GIVEN** the document register is loaded via the repair step
- **WHEN** seed objects are processed
- **THEN** a report object with slug `report-test-ano-original` is created in the `document` register
- **AND** it shows a completed analysis of `test_ano.docx` (13,545 bytes)
- **AND** `riskScore` is `97.85` and `riskLevel` is `"Critical"`
- **AND** 7 entities are detected (5 PERSON, 2 ORGANIZATION)

#### Scenario: Anonymization result sample is created on install

- **GIVEN** the document register is loaded
- **WHEN** seed objects are processed
- **THEN** an anonymization object with slug `anonymization-test-ano-result` is created
- **AND** it contains a `replacements` map linking entity text to 8-char hex replacement tokens
- **AND** it references schema `"anonymization"` which is now defined in the register

#### Scenario: Anonymized document re-analysis sample is created on install

- **GIVEN** the document register is loaded
- **WHEN** seed objects are processed
- **THEN** a report object with slug `report-test-ano-anonymized` is created
- **AND** it shows analysis of the already-anonymized document
- **AND** `riskScore` is `77.2` and `riskLevel` is `"High"`
- **AND** this demonstrates the known NER limitation: replacement tokens are still detected as entities

#### Scenario: Seed objects are idempotent on re-import

- **GIVEN** the seed objects already exist from a previous install
- **WHEN** the repair step runs again
- **THEN** no duplicate seed objects are created
- **AND** existing seed objects are matched by their `slug` field and skipped

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-050 | Original document report sample (`report-test-ano-original`) loaded on install | MUST | Bug → Fixed |
| DREG-051 | Anonymization result sample (`anonymization-test-ano-result`) loaded on install | MUST | Bug → Fixed |
| DREG-052 | Anonymized document re-analysis sample (`report-test-ano-anonymized`) loaded on install | MUST | Bug → Fixed |

## SHOULD Requirements (Planned)

### REQ-DREG-03: Planned Report Features (Priority: Should)

Report objects include placeholder fields for future features: WCAG compliance, language level analysis, retention policy, and GDPR data controller tracking. These fields are NOT implemented in this change but are documented here to reserve their names.

#### Scenario: WCAG compliance checking (future)

- **GIVEN** a report object exists for a document
- **WHEN** WCAG compliance checking is implemented (future change)
- **THEN** `wcagComplianceResults` will contain accessibility findings
- **AND** results include WCAG level (A/AA/AAA) and specific violations

#### Scenario: Language level analysis (future)

- **GIVEN** a report object exists with extracted `text`
- **WHEN** language level analysis is implemented (future change)
- **THEN** `languageLevelResults` will classify text as B1/B2/C1 per CEFR
- **AND** readability metrics will be included

#### Scenario: Retention policy tracking (future)

- **GIVEN** a report object exists for a government document
- **WHEN** retention management is implemented (future change)
- **THEN** `retentionPeriod` specifies days to keep the document
- **AND** `retentionExpiry` is calculated as creation date + retention period
- **AND** `legalBasis` documents the legal authority (Archiefwet 1995)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-019 | `wcagComplianceResults` field for WCAG 2.1 AA accessibility | SHOULD | Planned |
| DREG-020 | `languageLevelResults` field for B1/B2/C1 CEFR analysis | SHOULD | Planned |
| DREG-021 | `retentionPeriod` and `retentionExpiry` for Archiefwet 1995 retention | SHOULD | Planned |
| DREG-022 | `legalBasis` for legal basis tracking | SHOULD | Planned |
| DREG-023 | `dataController` for GDPR AVG data controller assignment | SHOULD | Planned |

### REQ-DREG-05b: Cross-Document Entity Tracking (Priority: Should)

#### Scenario: Cross-document entity linking (future)

- **GIVEN** `"Ruben van der Linde"` is detected in 5 different documents
- **WHEN** cross-document entity management is implemented (future change)
- **THEN** a single entity record in the `entity` schema links all 5 document references
- **AND** updates to the entity (e.g. consent status) apply across all linked documents

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| DREG-033 | Template objects intended for document template storage (TBD) | SHOULD | Planned |
| DREG-042 | Cross-document entity tracking enables consistent entity management | SHOULD | Planned |

## Data Model

### Report Schema Fields (ad-hoc contract, not schema-enforced)

| Field | Type | Required | Description | Status |
|-------|------|----------|-------------|--------|
| nodeId | integer | Yes | Nextcloud file node ID | Implemented |
| filePath | string | Yes | Path to file in Nextcloud | Implemented |
| fileName | string | Yes | File name | Implemented |
| fileType | string | Yes | MIME type | Implemented |
| fileExtension | string | Yes | File extension | Implemented |
| fileSize | integer | Yes | File size in bytes | Implemented |
| status | string | Yes | `completed` \| `error` \| `processing` | Implemented |
| errorMessage | string/null | No | Error description if `status = "error"` | Implemented |
| riskScore | float | No | Privacy risk score (0.0–100.0) | Implemented |
| riskLevel | string | No | `Critical` \| `High` \| `Medium` \| `Low` \| `None` | Implemented |
| anonymizationResults | array | No | Reserved; always empty array in v1 | Implemented |
| entities | array | No | Detected entities: `[{text, score, entityType}]` | Implemented |
| fileHash | string | No | MD5 hash of file content (RFC 1321; SHA-256 recommended for future) | Implemented |
| text | string | No | Full extracted text content | Implemented |
| wcagComplianceResults | array | No | WCAG 2.1 AA findings (planned) | Planned |
| languageLevelResults | array | No | CEFR B1/B2/C1 language level (planned) | Planned |
| retentionPeriod | integer | No | Retention period in days (Archiefwet 1995) | Planned |
| retentionExpiry | datetime/null | No | Calculated retention expiry date | Planned |
| legalBasis | string/null | No | Legal basis for document (Woo, AVG, etc.) | Planned |
| dataController | string/null | No | GDPR data controller identifier | Planned |

### Entity (inline in report.entities)

| Field | Type | Description |
|-------|------|-------------|
| text | string | Detected entity text value |
| score | float | NER detection confidence (0.0–1.0) |
| entityType | string | Entity class: `PERSON`, `ORGANIZATION`, `LOCATION`, etc. |

### Anonymization Schema Fields (ad-hoc contract)

| Field | Type | Description |
|-------|------|-------------|
| sourceNodeId | integer | Nextcloud node ID of the original document |
| targetNodeId | integer | Nextcloud node ID of the anonymized output |
| sourceFilePath | string | Path to the original file |
| targetFilePath | string | Path to the anonymized file |
| replacements | object | Map of `{entityText: replacementToken}` (8-char hex) |
| status | string | Operation status: `completed` \| `error` |

## Dependencies

- **OpenRegister `ConfigurationService`**: Loads register definitions from JSON on install/repair
- **OpenRegister `ImportHandler`**: Idempotent register, schema, and object upsert
- **OpenRegister `ObjectService`**: CRUD operations for report and anonymization objects in runtime
- **`AnonymizationService`** (DocuDesk): Creates report objects during the analysis pipeline
- **`document_register.json`**: Source of truth for register/schema/seed structure

## Standards & References

- **GDPR/AVG (Verordening (EU) 2016/679)**: `dataController`, `legalBasis`, `retentionPeriod` fields
- **Wet open overheid (Woo)**: Document analysis supports publication decisions and grondslag tracking
- **WCAG 2.1 AA (ISO/IEC 40500:2012)**: Planned accessibility assessment via `wcagComplianceResults`
- **CEFR (Common European Framework of Reference for Languages)**: Planned B1/B2/C1 classification via `languageLevelResults`
- **Archiefwet 1995**: `retentionPeriod` and `retentionExpiry` fields for Dutch archival law
- **NEN 2082**: Dutch document management metadata standard
- **MD5 (RFC 1321)**: Current file integrity hashing — cryptographically weak for collision resistance; SHA-256 (FIPS 180-4) is the recommended upgrade
