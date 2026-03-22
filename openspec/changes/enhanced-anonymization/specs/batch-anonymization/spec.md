## ADDED Requirements

### Requirement: Batch creation via multi-file upload
The system SHALL accept multiple files in a single upload request.

#### Scenario: Upload multiple files as a batch
- **WHEN** user uploads 5 files to POST /api/anonymization/batch/upload
- **THEN** system creates a batch with unique batchId and stores all files

### Requirement: Sequential batch extraction
The system SHALL extract entities from each file sequentially.

#### Scenario: Extract next file in batch
- **WHEN** POST /api/anonymization/batch/{batchId}/extract is called
- **THEN** the next unextracted file is processed

### Requirement: Batch anonymization
The system SHALL anonymize all extracted files with a shared entity list.

#### Scenario: Anonymize batch
- **WHEN** POST /api/anonymization/batch/{batchId}/anonymize is called with entities
- **THEN** each extracted file is anonymized

### Requirement: Batch completion report
The system SHALL generate a CSV audit report.

#### Scenario: Download batch report
- **WHEN** GET /api/anonymization/batch/{batchId}/report is called
- **THEN** a CSV file is returned

### Requirement: WOO entity category profiles
The system SHALL support pre-configured entity category profiles.

#### Scenario: Retrieve default WOO profile
- **WHEN** GET /api/anonymization/profiles is called
- **THEN** the default WOO profile is returned
