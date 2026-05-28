---
status: reviewed
---

# Document Register

## Purpose

Defines the data model for the `document` register used by DocuDesk to store document analysis results. This register is loaded from `lib/Settings/document_register.json` (separate from the consent-focused `docudesk_register.json`) and contains three schemas: `report` (analysis results), `template` (document templates), and `entity` (cross-document entity management). Pre-seeded sample objects demonstrate the anonymization pipeline's output format. Note: all three schemas have `properties: []` (empty) and `hardValidation: false`, meaning field definitions exist only on the sample objects as ad-hoc data, not as schema-enforced property definitions. Fields like wcagComplianceResults, languageLevelResults, retentionPeriod, etc. on the sample objects represent planned features that are not yet implemented.

## Requirements

### Document Register Structure

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-001 | A `document` register exists with slug `document`, version `0.0.1` | MUST | Implemented |
| DREG-002 | The document register contains three schemas: `report`, `template`, `entity` | MUST | Implemented |
| DREG-003 | The register is defined in `lib/Settings/document_register.json` (419 lines) | MUST | Implemented |
| DREG-004 | The register JSON follows OpenAPI-like structure with `components` containing `registers`, `schemas`, and `objects` | MUST | Implemented |
| DREG-005 | The register is separate from `docudesk_register.json` which handles consent schemas | MUST | Implemented |

### Report Schema

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-010 | The `report` schema stores document analysis results with slug `report`, version `0.0.1` | MUST | Implemented |
| DREG-010a | The report schema has `properties: []` and `required: []` (empty) -- all fields below exist only on sample objects as ad-hoc data, not as schema-enforced definitions | MUST | Implemented |
| DREG-011 | Report objects track the Nextcloud file via `nodeId` (integer file ID) and `filePath` (string) | MUST | Implemented |
| DREG-012 | Report objects store file metadata: `fileName`, `fileType` (MIME), `fileExtension`, `fileSize` | MUST | Implemented |
| DREG-013 | Report objects track processing status via `status` field (e.g., "completed") and `errorMessage` (null on success) | MUST | Implemented |
| DREG-014 | Report objects store risk assessment: `riskScore` (float 0-100) and `riskLevel` (string: "Critical", "High", etc.) | MUST | Implemented |
| DREG-015 | Report objects store detected entities as an array of `{text, score, entityType}` objects | MUST | Implemented |
| DREG-016 | Report objects store extracted text content in the `text` field | MUST | Implemented |
| DREG-017 | Report objects store a file integrity hash via `fileHash` (MD5) | MUST | Implemented |
| DREG-018 | Report objects have `anonymizationResults` array field (currently empty in samples -- reserved for future use) | MUST | Implemented |
| DREG-019 | Report objects have `wcagComplianceResults` array field -- planned feature for WCAG accessibility checking, not yet implemented | SHOULD | Planned |
| DREG-020 | Report objects have `languageLevelResults` array field -- planned feature for language level (B1/B2) analysis, not yet implemented | SHOULD | Planned |
| DREG-021 | Report objects have `retentionPeriod` (integer, days) and `retentionExpiry` (datetime, nullable) -- planned feature for document retention policy, not yet implemented | SHOULD | Planned |
| DREG-022 | Report objects have `legalBasis` (string, nullable) -- planned feature for legal basis tracking per document, not yet implemented | SHOULD | Planned |
| DREG-023 | Report objects have `dataController` (string, nullable) -- planned feature for GDPR data controller assignment, not yet implemented | SHOULD | Planned |
| DREG-024 | The schema has `hardValidation: false`, allowing flexible field usage without strict validation | MUST | Implemented |

### Template Schema

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-030 | The `template` schema exists with slug `template`, version `0.0.1` | MUST | Implemented |
| DREG-031 | The template schema has no defined properties (empty arrays for `required`, `properties`, `archive`) | MUST | Implemented |
| DREG-032 | The template schema has `hardValidation: false` | MUST | Implemented |
| DREG-033 | Template objects are intended for storing reusable document templates (structure TBD) | SHOULD | Planned |

### Entity Schema

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| DREG-040 | The `entity` schema exists with slug `entity`, version `0.0.1` | MUST | Implemented |
| DREG-041 | The entity schema description is "Stores detected entities across documents for consistent entity management" | MUST | Implemented |
| DREG-042 | Entity objects enable cross-document entity tracking -- the same person/organization detected in multiple documents can be linked to a single entity record | SHOULD | Planned |
| DREG-043 | The entity schema has no defined properties yet (empty arrays) -- schema fields TBD | MUST | Implemented |
| DREG-044 | The entity schema has `hardValidation: false` | MUST | Implemented |

## Data Model

### Report Schema Fields

