# anonymization-link Specification

## Purpose
TBD - created by archiving change anonymization-link-schema. Update Purpose after archive.
## Requirements
### Requirement: AnonymizationLink Schema — Data Model (REQ-ALINK-01)

The `document` register SHALL contain an `anonymizationLink` schema that records the mapping between a source NC file and its anonymised counterpart. The schema SHALL declare full `required`, `properties`, and `hardValidation: true` per OR Adoption Decision 3 (document-register spec). `sourceFileId` and `anonymizedFileId` are required fields and SHALL be declared `facetable: true` to enable OR search API faceted filtering in both directions.

| Field | Type | Required | Facetable | Description |
|-------|------|----------|-----------|-------------|
| sourceFileId | integer | Yes | Yes | NC file ID of the original (unanonymised) document — idempotency key |
| sourceFileName | string | No | No | Filename of the source document |
| sourceFilePath | string | No | No | Full path of the source document within Nextcloud |
| anonymizedFileId | integer | Yes | Yes | NC file ID of the anonymised output — reverse-lookup key |
| anonymizedFileName | string | No | No | Filename of the anonymised output |
| anonymizedFilePath | string | No | No | Full path of the anonymised output within Nextcloud |
| outputFormat | string (enum: pdf/docx/odt/txt/html) | No | No | Output format used |
| status | string (enum: anonymized) | No | No | Outcome of the run. This change records only successful runs, so the value is always `anonymized`; failed runs are not persisted as link records. |
| replacementCount | integer | No | No | Number of entity replacements applied |
| runCount | integer | No | No | How many times this source file has been anonymised |
| anonymizedAt | string (date-time) | No | No | ISO 8601 timestamp of the anonymisation run |
| anonymizedBy | string (maxLength 64) | No | No | Nextcloud user ID of the operator who triggered the run |

#### Scenario: Schema passes strict validation

- **WHEN** an `anonymizationLink` object is written without `sourceFileId`
- **THEN** OR's validator SHALL reject the write with a validation error
- **AND** no record SHALL be persisted

#### Scenario: Both ID fields are facetable

- **WHEN** the `anonymizationLink` schema is imported via `SettingsInitializer`
- **THEN** both `sourceFileId` and `anonymizedFileId` SHALL have `"facetable": true` in the persisted schema
- **AND** an OR search with `sourceFileId = <value>` SHALL return matching records
- **AND** an OR search with `anonymizedFileId = <value>` SHALL return matching records

#### Scenario: Register version bump triggers re-import

- **WHEN** `SettingsInitializer::initialize()` runs after the `anonymizationLink` schema is added
- **THEN** it SHALL detect `info.version "5.3.0" > stored "5.2.0"` via `version_compare`
- **AND** it SHALL call `ConfigurationService::importFromApp()` to persist the updated config
- **AND** the `anonymizationLink` schema SHALL be available in the `document` register

### Requirement: Idempotent UPSERT on Successful Anonymisation (REQ-ALINK-02)

After a successful anonymisation run (after `$resultInfo` is built), `AnonymizationService::anonymizeDocument` SHALL call a private method `recordAnonymizationLink` that performs an idempotent UPSERT keyed on `sourceFileId`.

- On **first run**: `searchObjects` finds no existing record → `saveObject` creates a new record with `runCount: 1`.
- On **subsequent run**: `searchObjects` finds an existing record → the `@self` is preserved and `saveObject` updates the record; `runCount` is incremented by 1, `anonymizedFileId`, `anonymizedFileName`, `anonymizedFilePath`, `anonymizedAt`, and `replacementCount` are overwritten with the new run's values.

The method SHALL use `OCA\OpenRegister\Service\ObjectService` retrieved via `getOpenRegisterService()`. The UPSERT SHALL be best-effort: any exception is caught, logged at `warning` level, and does NOT abort or modify the anonymisation HTTP response.

`recordAnonymizationLink` SHALL be invoked ONLY on the success path — i.e. only when an anonymised file was produced and `anonymizedFileId` is known. Runs that fail (whether they throw or return a failure-status result without throwing) SHALL NOT create or update a link record. Consequently every persisted record carries `status: "anonymized"` and a valid `anonymizedFileId`, which is why `anonymizedFileId` can remain `required` under `hardValidation: true`.

#### Scenario: First anonymisation creates a new link record

- **GIVEN** no `anonymizationLink` record exists with `sourceFileId = 42`
- **WHEN** `anonymizeDocument` completes successfully for file 42
- **THEN** `recordAnonymizationLink` SHALL call `objectService->searchObjects` with `['@self' => ['register' => 'document', 'schema' => 'anonymizationLink'], 'sourceFileId' => 42]`
- **AND** finding no result it SHALL call `objectService->saveObject` with `runCount: 1` and `sourceFileId: 42`
- **AND** `$resultInfo` SHALL contain an `anonymizationLinkId` key with the saved object's id

