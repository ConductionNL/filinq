<?php

/**
 * Unit tests for AnonymizationService::recordAnonymizationLink — anonymization-link-schema
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Tests for the idempotent source↔anonymised file-link UPSERT.
 *
 * Uses a hand-rolled fake ObjectService (anonymous class) rather than a
 * PHPUnit mock: the real OpenRegister ObjectService::saveObject has 10
 * parameters, and a mock's positional argument binding would misalign a
 * narrow capture callback. The fake declares exactly the methods + named
 * parameters the service-under-test calls.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationLinkServiceTest extends TestCase {

	/**
	 * Build a fake ObjectService that records the saved object.
	 *
	 * @param array $searchResult Value returned by searchObjects().
	 * @param mixed $saveReturn Value returned by saveObject().
	 * @param bool $searchThrows When true, searchObjects() throws.
	 * @param bool $saveThrows When true, saveObject() throws (after capturing).
	 *
	 * @return object Fake with public `$captured` and `$saveCalled`.
	 */
	private function makeObjectService(
		array $searchResult,
		mixed $saveReturn,
		bool $searchThrows = false,
		bool $saveThrows = false,
	): object {
		return new class($searchResult, $saveReturn, $searchThrows, $saveThrows) {
			/**
			 * Object passed to the most recent saveObject() call.
			 *
			 * @var array<string, mixed>|null
			 */
			public ?array $captured = null;

			/**
			 * Whether saveObject() was invoked.
			 *
			 * @var boolean
			 */
			public bool $saveCalled = false;

			/**
			 * Configure the fake's canned behaviour.
			 *
			 * @param array<int, mixed> $searchResult Search result.
			 * @param mixed $saveReturn Save return value.
			 * @param bool $searchThrows Throw on search.
			 * @param bool $saveThrows Throw on save.
			 */
			public function __construct(
				private array $searchResult,
				private mixed $saveReturn,
				private bool $searchThrows,
				private bool $saveThrows,
			) {
			}//end __construct()

			/**
			 * Return the configured search result, or throw when armed.
			 *
			 * @param array<string, mixed> $query Search query.
			 *
			 * @return array<int, mixed>
			 */
			public function searchObjects(array $query = []): array {
				if ($this->searchThrows === true) {
					throw new \RuntimeException('search boom');
				}

				return $this->searchResult;
			}//end searchObjects()

			/**
			 * The slug-aware read. AnonymizationPersistenceService asks for
			 * the register and schema by slug, because `searchObjects` has a
			 * numeric-id contract and answers a slug with zero rows and no
			 * error. This fake answers both so the suite fails if the service
			 * ever goes back to the numeric-only call.
			 *
			 * @param string $registerSlug The register slug.
			 * @param string $schemaSlug The schema slug.
			 * @param array<string, mixed> $filters Field filters.
			 *
			 * @return array<int, mixed>
			 */
			public function searchObjectsBySlug(string $registerSlug = '', string $schemaSlug = '', array $filters = []): array {
				if ($this->searchThrows === true) {
					throw new \RuntimeException('search boom');
				}

				return $this->searchResult;
			}//end searchObjectsBySlug()

			/**
			 * Capture the object and return the configured value, or throw when armed.
			 *
			 * @param array<string, mixed> $object Object data.
			 * @param string $register Register slug.
			 * @param string $schema Schema slug.
			 *
			 * @return mixed
			 */
			public function saveObject(array $object = [], string $register = '', string $schema = ''): mixed {
				$this->saveCalled = true;
				$this->captured = $object;
				if ($this->saveThrows === true) {
					throw new \RuntimeException('OR unavailable');
				}

				return $this->saveReturn;
			}//end saveObject()

		};

	}//end makeObjectService()

	/**
	 * Build the persistence service whose container resolves the fake ObjectService.
	 *
	 * `recordAnonymizationLink()` is owned by AnonymizationPersistenceService —
	 * the collaborator AnonymizationService delegates the post-run persistence to
	 * — so the behaviour is exercised on its owner instead of through a private
	 * pass-through on the orchestrator.
	 *
	 * @param object $objectService The fake OR object service.
	 *
	 * @return AnonymizationPersistenceService
	 */
	private function buildService(object $objectService): AnonymizationPersistenceService {
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($objectService);

		$appManager = $this->createMock(originalClassName: IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		return new AnonymizationPersistenceService(
			logger: new NullLogger(),
			locator: new OpenRegisterServiceLocator($appManager, $container),
			consentCrud: $this->createMock(originalClassName: ConsentCrudService::class),
			consentService: $this->createMock(originalClassName: ConsentService::class)
		);

	}//end buildService()

	/**
	 * Record an anonymisation link through the owning service.
	 *
	 * @param AnonymizationPersistenceService $service The service under test.
	 * @param int $fileId Source file id.
	 * @param mixed $sourceNode Source node (or null).
	 * @param array<string, mixed> $resultInfo Anonymisation result info.
	 *
	 * @return array<string, mixed> The (possibly enriched) result info.
	 */
	private function invokeRecord(
		AnonymizationPersistenceService $service,
		int $fileId,
		mixed $sourceNode,
		array $resultInfo,
	): array {
		return $service->recordAnonymizationLink(
			fileId: $fileId,
			sourceNode: $sourceNode,
			resultInfo: $resultInfo
		);

	}//end invokeRecord()

	/**
	 * Not-found → create: a fresh link record with runCount 1 is saved and its id surfaced.
	 *
	 * @return void
	 */
	public function testRecordAnonymizationLinkCreatesNewRecord(): void {
		$os = $this->makeObjectService(searchResult: [], saveReturn: ['@self' => ['id' => 'new-uuid-001']]);
		$service = $this->buildService(objectService: $os);

		$result = $this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: [
				'anonymizedFileId' => 99,
				'anonymizedFileName' => 'doc_anonymized.pdf',
				'anonymizedFilePath' => '/u/doc_anonymized.pdf',
				'replacementCount' => 5,
			]
		);

		$this->assertTrue(condition: $os->saveCalled, message: 'saveObject must have been called.');
		$this->assertSame(expected: 42, actual: $os->captured['sourceFileId']);
		$this->assertSame(expected: 1, actual: $os->captured['runCount']);
		$this->assertSame(expected: 99, actual: $os->captured['anonymizedFileId']);
		$this->assertSame(expected: 'anonymized', actual: $os->captured['status']);
		$this->assertSame(expected: 'pdf', actual: $os->captured['outputFormat']);
		$this->assertSame(expected: 'new-uuid-001', actual: $result['anonymizationLinkId']);

	}//end testRecordAnonymizationLinkCreatesNewRecord()

	/**
	 * Found → update: the existing @self is preserved and runCount is incremented.
	 *
	 * @return void
	 */
	public function testRecordAnonymizationLinkUpdatesExistingRecord(): void {
		$existing = [
			'@self' => ['id' => 'existing-uuid-007'],
			'sourceFileId' => 42,
			'anonymizedFileId' => 90,
			'runCount' => 1,
			'status' => 'anonymized',
		];

		$saveReturn = ['@self' => ['id' => 'existing-uuid-007']];
		$os = $this->makeObjectService(searchResult: [$existing], saveReturn: $saveReturn);
		$service = $this->buildService(objectService: $os);

		$result = $this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: [
				'anonymizedFileId' => 123,
				'anonymizedFileName' => 'doc_anonymized.pdf',
				'anonymizedFilePath' => '/u/doc_anonymized.pdf',
				'replacementCount' => 8,
			]
		);

		$this->assertTrue(condition: $os->saveCalled, message: 'saveObject must have been called.');
		$selfId = $os->captured['@self']['id'];
		$this->assertSame(expected: 'existing-uuid-007', actual: $selfId, message: 'Existing @self preserved.');
		$this->assertSame(expected: 2, actual: $os->captured['runCount'], message: 'runCount incremented.');
		$newAnonId = $os->captured['anonymizedFileId'];
		$this->assertSame(expected: 123, actual: $newAnonId, message: 'anonymizedFileId reflects new run.');
		$this->assertSame(expected: 'existing-uuid-007', actual: $result['anonymizationLinkId']);

	}//end testRecordAnonymizationLinkUpdatesExistingRecord()

	/**
	 * Best-effort: a saveObject failure is swallowed and no link id is surfaced.
	 *
	 * @return void
	 */
	public function testRecordAnonymizationLinkIsBestEffortOnSaveFailure(): void {
		$os = $this->makeObjectService(searchResult: [], saveReturn: null, saveThrows: true);
		$service = $this->buildService(objectService: $os);

		$result = $this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: ['anonymizedFileId' => 99]
		);

		$this->assertArrayNotHasKey(key: 'anonymizationLinkId', array: $result);

	}//end testRecordAnonymizationLinkIsBestEffortOnSaveFailure()

	/**
	 * Best-effort: a searchObjects failure is swallowed; the method returns without saving.
	 *
	 * @return void
	 */
	public function testRecordAnonymizationLinkIsBestEffortOnSearchFailure(): void {
		$os = $this->makeObjectService(searchResult: [], saveReturn: null, searchThrows: true);
		$service = $this->buildService(objectService: $os);

		$result = $this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: ['anonymizedFileId' => 99]
		);

		$this->assertFalse(condition: $os->saveCalled, message: 'saveObject must not run when lookup fails.');
		$this->assertArrayNotHasKey(key: 'anonymizationLinkId', array: $result);

	}//end testRecordAnonymizationLinkIsBestEffortOnSearchFailure()

	/**
	 * The verifier's verdict is written onto the link, with the mode it holds for.
	 *
	 * 🔴 WITHOUT THIS, THE VERIFICATION LIVES FOR THE LENGTH OF ONE REQUEST.
	 * Six months later, asked whether the copy that was published was ever
	 * checked, the only honest answer is "probably".
	 *
	 * @return void
	 */
	public function testTheVerificationVerdictIsRecordedOnTheLink(): void {
		$os = $this->makeObjectService(searchResult: [], saveReturn: ['@self' => ['id' => 'uuid-verdict']]);
		$service = $this->buildService(objectService: $os);

		$this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: [
				'anonymizedFileId' => 99,
				'anonymizedFileName' => 'doc_anonymized.pdf',
				'redactionVerification' => [
					'verdict' => 'clean',
					'outputMode' => 'pdf/ua',
					'routesChecked' => ['text_layer', 'xmp'],
					'findings' => [],
					'mayBePublished' => true,
				],
			]
		);

		$this->assertSame(expected: 'clean', actual: $os->captured['verificationVerdict']);
		$this->assertSame(
			expected: 'pdf/ua',
			actual: $os->captured['verificationOutputMode'],
			message: 'a verdict without its output mode claims more than it checked'
		);
		$this->assertSame(expected: 'text_layer,xmp', actual: $os->captured['verificationRoutes']);
		$this->assertSame(expected: '', actual: $os->captured['verificationLeakRoutes']);
		$this->assertNotSame(expected: '', actual: $os->captured['verifiedAt']);

	}//end testTheVerificationVerdictIsRecordedOnTheLink()

	/**
	 * A verdict of `unverifiable` is written, not left blank.
	 *
	 * An empty field and a clean verdict are the same value to anything
	 * filtering on "was this copy checked", and one of them means nobody
	 * looked.
	 *
	 * @return void
	 */
	public function testAnUnverifiableCopyIsRecordedAsSuch(): void {
		$os = $this->makeObjectService(searchResult: [], saveReturn: ['@self' => ['id' => 'uuid-unver']]);
		$service = $this->buildService(objectService: $os);

		$this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: [
				'anonymizedFileId' => 99,
				'redactionVerification' => [
					'verdict' => 'unverifiable',
					'outputMode' => 'pdf-only',
					'routesChecked' => [],
					'findings' => [],
					'mayBePublished' => false,
				],
			]
		);

		$this->assertSame(expected: 'unverifiable', actual: $os->captured['verificationVerdict']);

	}//end testAnUnverifiableCopyIsRecordedAsSuch()

	/**
	 * A leaking verdict names the routes something was found on.
	 *
	 * @return void
	 */
	public function testALeakingVerdictNamesTheRoutes(): void {
		$os = $this->makeObjectService(searchResult: [], saveReturn: ['@self' => ['id' => 'uuid-leak']]);
		$service = $this->buildService(objectService: $os);

		$this->invokeRecord(
			service: $service,
			fileId: 42,
			sourceNode: null,
			resultInfo: [
				'anonymizedFileId' => 99,
				'redactionVerification' => [
					'verdict' => 'leaking',
					'outputMode' => 'pdf-only',
					'routesChecked' => ['text_layer', 'xmp'],
					'findings' => [['route' => 'xmp', 'values' => ['Fatima El-Amrani']]],
					'mayBePublished' => false,
				],
			]
		);

		$this->assertSame(expected: 'leaking', actual: $os->captured['verificationVerdict']);
		$this->assertSame(
			expected: 'xmp',
			actual: $os->captured['verificationLeakRoutes'],
			message: 'a leaking verdict that does not say where is a verdict nobody can act on'
		);

	}//end testALeakingVerdictNamesTheRoutes()

}//end class
