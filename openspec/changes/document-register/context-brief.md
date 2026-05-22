---
status: implemented
---

# Document Register

## Placement & Information Architecture

**Placement type:** `SETTING` — Setting under the app's Beheer/Admin/Configuration surface. Lives in the existing settings UI; no top-level menu entry.

**Lives at:** Beheer > Documentenregister / Beheer

**Rationale:** Register/schema admin  
_Source: /tmp/ia-doc-dec-cat-conn.md_

> **Implementation note for builders:** Respect the placement above. Do not promote this spec to a top-level menu item, sub-page, or new route unless the placement type explicitly says so. If the placement is `DETAIL_TAB`, `WIDGET`, `ACTION`, `SETTING`, or `INFRA`, the feature must NOT introduce a new entry in the app sidebar. When in doubt, ask before creating a new top-level surface.

## Purpose

Defines the data model for the `document` register used by DocuDesk to store document analysis results. This register is loaded from `lib/Settings/document_register.json` (separate from the consent-focused `docudesk_register.json`) and contains three schemas: `report` (analysis results), `template` (document templates), and `entity` (cross-document entity management). Pre-seeded sample objects demonstrate the anonymization pipeline's output format. Note: all three schemas have `properties: []` (empty) and `hardValidation: false`, meaning field definitions exist only on the sample objects as ad-hoc data, not as schema-enforced property definitions.

## Requirements

### REQ-DREG-01: Document Register Structure (Priority: Must)

A dedicated document register exists with three schemas for storing analysis results, templates, and entity tracking.

#### Scenario: Register creation from JSON
- GIVEN the document_register.json file exists in lib/Settings/
- WHEN the register is loaded into OpenRegister
- THEN a register with slug "document" and version "0.0.1" is created
- AND it contains three schemas: report, template, entity

#### Scenario: Separate from consent register
- GIVEN both document_register.json and docudesk_register.json exist
- WHEN the registers are inspected
- THEN they are separate registers with different purposes
- AND docudesk_register.json handles consent schemas
- AND document_register.json handles analysis/reporting schemas

#### Scenario: Register not auto-loaded on boot
- GIVEN DocuDesk boots and calls initialize()
- WHEN the initialization runs
- THEN only docudesk_register.json is imported
- AND document_register.json is NOT loaded automatically
- AND this is a known gap that needs resolution

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-001 | A `document` register exists with slug `document`, version `0.0.1` | MUST | Implemented |
| DREG-002 | The register contains three schemas: report, template, entity | MUST | Implemented |
| DREG-003 | The register is defined in `lib/Settings/document_register.json` | MUST | Implemented |
| DREG-004 | The JSON follows OpenAPI-like structure with components | MUST | Implemented |
| DREG-005 | The register is separate from docudesk_register.json | MUST | Implemented |

### REQ-DREG-02: Report Schema for Analysis Results (Priority: Must)

The report schema stores document analysis results including file metadata, entity detection, risk assessment, and processing status.

#### Scenario: Create report for analyzed document
- GIVEN a document has been analyzed through the anonymization pipeline
- WHEN text extraction and entity detection complete
- THEN a report object is created with file metadata, detected entities, and risk score
- AND the status is set to "completed"

#### Scenario: Report with critical risk level
- GIVEN a document contains 7 detected entities (5 PERSON, 2 ORGANIZATION)
- WHEN risk assessment runs
- THEN riskScore is calculated (e.g., 97.85)
- AND riskLevel is set to "Critical"

#### Scenario: Report for failed processing
- GIVEN a document fails during text extraction
- WHEN the error is recorded
- THEN status is set to "error"
- AND errorMessage contains the failure description
- AND other fields are preserved as available

#### Scenario: Schema has no enforced validation
- GIVEN the report schema has `properties: []` and `hardValidation: false`
- WHEN a report object is created with any fields
- THEN all fields are accepted as ad-hoc data
- AND no schema-level validation is applied

#### Scenario: File integrity tracking
- GIVEN a document is analyzed
- WHEN the report is created
- THEN fileHash contains an MD5 hash of the file content
- AND this can be used to verify file integrity

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-010 | Report schema stores analysis results with slug "report" | MUST | Implemented |
| DREG-010a | Report schema has empty properties and no validation | MUST | Implemented |
| DREG-011 | Report objects track file via nodeId and filePath | MUST | Implemented |
| DREG-012 | Report objects store file metadata: fileName, fileType, fileExtension, fileSize | MUST | Implemented |
| DREG-013 | Report objects track processing status and errorMessage | MUST | Implemented |
| DREG-014 | Report objects store risk assessment: riskScore and riskLevel | MUST | Implemented |
| DREG-015 | Report objects store detected entities as array of {text, score, entityType} | MUST | Implemented |
| DREG-016 | Report objects store extracted text content | MUST | Implemented |
| DREG-017 | Report objects store file integrity hash (MD5) | MUST | Implemented |
| DREG-018 | Report objects have anonymizationResults field (reserved) | MUST | Implemented |

