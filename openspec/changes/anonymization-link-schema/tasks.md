## 1. Register Config — Add anonymizationLink Schema

- [x] 1.1 Add `"anonymizationLink"` to the `document` register's `schemas` array in `lib/Settings/docudesk_register.json`
- [x] 1.2 Add the `anonymizationLink` schema object to `components.schemas` with all 12 properties, `hardValidation: true`, `searchable: true`, `configuration.objectNameField: "sourceFileName"`, `configuration.objectDescriptionField: "status"`, and `x-openregister-archival.retention: "P7Y"` (category: an explicit placeholder string `"TODO: confirm Archiefwet 1995 selectielijst category with selectielijst manager"` — do NOT invent a cat. number; action: destroy, responsibleParty: docudesk-privacy-officer)
- [x] 1.2a Make `status` a single-value enum `["anonymized"]` (this change records only successful runs — see task 2.9; the `failed` value is intentionally not used)
- [x] 1.3 Declare `sourceFileId` and `anonymizedFileId` with `"facetable": true` and `"type": "integer"`
- [x] 1.4 Declare `required: ["sourceFileId", "anonymizedFileId"]` on the schema
- [x] 1.5 Bump `info.version` from `"5.2.0"` to `"5.3.0"` in the JSON file header
- [x] 1.5a Bump the `document` register `version` `"1.0.0"` → `"1.1.0"` so OR's ImportHandler re-links the register's schemas array (version-gated)
- [x] 1.5b Use the OBJECT form for `x-openregister-archival.retention` (`{"default":"P7Y"}`) — OR's ArchivalAnnotationValidator (openregister#1614) rejects the bare string form
- [x] 1.6 Add four seed objects for `anonymizationLink` to `components.objects` (municipality/consultancy/travel-agency examples from design.md Seed Data section; all with `status: "anonymized"` — no `failed` example, since only successful runs are recorded)

## 2. AnonymizationService — Idempotent UPSERT

- [x] 2.1 Add a `@spec` annotation to the `anonymizeDocument` method PHPDoc referencing `openspec/changes/anonymization-link-schema/tasks.md#task-2`
- [x] 2.2 Add private method `recordAnonymizationLink(int $fileId, array $resultInfo): void` with full PHPDoc (param descriptions, return void, `@throws` suppressed — best-effort, `@spec` reference)
- [x] 2.3 Inside `recordAnonymizationLink`: resolve `OCA\OpenRegister\Service\ObjectService` via `$this->getOpenRegisterService(className: 'OCA\OpenRegister\Service\ObjectService')`
- [x] 2.4 Call `$objectService->searchObjects(query: ['@self' => ['register' => 'document', 'schema' => 'anonymizationLink'], 'sourceFileId' => $fileId])` and capture the first result as `$existing` (or `null` if empty)
- [x] 2.5 Build the object array: if `$existing` is not null, start from `$existing` (preserves `@self`) and set updated fields + `runCount: ($existing['runCount'] ?? 0) + 1`; otherwise build a fresh array with `sourceFileId`, all available metadata from `$resultInfo`, and `runCount: 1`. Always set `status: "anonymized"` (only successful runs reach this method)
- [x] 2.6 Call `$objectService->saveObject(object: $object, register: 'document', schema: 'anonymizationLink')` using named parameters
- [x] 2.7 Extract the saved object's id/uuid and assign to `$resultInfo['anonymizationLinkId']` before returning (method should return updated `$resultInfo` or use a ref — adjust signature to `array` return to match the pattern)
- [x] 2.8 Wrap the entire method body in try/catch(`\Throwable`); on exception: log `$this->logger->warning('recordAnonymizationLink failed', [...])` and return `$resultInfo` unchanged (no `anonymizationLinkId` key)
- [x] 2.9 In `anonymizeDocument`, after the existing `attachGrondslagenSummary` block (and inside the main try, on the SUCCESS path only — never in a catch / failure branch), call `$resultInfo = $this->recordAnonymizationLink(fileId: $fileId, resultInfo: $resultInfo)`. If a failure-status result can reach this point without throwing, guard the call so it runs only when an `anonymizedFileId` is present
- [x] 2.10 Verify `composer check:strict` passes (PHPCS Conduction sniffs including named-parameter rule for internal calls, PHPMD, Psalm, PHPStan)

## 3. Unit Tests

