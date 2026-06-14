## Why

After a successful anonymisation run, DocuDesk has no persistent record linking the original (source) file to the anonymised output file. This means neither operators nor downstream systems can answer "what is the anonymised version of file X?" or "what is the source of anonymised file Y?" without re-running analysis. A durable, searchable mapping record stored in OpenRegister enables bidirectional lookup, idempotent re-anonymisation (the same source always updates a single record), and an audit trail with run statistics.

## What Changes

- **New schema `anonymizationLink`** added to the existing `document` register in `lib/Settings/docudesk_register.json`. The schema records `sourceFileId`, `anonymizedFileId`, path/name metadata, output format, status, replacement count, run count, timestamp, and operator id. Both `sourceFileId` and `anonymizedFileId` are `facetable: true` to power OR search API queries in both directions.
- **Register config version bump** `5.2.0` → `5.3.0` so `SettingsInitializer::initialize()` (gated on `version_compare`) re-imports the config on next boot.
- **Idempotent UPSERT in `AnonymizationService::anonymizeDocument`**: at the end of every successful anonymisation run a new private method `recordAnonymizationLink` searches for an existing record keyed on `sourceFileId`. If found it updates the target fields and increments `runCount`; if not found it creates a new record with `runCount: 1`. The operation is best-effort (try/catch, log-warning on failure), never aborts the response, and surfaces `anonymizationLinkId` in `$resultInfo`.
- **Bidirectional query contract documented** in spec: query `sourceFileId` to resolve the anonymised file for a given source; query `anonymizedFileId` to resolve the source for a given anonymised file.
- **OpenRegister annotation-format compatibility (scope expansion, approved during install)**: the existing docudesk schemas could not import into current OpenRegister because their annotations used outdated shapes. Migrated the whole config so all 13 schemas import: `x-openregister-archival.retention` string → object `{default}` (`anonymizationLink`, `correspondence`, `batchCorrespondenceJob`, `signingRequest`, `signerRecord`, `signingAuditEntry`); `x-openregister-lifecycle` `initialState` → `initial` and transition `from` string → array (`signingRequest`, `signingSession`, `batchCorrespondenceJob`); notification `trigger.type` `"lifecycle"` → `"transition"` (`batchCorrespondenceJob`); register version bumps `document` `1.0.0`→`1.2.0` and `signing` `1.1.0`→`1.2.0` so OR re-links schemas.

## Capabilities

### New Capabilities

- `anonymization-link`: The source↔anonymised file mapping data model, the `AnonymizationService` idempotent-upsert behaviour, and the bidirectional query contract via OR search API facets.

### Modified Capabilities

- `document-register`: The `document` register gains the `anonymizationLink` schema (config version 5.2.0 → 5.3.0).

## Impact

- **`lib/Settings/docudesk_register.json`**: new `anonymizationLink` schema + register version bump.
- **`lib/Service/AnonymizationService.php`**: new private method `recordAnonymizationLink`; `anonymizeDocument` calls it after the existing optional post-steps.
- **`tests/unit/Service/AnonymizationServiceTest.php`** (or a new sibling): unit tests for the upsert (found → update path, not-found → create path).
- **No new PHP dependencies**: reuses `getOpenRegisterService()` + `OCA\OpenRegister\Service\ObjectService` already used by `ConsentService` and `CorrespondenceService`.
- **No API surface change**: `anonymizationLinkId` is added to the `anonymizeDocument` response body; callers that ignore unknown fields are unaffected.
- **No database migrations**: all persistence goes through OpenRegister ObjectService.

## Future Work

- **Pre-run mapping lookup + stale-target reconciliation**: before starting a new anonymisation run, look up the existing `anonymizationLink` record (if any) and check whether the previously recorded `anonymizedFilePath` still exists. If the source file was moved or renamed between runs, OR's `DocumentProcessingHandler` hard-codes `<basename>_anonymized.<ext>` in the source's current parent folder and deletes + recreates it — the prior anonymised NC fileId is orphaned and the old path is stale. A future change (dependent on an OpenRegister feature that allows callers to specify an alternative output location/name) will clean up orphaned files. The current change is upsert-only and relies on OR's deterministic `_anonymized` naming for the file-level overwrite.
