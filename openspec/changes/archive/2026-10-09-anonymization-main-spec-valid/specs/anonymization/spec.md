## MODIFIED Requirements

### Requirement: File Input via OR File Attachments (REQ-ANON-00)

@e2e exclude Backend FileUploadService persistence as OR file attachment and OR virus-scan-hook rejection (HTTP 422) — service/contract behaviour, no UI assertion. Covered by PHPUnit (FileUploadService) and Newman (upload endpoint).

**Priority:** MUST

Uploaded files MUST be stored as OR File Attachments, not by filinq-specific storage code. Virus-scan and MIME-validation hooks are inherited from OR.

#### Scenario: File persisted as OR File Attachment

- **GIVEN** a logged-in user uploads a file for anonymization
- **WHEN** `FileUploadService::upload()` stores the file
- **THEN** the file SHALL be persisted as an OR file attachment (not raw Nextcloud file API only)
- **AND** OR's MIME-validation and virus-scan hooks SHALL execute before the file is accepted
- **AND** the response includes the OR attachment `fileId` usable as a lookup key

#### Scenario: Virus-scan rejected file

- **GIVEN** a file triggers OR's virus-scan hook
- **WHEN** the hook returns `BLOCKED`
- **THEN** the upload SHALL be rejected with HTTP 422 — "File rejected by security scan"
- **AND** no filinq record SHALL reference the rejected file

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-000 | Files persisted as OR File Attachments | MUST | Apply-phase |
| ANON-000a | MIME-validation and virus-scan hooks execute on ingest | MUST | Apply-phase |
| ANON-000b | Upload response uses OR attachment fileId as lookup key | MUST | Apply-phase |

### Requirement: Anonymization Confidence is a Calculation (REQ-ANON-CAL)

@e2e exclude Backend x-openregister-calculations derivation (riskLevel/redactionCoverage computed by OR, not written by AnonymizationService) — no browser surface. Covered by PHPUnit (calculation expressions) and OR calculation integration tests.

**Priority:** MUST

`anonymizationConfidence`, `riskScore`, `riskLevel`, `entityDensity`, and `redactionCoverage` are declared as `x-openregister-calculations` on the file-attachment extension schema. `AnonymizationService` SHALL NOT write these fields directly; OR derives them from the calculation expression after entity detection completes.

#### Scenario: Risk level derived, not written

- **GIVEN** `x-openregister-calculations.riskLevel` is declared on the file-attachment schema
- **WHEN** entity detection completes and entity objects are persisted
- **THEN** `riskLevel` SHALL be derived by OR from the entity array
- **AND** `AnonymizationService::detectEntities()` SHALL NOT contain a `$result['riskLevel'] = ...` write

#### Scenario: Redaction coverage computed after anonymization

- **GIVEN** `x-openregister-calculations.redactionCoverage` is declared
- **WHEN** anonymization finishes and the anonymized file is stored
- **THEN** `redactionCoverage` SHALL equal `anonymizedEntityCount / totalEntityCount`
- **AND** this value SHALL be available on the file-attachment object without a service write

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| ANON-CAL-001 | `x-openregister-calculations` declared for: anonymizationConfidence, riskScore, riskLevel, entityDensity, redactionCoverage | MUST | Implementing |
| ANON-CAL-002 | AnonymizationService does not contain direct writes to calculated fields | MUST | Apply-phase |
| ANON-CAL-003 | OR PR raised if file-attachment schema is upstream and needs the extension | SHOULD | Apply-phase |
