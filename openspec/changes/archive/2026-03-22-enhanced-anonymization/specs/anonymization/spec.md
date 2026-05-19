## MODIFIED Requirements

### Requirement: Document Anonymization
Anonymization replaces detected entities with anonymized placeholders via `FileService::anonymizeDocument()`. Entity values shorter than 3 characters are skipped. Purely numeric entity values are skipped. Duplicate entity values are deduplicated before anonymization. Each entity is assigned a unique UUID key for the anonymization mapping. The anonymize endpoint SHALL additionally accept an optional `excludeTypes` array parameter to skip entities of specified types. The anonymize endpoint SHALL additionally accept an optional `minConfidence` float parameter to skip entities below the confidence threshold.

#### Scenario: Anonymize with entity type exclusion
- **WHEN** `POST /api/anonymization/anonymize/{fileId}` is called with entities and excludeTypes=["ORGANIZATION"]
- **THEN** entities of type ORGANIZATION are excluded from anonymization
- **AND** all other entity types are anonymized as normal

#### Scenario: Anonymize with confidence threshold
- **WHEN** `POST /api/anonymization/anonymize/{fileId}` is called with entities and minConfidence=0.8
- **THEN** only entities with confidence >= 0.8 are anonymized
- **AND** entities below 0.8 confidence are skipped

#### Scenario: Standard anonymize without new parameters
- **WHEN** `POST /api/anonymization/anonymize/{fileId}` is called without excludeTypes or minConfidence
- **THEN** all provided entities are anonymized as before (backward compatible)

### Requirement: Entity Extraction
Text extraction is performed via OpenRegister's `TextExtractionService::extractFile()`. Entity recognition runs during text extraction. Full entity details are retrieved via `EntityRelationMapper::findEntitiesForFile()`. Entities are normalized to a consistent format: `type`, `value`, `confidence`. The extract endpoint SHALL additionally return a `riskLevel` field from OpenRegister's RiskLevelService for the extracted file.

#### Scenario: Extract with risk level
- **WHEN** `POST /api/anonymization/extract/{fileId}` is called
- **THEN** the response includes entities array, entityCount, AND riskLevel
- **AND** riskLevel is obtained from OpenRegister's RiskLevelService

#### Scenario: Extract when RiskLevelService unavailable
- **WHEN** extraction succeeds but RiskLevelService throws RuntimeException
- **THEN** the response includes entities and entityCount with riskLevel="unknown"
- **AND** extraction is not blocked by risk level failure