| Field | Type | Required | Description | Status |
|-------|------|----------|-------------|--------|
| nodeId | integer | Yes | Nextcloud file node ID | Implemented |
| filePath | string | Yes | Path to the file in Nextcloud (e.g., `/admin/files/test.docx`) | Implemented |
| fileName | string | Yes | File name | Implemented |
| fileType | string | Yes | MIME type (e.g., `application/vnd.openxmlformats-officedocument.wordprocessingml.document`) | Implemented |
| fileExtension | string | Yes | File extension (e.g., `docx`) | Implemented |
| fileSize | integer | Yes | File size in bytes | Implemented |
| status | string | Yes | Processing status: `completed`, `error`, `processing` | Implemented |
| errorMessage | string/null | No | Error description if processing failed, null on success | Implemented |
| riskScore | float | No | Privacy risk score (0.0 - 100.0) | Implemented |
| riskLevel | string | No | Risk classification: `Critical`, `High`, `Medium`, `Low`, `None` | Implemented |
| anonymizationResults | array | No | Anonymization operation results (reserved) | Implemented (empty) |
| entities | array[Entity] | No | Detected entities with `{text, score, entityType}` | Implemented |
| wcagComplianceResults | array | No | WCAG accessibility compliance results | Planned |
| languageLevelResults | array | No | Language level (B1/B2) analysis results | Planned |
| retentionPeriod | integer | No | Retention period in days (0 = no retention) | Planned |
| retentionExpiry | datetime/null | No | Date when document retention expires | Planned |
| legalBasis | string/null | No | Legal basis for holding/publishing document | Planned |
| dataController | string/null | No | GDPR data controller responsible for document | Planned |
| fileHash | string | No | MD5 hash for file integrity verification | Implemented |
| text | string | No | Full extracted text content | Implemented |

### Entity (inline in report)

| Field | Type | Description |
|-------|------|-------------|
| text | string | The detected entity text (e.g., "Ruben van der Linde") |
| score | float | Detection confidence (0.0 - 1.0, from NER model) |
| entityType | string | Entity classification: `PERSON`, `ORGANIZATION`, `LOCATION`, etc. |

### Template Schema Fields

No properties defined yet. Schema is a placeholder for future template functionality.

### Entity Schema Fields

No properties defined yet. Schema is a placeholder for cross-document entity management.

## Pre-Seeded Sample Objects

The `document_register.json` includes 3 pre-seeded objects that demonstrate the anonymization pipeline output:

### Sample 1: Original Document Report (report schema)

- **UUID**: `948f8498-b828-4d41-9b21-c54fc57d8703`
- **Purpose**: Demonstrates a completed analysis of an original document
- **File**: `test_ano.docx` (13,545 bytes, nodeId 1089)
- **Risk**: Score 97.85 / Level "Critical" (multiple PERSON entities detected)
- **Entities**: 7 detected (5 PERSON, 2 ORGANIZATION), scores ranging 0.91-1.0
- **Text**: Contains real names ("Ruben van der Linde", "Remco Damhuis", "DocuDesk")

### Sample 2: Anonymization Result (anonymization schema -- not in defined schemas)

- **UUID**: `c04e1fa9-d20c-457d-8afa-011af9a16b7e`
- **Purpose**: Demonstrates the output of an anonymization operation
- **Note**: Uses schema `anonymization` which is NOT in the register's schema list (report/template/entity) -- this is stored ad-hoc by the pipeline
- **Fields**: `originalFileName`, `anonymizedFileName`, `anonymizedFilePath`, `replacements` array, `startTime`/`endTime`/`processingTime`, `status`, `message`
- **Replacements**: Maps each entity to a random 8-char hex key (e.g., `"Ruben van der Linde" -> "980100f4"`)

### Sample 3: Anonymized Document Report (anonymization schema)

- **UUID**: `685c5b5c-1b31-45a3-9b1e-58357dc5896d`
- **Purpose**: Demonstrates the analysis of an already-anonymized document
- **File**: `test_ano_anonymized.docx` (8,195 bytes, nodeId 1090)
- **Risk**: Score 77.2 / Level "High" (replacement keys still detected as entities)
- **Text**: Contains placeholder tokens like `[PERSON: 655f2366]` and `[ORGANIZATION: 60a9f7f0]`
- **Observation**: The NER model detects replacement tokens ("PERSON", "ORGANIZATION", hex keys) as entities, inflating the risk score of anonymized documents

## Scenarios

### Register Initialization

```
GIVEN DocuDesk is installed with the document_register.json file
WHEN the register is loaded by the configuration system
THEN the "document" register is created with report, template, and entity schemas
AND the pre-seeded sample objects are available for reference
```

### Analyze Original Document

```
GIVEN a user uploads a document for analysis
WHEN text extraction and entity detection complete
THEN a report object is created in the document register
AND it contains the extracted text, detected entities, risk score, and file metadata
AND wcagComplianceResults, languageLevelResults, retentionPeriod are initialized to empty/zero
```

### Anonymized Document Re-Analysis

```
GIVEN a document has been anonymized (entities replaced with [TYPE: key] tokens)
WHEN the anonymized document is re-analyzed
THEN the NER model detects the replacement tokens as entities
AND the risk score is lower than the original but still elevated
AND this is a known limitation of the current pipeline
```

### Planned Features: WCAG Compliance

```
GIVEN a report object exists for a document
WHEN WCAG compliance checking is implemented (future)
THEN the wcagComplianceResults field will be populated with accessibility findings
AND the results will include WCAG level (A/AA/AAA) and specific violations
```

### Planned Features: Language Level Analysis

```
GIVEN a report object exists with extracted text
WHEN language level analysis is implemented (future)
THEN the languageLevelResults field will contain readability metrics
AND the results will classify text as B1/B2/C1 per CEFR framework
```

### Planned Features: Retention Policy

```
GIVEN a report object exists for a document
WHEN retention management is implemented (future)
THEN retentionPeriod will specify how long to keep the document (in days)
AND retentionExpiry will be calculated as creation date + retention period
AND legalBasis will document the legal authority for retention
AND dataController will identify the GDPR-responsible party
```

## Dependencies

- **OpenRegister ConfigurationService**: Loads register definitions from JSON
- **AnonymizationService**: Creates report objects during the analysis pipeline
- **OpenRegister ObjectService**: CRUD operations on register objects
- **document_register.json**: Source of truth for register/schema structure and sample data