### REQ-DREG-03: Planned Report Features (Priority: Should)

Report objects include placeholder fields for future features: WCAG compliance, language level analysis, retention policy, and GDPR data controller tracking.

#### Scenario: WCAG compliance checking (future)
- GIVEN a report object exists for a document
- WHEN WCAG compliance checking is implemented
- THEN wcagComplianceResults will contain accessibility findings
- AND results include WCAG level (A/AA/AAA) and specific violations

#### Scenario: Language level analysis (future)
- GIVEN a report object exists with extracted text
- WHEN language level analysis is implemented
- THEN languageLevelResults will classify text as B1/B2/C1 per CEFR
- AND readability metrics will be included

#### Scenario: Retention policy tracking (future)
- GIVEN a report object exists for a government document
- WHEN retention management is implemented
- THEN retentionPeriod specifies days to keep the document
- AND retentionExpiry is calculated as creation date + retention period
- AND legalBasis documents the legal authority

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-019 | wcagComplianceResults field for WCAG accessibility | SHOULD | Planned |
| DREG-020 | languageLevelResults field for B1/B2/C1 analysis | SHOULD | Planned |
| DREG-021 | retentionPeriod and retentionExpiry for document retention | SHOULD | Planned |
| DREG-022 | legalBasis for legal basis tracking | SHOULD | Planned |
| DREG-023 | dataController for GDPR data controller assignment | SHOULD | Planned |
| DREG-024 | Report schema has `hardValidation: false` for flexible usage | MUST | Implemented |

### REQ-DREG-04: Template Schema (Priority: Must)

The template schema provides a placeholder for storing document templates within the document register.

#### Scenario: Template schema exists
- GIVEN the document register is loaded
- WHEN the template schema is inspected
- THEN it exists with slug "template" and version "0.0.1"
- AND it has no defined properties (empty arrays)
- AND hardValidation is false

#### Scenario: Template schema is separate from docudesk_register template
- GIVEN both registers define template-related schemas
- WHEN template management is used
- THEN the active template schema is from docudesk_register.json (with properties)
- AND the document_register.json template schema is a placeholder

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-030 | Template schema exists with slug "template" | MUST | Implemented |
| DREG-031 | Template schema has no defined properties | MUST | Implemented |
| DREG-032 | Template schema has `hardValidation: false` | MUST | Implemented |
| DREG-033 | Template objects intended for document template storage (TBD) | SHOULD | Planned |

### REQ-DREG-05: Entity Schema for Cross-Document Tracking (Priority: Must)

The entity schema enables tracking detected entities across multiple documents for consistent entity management.

#### Scenario: Entity schema exists
- GIVEN the document register is loaded
- WHEN the entity schema is inspected
- THEN it exists with slug "entity" and description about cross-document entity management
- AND it has no defined properties yet

#### Scenario: Cross-document entity linking (future)
- GIVEN "Ruben van der Linde" is detected in 5 different documents
- WHEN cross-document entity management is implemented
- THEN a single entity record links all 5 document references
- AND updates to the entity (e.g., consent status) apply across all documents

#### Scenario: Entity schema has no validation
- GIVEN the entity schema has empty properties and hardValidation: false
- WHEN entity objects are created
- THEN any fields are accepted as ad-hoc data

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-040 | Entity schema exists with slug "entity" | MUST | Implemented |
| DREG-041 | Entity description references cross-document entity management | MUST | Implemented |
| DREG-042 | Cross-document entity tracking enables consistent management | SHOULD | Planned |
| DREG-043 | Entity schema has no defined properties yet | MUST | Implemented |
| DREG-044 | Entity schema has `hardValidation: false` | MUST | Implemented |

### REQ-DREG-06: Pre-Seeded Sample Objects (Priority: Must)

Three pre-seeded sample objects demonstrate the anonymization pipeline's output format.

#### Scenario: Original document report sample
- GIVEN sample object UUID 948f8498-b828-4d41-9b21-c54fc57d8703
- WHEN the sample is inspected
- THEN it shows a completed analysis of test_ano.docx (13,545 bytes)
- AND risk score is 97.85 / level "Critical"
- AND 7 entities detected (5 PERSON, 2 ORGANIZATION)
- AND text contains real names for demonstration

#### Scenario: Anonymization result sample
- GIVEN sample object UUID c04e1fa9-d20c-457d-8afa-011af9a16b7e
- WHEN the sample is inspected
- THEN it demonstrates the anonymization operation output
- AND it uses schema "anonymization" which is NOT in the defined schema list
- AND replacements map entities to random 8-char hex keys