#### Scenario: Re-anonymisation updates existing record and increments runCount

- **GIVEN** an `anonymizationLink` record exists for `sourceFileId = 42` with `runCount: 1`
- **WHEN** `anonymizeDocument` completes successfully for file 42 a second time
- **THEN** `recordAnonymizationLink` SHALL find the existing record via `searchObjects`
- **AND** it SHALL preserve the existing `@self` in the object passed to `saveObject`
- **AND** the saved record SHALL have `runCount: 2`
- **AND** `anonymizedFileId`, `anonymizedFileName`, `anonymizedFilePath`, `anonymizedAt`, `replacementCount` SHALL reflect the new run's values

#### Scenario: Link failure is non-fatal

- **GIVEN** `objectService->saveObject` throws an exception
- **WHEN** `anonymizeDocument` is called
- **THEN** the exception SHALL be caught inside `recordAnonymizationLink`
- **AND** a warning SHALL be logged
- **AND** the `anonymizeDocument` return value SHALL NOT contain an `anonymizationLinkId` key
- **AND** the HTTP response status and all other result fields SHALL be unchanged

#### Scenario: Link is NOT written at analysis time

- **GIVEN** `extractAndDetectEntities` is called for file 42
- **WHEN** it completes successfully
- **THEN** no `anonymizationLink` record SHALL be created or updated in OR
- **AND** `recordAnonymizationLink` SHALL NOT be called

#### Scenario: Link is NOT written for a failed run

- **GIVEN** an anonymisation run for file 42 fails (throws, or returns a failure-status result)
- **WHEN** `anonymizeDocument` handles that outcome
- **THEN** no `anonymizationLink` record SHALL be created or updated in OR
- **AND** `$resultInfo` SHALL NOT contain an `anonymizationLinkId` key

### Requirement: Bidirectional Lookup via OR Search API (REQ-ALINK-03)

The `anonymizationLink` schema SHALL support two query directions via OR's standard search API, enabled by the `facetable: true` declarations on `sourceFileId` and `anonymizedFileId`.

**Forward query** (source → anonymised): retrieve the anonymised file for a given source file.

```
GET /api/objects?register=document&schema=anonymizationLink&sourceFileId=<NC_FILE_ID>
```

**Reverse query** (anonymised → source): retrieve the source file for a given anonymised file.

```
GET /api/objects?register=document&schema=anonymizationLink&anonymizedFileId=<NC_FILE_ID>
```

#### Scenario: Forward lookup resolves anonymised file for a source

- **GIVEN** an `anonymizationLink` record exists with `sourceFileId: 42` and `anonymizedFileId: 99`
- **WHEN** the OR search API is queried with `register=document&schema=anonymizationLink&sourceFileId=42`
- **THEN** the response SHALL contain the link record
- **AND** `anonymizedFileId` SHALL equal `99`

#### Scenario: Reverse lookup resolves source for an anonymised file

- **GIVEN** an `anonymizationLink` record exists with `sourceFileId: 42` and `anonymizedFileId: 99`
- **WHEN** the OR search API is queried with `register=document&schema=anonymizationLink&anonymizedFileId=99`
- **THEN** the response SHALL contain the link record
- **AND** `sourceFileId` SHALL equal `42`

#### Scenario: Lookup returns empty when no link exists

- **GIVEN** no `anonymizationLink` record exists for `sourceFileId: 777`
- **WHEN** the OR search API is queried with `sourceFileId=777`
- **THEN** the response SHALL return an empty results array

### Requirement: Unit Test Coverage for UPSERT Logic (REQ-ALINK-04)

Per ADR-009, the `recordAnonymizationLink` logic SHALL have unit test coverage of at least 75%. Tests SHALL follow the `tests/unit/Service/` pattern and mock `OCA\OpenRegister\Service\ObjectService` using PHPUnit MockObject.

#### Scenario: Test covers the found→update path (runCount incremented)

- **WHEN** `searchObjects` mock returns an existing record with `runCount: 1`
- **THEN** the test SHALL assert that `saveObject` is called with an object containing `runCount: 2`
- **AND** the test SHALL assert that `anonymizedFileId` in the saved object reflects the new run

#### Scenario: Test covers the not-found→create path

- **WHEN** `searchObjects` mock returns an empty array
- **THEN** the test SHALL assert that `saveObject` is called with an object containing `runCount: 1`
- **AND** the test SHALL assert that `sourceFileId` in the saved object matches the input file ID

#### Scenario: Test covers the best-effort failure path

- **WHEN** `saveObject` mock throws a `RuntimeException`
- **THEN** the test SHALL assert that `anonymizeDocument` does NOT throw
- **AND** the test SHALL assert that the returned `$resultInfo` does NOT contain `anonymizationLinkId`

