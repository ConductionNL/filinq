## MODIFIED Requirements

### Requirement: Document Anonymization
Extended to support excludeTypes and minConfidence parameters.

#### Scenario: Anonymize with entity type exclusion
- **WHEN** excludeTypes=["ORGANIZATION"] is provided
- **THEN** ORGANIZATION entities are excluded

### Requirement: Entity Extraction
Extended to include riskLevel in response.

#### Scenario: Extract with risk level
- **WHEN** extraction is performed
- **THEN** response includes riskLevel field
