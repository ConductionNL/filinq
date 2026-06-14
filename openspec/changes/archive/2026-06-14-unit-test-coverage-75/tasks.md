# Tasks: Unit Test Coverage 75%+

## Phase 1: Fix broken test infrastructure

- [x] 1.1 Fix duplicate OCP\IRequest declaration in OpenRegisterStubs.php
- [x] 1.2 Fix duplicate OCP\IL10N declaration in OpenRegisterStubs.php
- [x] 1.3 Fix duplicate OCP\AppFramework\Http\JSONResponse in OpenRegisterStubs.php
- [x] 1.4 Fix duplicate OCP\AppFramework\Controller in OpenRegisterStubs.php
- [x] 1.5 Create GlobalStubs.php with \OC global class stub
- [x] 1.6 Update bootstrap-unit.php to load GlobalStubs.php first
- [x] 1.7 Fix NextcloudStubs.php JSONResponse to use statusCode parameter name

## Phase 2: Add missing stubs

- [x] 2.1 Add OCP\IDBConnection interface stub
- [x] 2.2 Add OCP\IConfig interface stub
- [x] 2.3 Add OCP\ICache and OCP\ICacheFactory interface stubs
- [x] 2.4 Add OCP\BackgroundJob\IJobList interface stub
- [x] 2.5 Add OCP\BackgroundJob\QueuedJob abstract class stub
- [x] 2.6 Add OCP\AppFramework\Utility\ITimeFactory interface stub
- [x] 2.7 Add OCP\Notification\IManager and INotification interface stubs
- [x] 2.8 Add Psr\Container\ContainerInterface stub
- [x] 2.9 Add OCP\AppFramework\App class stub
- [x] 2.10 Add OCP\AppFramework\Bootstrap interfaces (IBootstrap, IRegistrationContext, IBootContext)
- [x] 2.11 Add OCP\AppFramework\Http\TextPlainResponse class stub
- [x] 2.12 Add OCP\Files\Node::getId(), getMimeType(), getRelativePath(), getType(), getPermissions()
- [x] 2.13 Add OCP\Files\Folder::extends Node, getPermissions(), getDirectoryListing(), nodeExists(), get()
- [x] 2.14 Add OCP\Constants class stub
- [x] 2.15 Add OCP\Files\NotFoundException and NotPermittedException stubs
- [x] 2.16 Add OCA\OpenRegister\Db\SchemaMapper and RegisterMapper stubs
- [x] 2.17 Add OCA\OpenRegister\Db\Register and Schema entity stubs

## Phase 3: Fix pre-existing test failures

- [x] 3.1 Fix BatchStateServiceTest createMock named param (className: → positional)
- [x] 3.2 Fix BatchStateServiceKeepAliveTest createMock named param
- [x] 3.3 Fix NativeSigningProviderTest createMock named param
- [x] 3.4 Fix BatchStateServiceTest::setUp() removing conflicting getUser stub
- [x] 3.5 Fix BatchStateServiceTest callback return type (void → bool)
- [x] 3.6 Fix PdfControllerTest missing IUserSession dependency
- [x] 3.7 Fix PdfControllerTest missing auth user mock setup
- [x] 3.8 Fix MetricsControllerTest missing IAppConfig dependency
- [x] 3.9 Fix TemplatesControllerTest missing auth user mock setup
- [x] 3.10 Fix CorrespondenceServiceTest wrong saveObject argument count
- [x] 3.11 Fix RegisterDiscoveryServiceTest SchemaMapper::find mixed ID type

## Phase 4: New service tests

- [x] 4.1 tests/unit/Service/BatchExtractionServiceTest.php
- [x] 4.2 tests/unit/Service/BatchReportServiceTest.php
- [x] 4.3 tests/unit/Service/BatchUploadServiceTest.php
- [x] 4.4 tests/unit/Service/EntityConsolidationServiceTest.php
- [x] 4.5 tests/unit/Service/SigningServiceTest.php
- [x] 4.6 tests/unit/Service/WooProfileServiceTest.php

## Phase 5: New controller tests

- [x] 5.1 tests/unit/Controller/CorrespondenceControllerTest.php
- [x] 5.2 tests/unit/Controller/HealthControllerTest.php
- [x] 5.3 tests/unit/Controller/MetricsCollectorTest.php
- [x] 5.4 tests/unit/Controller/PreferencesControllerTest.php
- [x] 5.5 tests/unit/Controller/PrintControllerTest.php

## Phase 6: New background job tests

- [x] 6.1 tests/unit/BackgroundJob/BatchCorrespondenceJobTest.php

## Phase 7: Newman API collection

- [x] 7.1 tests/newman/docudesk-api-collection.json
