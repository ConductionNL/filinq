## ADDED Requirements

### Requirement: Consolidated entity list endpoint
The system SHALL deduplicate entities across files.

#### Scenario: Retrieve consolidated entities
- **WHEN** GET /api/anonymization/batch/{batchId}/entities is called
- **THEN** deduplicated entities are returned with included flags

### Requirement: Confidence threshold filter
The system SHALL support configurable confidence thresholds.

#### Scenario: Apply confidence threshold
- **WHEN** minConfidence=0.7 parameter is provided
- **THEN** entities below 0.7 have included=false
