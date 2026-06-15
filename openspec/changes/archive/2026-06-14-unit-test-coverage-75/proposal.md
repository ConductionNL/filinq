# Unit Test Coverage 75%+ and Newman API Tests

## Summary

Bring PHPUnit unit test coverage from 15% (6/40 files) to 75%+ and add Newman/Postman API test collection for workflow validation.

## Current State

- **PHP files:** 40
- **Test files:** 6 (15% coverage)
- **Services:** 21 total
- **Controllers:** 11 total

## Demand Evidence

- OpenSpec verify skill requires tests for all new code
- 97,819 tender requirements reference quality assurance and testing
- BIO/ISO 27001 compliance requires demonstrated test coverage
- GIBIT ICT quality norms mandate automated testing

## Scope

### In Scope

1. **PHPUnit tests** for all untested services (3-5 test methods each):

- AnonymizationResultParser
- AnonymizationService
- ConsentService
- ConsentUpdateHandler
- DocumentTextExtractor
- EntityDetectionService
- FileEntityStatsService
- FileListingService
- FileUploadService
- MetadataService
- ObjectionDeadlineChecker
- OpenRegisterResolver
- PdfService
- RegisterDiscoveryService
- SettingsInitializer
- TemplateRenderer

2. **PHPUnit tests** for all untested controllers:

- AnonymizationController
- ConsentController
- DashboardController
- HealthController
- MetadataController
- MetricsCollector
- PdfController
- SettingsController
- TemplateRequestHandler
- TemplatesController

3. **Newman/Postman collection** for API workflow testing:
   - CRUD operations for all main entities
   - Workflow validation (status transitions, business rules)
   - Error handling (400, 403, 404, 500 responses)
   - Environment variables for base URL and authentication

### Out of Scope

- Integration tests (require running Nextcloud instance)
- Frontend (Jest/Vitest) tests
- Performance/load testing

## Acceptance Criteria

- GIVEN the test suite WHEN `composer test` runs THEN 75%+ of PHP files have corresponding test files
- GIVEN a Newman collection WHEN `newman run` executes THEN all API workflows complete successfully
- GIVEN any service class WHEN its test runs THEN constructor, main methods, and error paths are covered
- GIVEN any controller WHEN its test runs THEN request handling and response codes are verified

## Dependencies

- PHPUnit (already configured in phpunit.xml)
- Newman/Postman (npm install -g newman)
- Existing test patterns in tests/Unit/