#### Scenario: Anonymized document re-analysis sample
- GIVEN sample object UUID 685c5b5c-1b31-45a3-9b1e-58357dc5896d
- WHEN the sample is inspected
- THEN it shows analysis of the already-anonymized document
- AND risk score is 77.2 / level "High" (replacement tokens still detected)
- AND this demonstrates a known limitation of NER on anonymized text

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-050 | Original document report sample with 7 entities and Critical risk | MUST | Implemented |
| DREG-051 | Anonymization result sample with replacement mappings | MUST | Implemented |
| DREG-052 | Anonymized document re-analysis sample showing token detection limitation | MUST | Implemented |

### REQ-DREG-07: Register Loading Gap (Priority: Must)

The document_register.json is NOT loaded during application boot, which is a critical gap.

#### Scenario: Boot initialization skips document register
- GIVEN Application::boot() calls SettingsService::initialize()
- WHEN initialization runs
- THEN only docudesk_register.json is imported via ConfigurationService::importFromApp()
- AND document_register.json is never referenced in any code path
- AND the register, schemas, and sample objects are never created in OpenRegister

#### Scenario: Manual register loading required
- GIVEN the document register needs to be available
- WHEN an administrator wants to use document analysis features
- THEN the register must be loaded manually or through a separate mechanism
- AND this gap prevents the pipeline from storing analysis results

#### Scenario: Anonymization schema inconsistency
- GIVEN sample objects 2 and 3 use schema "anonymization"
- WHEN the register's schema list is inspected
- THEN only "report", "template", and "entity" schemas are defined
- AND "anonymization" is not a defined schema, creating an inconsistency

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-060 | document_register.json is NOT loaded during boot | MUST | Bug |
| DREG-061 | Only docudesk_register.json is imported by initialize() | MUST | Implemented |
| DREG-062 | "anonymization" schema used by samples but not defined in register | MUST | Bug |

## Data Model

### Report Schema Fields

| Field | Type | Required | Description | Status |
|-------|------|----------|-------------|--------|
| nodeId | integer | Yes | Nextcloud file node ID | Implemented |
| filePath | string | Yes | Path to file in Nextcloud | Implemented |
| fileName | string | Yes | File name | Implemented |
| fileType | string | Yes | MIME type | Implemented |
| fileExtension | string | Yes | File extension | Implemented |
| fileSize | integer | Yes | Size in bytes | Implemented |
| status | string | Yes | Processing status: completed, error, processing | Implemented |
| errorMessage | string/null | No | Error description if failed | Implemented |
| riskScore | float | No | Privacy risk score (0.0 - 100.0) | Implemented |
| riskLevel | string | No | Risk classification: Critical, High, Medium, Low, None | Implemented |
| anonymizationResults | array | No | Anonymization results (reserved) | Implemented (empty) |
| entities | array[Entity] | No | Detected entities with {text, score, entityType} | Implemented |
| wcagComplianceResults | array | No | WCAG accessibility results | Planned |
| languageLevelResults | array | No | Language level (B1/B2/C1) results | Planned |
| retentionPeriod | integer | No | Retention period in days | Planned |
| retentionExpiry | datetime/null | No | Retention expiry date | Planned |
| legalBasis | string/null | No | Legal basis for document | Planned |
| dataController | string/null | No | GDPR data controller | Planned |
| fileHash | string | No | MD5 hash for file integrity | Implemented |
| text | string | No | Full extracted text content | Implemented |

### Entity (inline in report)

| Field | Type | Description |
|-------|------|-------------|
| text | string | Detected entity text |
| score | float | Detection confidence (0.0 - 1.0) |
| entityType | string | Entity classification: PERSON, ORGANIZATION, LOCATION, etc. |

## Dependencies

- **OpenRegister ConfigurationService**: Loads register definitions from JSON
- **AnonymizationService**: Creates report objects during analysis
- **OpenRegister ObjectService**: CRUD operations on register objects
- **document_register.json**: Source of truth for register/schema structure

### Current Implementation Status
- **Partially implemented**:
  - `lib/Settings/document_register.json` -- JSON file exists with register, schemas, and samples
  - `lib/Settings/docudesk_register.json` -- separate consent register, loaded during boot
- **Critical gap**: document_register.json is NOT loaded during boot
- **Not yet implemented**: WCAG, language level, retention, entity schema properties

### Standards & References
- **GDPR/AVG**: Data controller, legal basis, retention fields
- **WOO**: Document analysis supports publication decisions
- **WCAG 2.1 AA (ISO 40500)**: Planned accessibility assessment
- **CEFR**: Planned language level classification
- **Archiefwet 1995**: Retention period and expiry
- **NEN 2082**: Dutch document management metadata standard
- **MD5 (RFC 1321)**: File integrity hashing (cryptographically weak; SHA-256 recommended)
