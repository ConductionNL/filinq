## MODIFIED Requirements

### Requirement: Document Anonymization with Entity Replacement (REQ-ANON-03)

Detected entities are replaced with anonymized placeholders in the document, producing an anonymized copy. The anonymization endpoint SHALL additionally accept optional `excludeTypes` and `minConfidence` parameters so callers can narrow which detected entities are replaced.

#### Scenario: Anonymize with entity type exclusion
- **WHEN** `excludeTypes=["ORGANIZATION"]` is provided to `POST /api/anonymization/anonymize/{fileId}`
- **THEN** ORGANIZATION entities are excluded from replacement
- **AND** all other detected entity types are still anonymized

#### Scenario: Anonymize with a minimum confidence threshold
- **WHEN** `minConfidence=0.7` is provided
- **THEN** entities whose detection confidence is below 0.7 are excluded from replacement

### Requirement: Text Extraction and Entity Detection (REQ-ANON-02)

Text is extracted from uploaded documents and entities are detected via the OpenRegister NER pipeline. The extraction response SHALL include a `riskLevel` field summarising the privacy risk of the detected entities.

#### Scenario: Extract with risk level
- **WHEN** extraction is performed via `POST /api/anonymization/extract/{fileId}`
- **THEN** the response includes a `riskLevel` field derived from the detected entities
