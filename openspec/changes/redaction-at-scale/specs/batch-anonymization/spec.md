# batch-anonymization Specification (delta)

---
status: proposed
---

## Purpose

Scales the batch anonymize step for municipal volume: above the
synchronous threshold the anonymize endpoint enqueues a background batch
(processed per `redaction-at-scale` REQ-DDRAS-002) instead of looping over
every file inside one HTTP request. Below the threshold behaviour is
unchanged.

## MODIFIED Requirements

### Requirement: Batch anonymization
The system SHALL anonymize all extracted files in a batch via `POST /api/anonymization/batch/{batchId}/anonymize`. The request body SHALL include an `entities` array of entity values/types to anonymize (from the review step). The system SHALL apply the entity list to each file using OpenRegister's FileService::anonymizeDocument(). Per GDPR Article 4(5), entity replacements SHALL use unique UUID pseudonyms per entity value (consistent across all files in the batch). Batches whose file count exceeds the synchronous cap (`filinq.batch.max_files_per_run`, default 100) SHALL NOT be anonymized in-request: the endpoint SHALL persist the reviewed entity list on the batch job object, transition the batch to `anonymizing`, and return HTTP 202 with the batch progress location; the files are then processed in background work units (see `redaction-at-scale` REQ-DDRAS-002), preserving the same per-file anonymization semantics and pseudonym consistency across the whole batch. Batches at or below the cap SHALL keep the existing synchronous behaviour and response shape.

#### Scenario: Anonymize batch with reviewed entities
- **WHEN** `POST /api/anonymization/batch/{batchId}/anonymize` is called with 10 selected entities
- **THEN** each extracted file in the batch is anonymized using the provided entity list
- **AND** each file's status updates to "anonymized" with replacementCount
- **AND** the same entity value receives the same UUID pseudonym across all files
- **AND** the batch status changes to "completed"

#### Scenario: Anonymize batch with empty entity list
- **WHEN** the entities array is empty
- **THEN** the system returns HTTP 400 with error "No entities provided for anonymization"

#### Scenario: Skip error files during batch anonymization
- **WHEN** a batch contains files with status "error" from extraction
- **THEN** those files are skipped during anonymization
- **AND** the response includes a skippedFiles array listing the skipped file IDs and reasons

#### Scenario: Large batch is enqueued instead of processed in-request (REQ-DDRAS-009)
- **GIVEN** a review-status batch of 480 files (above the synchronous cap of 100)
- **WHEN** `POST /api/anonymization/batch/{batchId}/anonymize` is called with the reviewed entity list
- **THEN** the system returns HTTP 202 with the batch progress location
- **AND** the reviewed entity list is persisted on the batch job object
- **AND** the files are anonymized in background work units with the same pseudonym consistency across the batch
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: Small batch keeps the synchronous contract
- **GIVEN** a review-status batch of 20 files
- **WHEN** the anonymize endpoint is called
- **THEN** the files are anonymized in-request and the pre-change response shape is returned
- @e2e exclude backwards-compatible API contract; covered by PHPUnit (tests/unit/Controller/BatchAnonymizationControllerTest.php) and Newman batch contracts
