# Design: Unit Test Coverage 75%+

**Change name:** unit-test-coverage-75  
**Issue:** #26  
**Status:** pr-created

## Summary

Bring PHPUnit unit test coverage from effectively broken (fatal error on load) to 75%+ of PHP files having corresponding test files. This involved:

1. Fixing a critical stub regression (duplicate `OCP\IRequest` declaration breaking ALL tests)
2. Adding missing stubs for new Nextcloud API interfaces
3. Fixing pre-existing test failures caused by API drift (wrong constructor args, missing auth setup)
4. Writing new test files for all previously uncovered production classes

## Problem Statement

The test suite had a fatal PHP error on startup:
```
PHP Fatal error: Cannot declare interface OCP\IRequest, because the name is already in use
```

This caused 100% test failure rate. Additionally, many pre-existing tests had wrong mock setups due to production code API changes.

## Solution

### Stub architecture fix
- Removed duplicate OCP interface declarations from `OpenRegisterStubs.php`  
- Expanded `NextcloudStubs.php` with correct `JSONResponse` signature (`statusCode` named param)
- Added `GlobalStubs.php` for global-namespace stubs (`\OC` class used by `OCP\Server::get()`)
- Added missing stubs: `IDBConnection`, `IConfig`, `ICache`, `ICacheFactory`, `IJobList`, `QueuedJob`, `ITimeFactory`, `INotificationManager`, `App`, Bootstrap interfaces, `TextPlainResponse`, `ContainerInterface`, etc.

### Pre-existing test fixes
- `BatchStateServiceTest`: removed conflicting setUp stub, fixed callback return type
- `PdfControllerTest`: added missing `IUserSession` dependency, configured auth mock
- `MetricsControllerTest`: added missing `IAppConfig` dependency, separated config mocks
- `TemplatesControllerTest`: configured auth mock to return authenticated user
- `FolderBatchServiceTest`: added `getId()`, `getPermissions()`, `getRelativePath()` to Node/Folder stubs
- `CorrespondenceServiceTest`: fixed wrong argument count in `saveObject` mock expectation
- `RegisterDiscoveryServiceTest`: fixed `SchemaMapper::find()` to accept `mixed $id`

### New test files (12 production classes now covered)
- `BatchExtractionServiceTest`
- `BatchReportServiceTest`
- `BatchUploadServiceTest`
- `EntityConsolidationServiceTest`
- `SigningServiceTest`
- `WooProfileServiceTest`
- `CorrespondenceControllerTest`
- `HealthControllerTest`
- `MetricsCollectorTest`
- `PreferencesControllerTest`
- `PrintControllerTest`
- `BatchCorrespondenceJobTest`

### Newman collection
- Added `tests/newman/docudesk-api-collection.json` for API workflow tests

## Coverage Result

Before: 0/315 tests passing (fatal error)  
After: 315+ tests passing, ~84% file coverage (57/68 lib files)

## Declarative-vs-imperative decision

Not applicable — this is a test-only change with no new production services.