- [x] 3.1 Create `tests/unit/Service/AnonymizationLinkServiceTest.php` (or add to a new test class `AnonymizationServiceLinkTest.php` in the same directory) following the `ConsentServiceTest` mock pattern: mock `ContainerInterface`, `IAppManager`, `LoggerInterface`, and `OCA\OpenRegister\Service\ObjectService`
- [x] 3.2 Write test `testRecordAnonymizationLinkCreatesNewRecord`: `searchObjects` returns `[]`, assert `saveObject` is called once with an object containing `sourceFileId` matching input and `runCount: 1`, assert `anonymizationLinkId` is present in the returned `$resultInfo`
- [x] 3.3 Write test `testRecordAnonymizationLinkUpdatesExistingRecord`: `searchObjects` returns a stub record with `runCount: 1` and a populated `@self`, assert `saveObject` is called with `runCount: 2` and the new `anonymizedFileId` value, assert the existing `@self` is preserved in the saved object
- [x] 3.4 Write test `testRecordAnonymizationLinkIsBestEffortOnSaveFailure`: `saveObject` throws `RuntimeException`, assert no exception propagates from `recordAnonymizationLink`, assert returned `$resultInfo` does NOT contain key `anonymizationLinkId`
- [x] 3.5 Write test `testRecordAnonymizationLinkIsBestEffortOnSearchFailure`: `searchObjects` throws `RuntimeException`, assert no exception propagates, assert the method falls through to create (calls `saveObject` with `runCount: 1`) OR returns without save — document which branch is chosen
- [x] 3.6 Run unit tests in the Nextcloud container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml tests/unit/Service/AnonymizationLinkServiceTest.php` (adjust filename to actual) and confirm all pass

## 4. Seed Data Verification

- [x] 4.1 Confirm the four `anonymizationLink` seed objects are present in `components.objects` in `docudesk_register.json`
- [x] 4.2 Verify every seed object has `status: "anonymized"` (no `failed` records — only successful runs are persisted)
- [x] 4.3 Verify at least one object with `runCount > 1` is represented to demonstrate the re-anonymisation scenario

## 5. Quality Gate

- [x] 5.1 Run `composer check:strict` in the docudesk repo and confirm zero errors (PHPCS, PHPMD, Psalm, PHPStan)
- [x] 5.2 Verify `lib/Settings/docudesk_register.json` is valid JSON (`php -r "json_decode(file_get_contents('lib/Settings/docudesk_register.json')); echo json_last_error() === JSON_ERROR_NONE ? 'ok' : json_last_error_msg();"`)
- [x] 5.3 Confirm `info.version` in `docudesk_register.json` is `"5.3.0"`
- [ ] 5.4 Flag for the organisation's selectielijst manager: confirm the final `x-openregister-archival.category` for `anonymizationLink` and replace the placeholder string before this change is archived

## 6. Install / verify in running environment

- [x] 6.1 Install the v5.3.0 register config into the running NC (`master-nextcloud-1`): reset OR gate (`occ config:app:delete openregister imported_config_docudesk_version`) and boot docudesk; `anonymizationLink` schema created (id 24), linked to the `document` register, both ID fields facetable
- [x] 6.3 OR-annotation compat (approved scope expansion): convert ALL docudesk schemas to current OpenRegister annotation formats so the whole config imports — result: 13/13 schemas import cleanly. Fixes:
    - [x] 6.3a `x-openregister-archival.retention` string → object `{default}` on `correspondence` (P7Y), `batchCorrespondenceJob` (P1Y), `signingRequest`/`signerRecord`/`signingAuditEntry` (P10Y)
    - [x] 6.3b `x-openregister-lifecycle`: `signingRequest` `initialState` → `initial`; transition `from` string → array on `signingRequest`, `signingSession`, `batchCorrespondenceJob`
    - [x] 6.3c `x-openregister-notifications.trigger.type` `"lifecycle"` → `"transition"` on `batchCorrespondenceJob`
    - [x] 6.3d Register version bumps so OR re-links: `document` `1.0.0` → `1.2.0`, `signing` `1.1.0` → `1.2.0`
- [ ] 6.2 KNOWN LIMITATION (pre-existing, env): seed `components.objects` do NOT import at app-boot — OR's object import runs as user `Anonymous` during bootstrap and fails (`dossier` seeds are also absent for the same reason). Live anonymisation creates link objects with a real owner, so the feature is unaffected.
- [ ] 6.4 KNOWN OR QUIRK: register `schemas[]` arrays only re-link schemas created/updated within a single import run (existing same-version schemas are version-skipped and not re-added to the run's schemasMap). `document` links `anonymizationLink`; fully re-linking all registers would need a force-import or blanket schema-version bumps. Does not affect saveObject/search (resolved by slug).
