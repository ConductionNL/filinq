<?php

/**
 * Unit tests for GlAccountSuggestionService
 *
 * Covers the history path (REQ-GLS-02), the cold-start keyword path
 * (REQ-GLS-03), the honest-empty path, the correction-feedback booking
 * recorder (REQ-GLS-05), and the AI re-rank graceful-degradation +
 * candidate-set-only guarantee (REQ-GLS-04).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\GlAccountSuggestionService;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Suggestion\CategoryKeywordMapper;
use OCA\Filinq\Service\Suggestion\HistoryRanker;
use OCA\Filinq\Service\Suggestion\SupplierIdentityResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\TaskProcessing\IManager as TaskProcessingIManager;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for GlAccountSuggestionService.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GlAccountSuggestionServiceTest extends TestCase {

	private GlAccountSuggestionService $service;

	private ObjectService|MockObject $objectService;

	private IAppConfig|MockObject $config;

	private IEventDispatcher|MockObject $eventDispatcher;

	private ContainerInterface|MockObject $container;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->objectService = $this->getMockBuilder(className: ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['saveObject', 'find', 'searchObjects'])
			->getMock();

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($this->objectService);
		// Bindings resolve through SettingsService and FAIL CLOSED when unset,
		// instead of silently querying register ''. An unstubbed mock returns
		// null — which is exactly what the guard exists to catch — so the
		// configured path has to be stated for these tests to exercise it.
		$settingsService->method('resolveFinancialExtractionBinding')
			->willReturn(['register' => 'document', 'schema' => 'financialExtraction']);
		$settingsService->method('resolveGlAccountBookingBinding')
			->willReturn(['register' => 'document', 'schema' => 'glAccountBooking']);
		$settingsService->method('resolveGlAccountMappingRuleBinding')
			->willReturn(['register' => 'document', 'schema' => 'glAccountMappingRule']);

		$this->config = $this->createMock(IAppConfig::class);
		$this->config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				$map = [
					'financialExtraction_register' => 'document',
					'financialExtraction_schema' => 'financialExtraction',
					'glAccountBooking_register' => 'document',
					'glAccountBooking_schema' => 'glAccountBooking',
					'glAccountMappingRule_register' => 'document',
					'glAccountMappingRule_schema' => 'glAccountMappingRule',
				];
				return $map[$key] ?? $default;
			}
		);

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->method('get')->willThrowException(new Exception('service not found'));
		$logger = $this->createMock(LoggerInterface::class);

		$this->service = new GlAccountSuggestionService(
			settingsService: $settingsService,
			// A REAL resolver over the stubbed SettingsService, not a mock: it
			// is the piece that turns an unset binding into
			// RegisterNotConfiguredException, so mocking it away would remove
			// the behaviour these tests are meant to run through.
			registerResolver: new OpenRegisterResolver(settingsService: $settingsService),
			eventDispatcher: $this->eventDispatcher,
			container: $this->container,
			logger: $logger,
			identityResolver: new SupplierIdentityResolver(),
			historyRanker: new HistoryRanker(),
			keywordMapper: new CategoryKeywordMapper(),
		);

	}//end setUp()

	/**
	 * A resolvable supplier identity with matching booking history yields a
	 * history-ranked suggestion, and dispatches the sibling event.
	 *
	 * @return void
	 */
	public function testSuggestReturnsHistoryRankedResult(): void {
		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierKvk' => '12345678', 'supplierName' => 'Hostbaar B.V.'],
		]);

		$this->objectService->method('searchObjects')->willReturn([
			['accountCode' => '4300', 'accountLabel' => 'Kantoorkosten', 'bookedAt' => '2024-01-01T00:00:00+00:00'],
			['accountCode' => '4300', 'accountLabel' => 'Kantoorkosten', 'bookedAt' => '2024-02-01T00:00:00+00:00'],
		]);

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped');

		$result = $this->service->suggest(
			extractionId: 'extraction-1',
			candidateAccounts: [],
			sourceApp: 'shillinq',
			requestedBy: 'annemarie'
		);

		$this->assertSame('12345678', $result['supplierIdentity']);
		$this->assertSame('kvk', $result['identityType']);
		$this->assertSame('history', $result['source']);
		$this->assertSame('4300', $result['suggestedAccounts'][0]['code']);

	}//end testSuggestReturnsHistoryRankedResult()

	/**
	 * No booking history but a matching keyword rule yields a cold-start
	 * suggestion.
	 *
	 * @return void
	 */
	public function testSuggestFallsBackToKeywordRuleOnColdStart(): void {
		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierName' => 'Lunchroom De Hoek'],
		]);

		$this->objectService->method('searchObjects')->willReturnCallback(
			function (array $query) {
				if (($query['@self']['schema'] ?? '') === 'glAccountMappingRule') {
					return [
						['keywords' => ['lunch'], 'accountCode' => '4400', 'accountLabel' => 'Representatiekosten', 'priority' => 0, 'enabled' => true],
					];
				}

				return [];
			}
		);

		$result = $this->service->suggest(
			extractionId: 'extraction-2',
			candidateAccounts: [],
			sourceApp: 'shillinq',
			requestedBy: 'annemarie'
		);

		$this->assertSame('keyword-rule', $result['source']);
		$this->assertSame('4400', $result['suggestedAccounts'][0]['code']);

	}//end testSuggestFallsBackToKeywordRuleOnColdStart()

	/**
	 * No history and no rule match returns an honest empty result, never a
	 * guess.
	 *
	 * @return void
	 */
	public function testSuggestReturnsHonestEmptyResultWhenNothingMatches(): void {
		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierName' => 'Onbekende Leverancier'],
		]);
		$this->objectService->method('searchObjects')->willReturn([]);

		$result = $this->service->suggest(
			extractionId: 'extraction-3',
			candidateAccounts: [],
			sourceApp: 'shillinq',
			requestedBy: 'annemarie'
		);

		$this->assertSame('none', $result['source']);
		$this->assertSame([], $result['suggestedAccounts']);

	}//end testSuggestReturnsHonestEmptyResultWhenNothingMatches()

	/**
	 * An unresolvable supplier identity (no KvK/IBAN/name) yields a `none`
	 * result without querying booking history.
	 *
	 * @return void
	 */
	public function testSuggestWithUnresolvableIdentityReturnsNone(): void {
		$this->objectService->method('find')->willReturn(['fields' => []]);
		$this->objectService->expects($this->never())->method('searchObjects');

		$result = $this->service->suggest(
			extractionId: 'extraction-4',
			candidateAccounts: [],
			sourceApp: 'shillinq',
			requestedBy: 'annemarie'
		);

		$this->assertNull($result['supplierIdentity']);
		$this->assertSame('none', $result['source']);

	}//end testSuggestWithUnresolvableIdentityReturnsNone()

	/**
	 * An unknown extraction id raises a 404-coded exception.
	 *
	 * @return void
	 */
	public function testSuggestThrows404ForUnknownExtraction(): void {
		$this->objectService->method('find')->willReturn(null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(404);

		$this->service->suggest(extractionId: 'missing', candidateAccounts: [], sourceApp: 'shillinq', requestedBy: 'annemarie');

	}//end testSuggestThrows404ForUnknownExtraction()

	/**
	 * A consumer-supplied candidateAccounts allow-list constrains the ranked
	 * result even when other codes have history.
	 *
	 * @return void
	 */
	public function testSuggestHonoursCandidateAccountsConstraint(): void {
		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierKvk' => '12345678'],
		]);
		$this->objectService->method('searchObjects')->willReturn([
			['accountCode' => '4300', 'accountLabel' => null, 'bookedAt' => '2024-01-01T00:00:00+00:00'],
			['accountCode' => '9999', 'accountLabel' => null, 'bookedAt' => '2024-02-01T00:00:00+00:00'],
		]);

		$result = $this->service->suggest(
			extractionId: 'extraction-5',
			candidateAccounts: [['code' => '4300']],
			sourceApp: 'shillinq',
			requestedBy: 'annemarie'
		);

		$this->assertCount(1, $result['suggestedAccounts']);
		$this->assertSame('4300', $result['suggestedAccounts'][0]['code']);

	}//end testSuggestHonoursCandidateAccountsConstraint()

	/**
	 * recordBooking() persists a `glAccountBooking` row when the extraction
	 * has a resolvable supplier identity.
	 *
	 * @return void
	 */
	public function testRecordBookingPersistsHistoryRow(): void {
		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierKvk' => '12345678'],
			'sourceApp' => 'shillinq',
		]);

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(
				$this->callback(function (array $object): bool {
					return $object['supplierIdentity'] === '12345678'
						&& $object['identityType'] === 'kvk'
						&& $object['accountCode'] === '4300'
						&& $object['source'] === 'correction';
				}),
				'document',
				'glAccountBooking'
			);

		$this->service->recordBooking(
			extractionId: 'extraction-1',
			accountCode: '4300',
			accountLabel: 'Kantoorkosten',
			correctedBy: 'annemarie'
		);

	}//end testRecordBookingPersistsHistoryRow()

	/**
	 * recordBooking() is a no-op (no exception, no save) when the extraction
	 * has no resolvable supplier identity.
	 *
	 * @return void
	 */
	public function testRecordBookingNoOpWithoutResolvableIdentity(): void {
		$this->objectService->method('find')->willReturn(['fields' => []]);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->service->recordBooking(
			extractionId: 'extraction-1',
			accountCode: '4300',
			accountLabel: null,
			correctedBy: 'annemarie'
		);

	}//end testRecordBookingNoOpWithoutResolvableIdentity()

	/**
	 * recordBooking() is a no-op when the extraction id does not resolve to
	 * an object at all.
	 *
	 * @return void
	 */
	public function testRecordBookingNoOpForUnknownExtraction(): void {
		$this->objectService->method('find')->willReturn(null);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->service->recordBooking(
			extractionId: 'missing',
			accountCode: '4300',
			accountLabel: null,
			correctedBy: 'annemarie'
		);

	}//end testRecordBookingNoOpForUnknownExtraction()

	/**
	 * No AI provider available: the deterministic (history) ranking is
	 * returned unchanged, no error (REQ-GLS-04 graceful degradation).
	 *
	 * @return void
	 */
	public function testSuggestWithNoAiProviderReturnsDeterministicResultUnchanged(): void {
		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierKvk' => '12345678'],
		]);
		$this->objectService->method('searchObjects')->willReturn([
			['accountCode' => '4300', 'accountLabel' => null, 'bookedAt' => '2024-01-01T00:00:00+00:00'],
		]);

		$result = $this->service->suggest(
			extractionId: 'extraction-1',
			candidateAccounts: [],
			sourceApp: 'shillinq',
			requestedBy: 'annemarie'
		);

		$this->assertSame('4300', $result['suggestedAccounts'][0]['code']);
		$this->assertSame(1.0, $result['suggestedAccounts'][0]['confidence']);

	}//end testSuggestWithNoAiProviderReturnsDeterministicResultUnchanged()

	/**
	 * An available AI provider may reorder the deterministic candidates but
	 * cannot introduce a code outside the candidate set (REQ-GLS-04).
	 *
	 * @return void
	 */
	public function testAiReRankNeverIntroducesCodeOutsideCandidateSet(): void {
		$manager = $this->createMock(TaskProcessingIManager::class);
		$manager->method('runTask')->willReturnCallback(function (TaskProcessingTask $task) {
			$task->setStatus(TaskProcessingTask::STATUS_SUCCESSFUL);
			// The AI hallucinates a code ("9999") absent from the candidate set.
			$task->setOutput(['output' => json_encode(['order' => ['4200', '9999', '4300']])]);
			return $task;
		});

		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->method('get')->willReturnCallback(
			function (string $class) use ($manager) {
				if ($class === 'OCP\\TaskProcessing\\IManager') {
					return $manager;
				}

				throw new Exception('unexpected service: ' . $class);
			}
		);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($this->objectService);
		// Bindings resolve through SettingsService and FAIL CLOSED when unset,
		// instead of silently querying register ''. An unstubbed mock returns
		// null — which is exactly what the guard exists to catch — so the
		// configured path has to be stated for these tests to exercise it.
		$settingsService->method('resolveFinancialExtractionBinding')
			->willReturn(['register' => 'document', 'schema' => 'financialExtraction']);
		$settingsService->method('resolveGlAccountBookingBinding')
			->willReturn(['register' => 'document', 'schema' => 'glAccountBooking']);
		$settingsService->method('resolveGlAccountMappingRuleBinding')
			->willReturn(['register' => 'document', 'schema' => 'glAccountMappingRule']);

		$service = new GlAccountSuggestionService(
			settingsService: $settingsService,
			// A REAL resolver over the stubbed SettingsService, not a mock: it
			// is the piece that turns an unset binding into
			// RegisterNotConfiguredException, so mocking it away would remove
			// the behaviour these tests are meant to run through.
			registerResolver: new OpenRegisterResolver(settingsService: $settingsService),
			eventDispatcher: $this->eventDispatcher,
			container: $this->container,
			logger: $this->createMock(LoggerInterface::class),
			identityResolver: new SupplierIdentityResolver(),
			historyRanker: new HistoryRanker(),
			keywordMapper: new CategoryKeywordMapper(),
		);

		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierKvk' => '12345678'],
		]);
		$this->objectService->method('searchObjects')->willReturn([
			['accountCode' => '4300', 'accountLabel' => null, 'bookedAt' => '2024-01-01T00:00:00+00:00'],
			['accountCode' => '4300', 'accountLabel' => null, 'bookedAt' => '2024-02-01T00:00:00+00:00'],
			['accountCode' => '4200', 'accountLabel' => null, 'bookedAt' => '2024-03-01T00:00:00+00:00'],
		]);

		$result = $service->suggest(extractionId: 'extraction-1', candidateAccounts: [], sourceApp: 'shillinq', requestedBy: 'annemarie');

		$codes = array_column($result['suggestedAccounts'], 'code');
		$this->assertNotContains('9999', $codes);
		// AI reordered 4200 ahead of 4300 (both were valid candidates).
		$this->assertSame('4200', $codes[0]);
		$this->assertSame('4300', $codes[1]);
		$this->assertCount(2, $codes);

	}//end testAiReRankNeverIntroducesCodeOutsideCandidateSet()

	/**
	 * The AI re-rank step is never invoked when the deterministic candidate
	 * set is empty.
	 *
	 * @return void
	 */
	public function testAiReRankNeverRunsOnEmptyCandidateSet(): void {
		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->expects($this->never())->method('get');

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($this->objectService);
		// Bindings resolve through SettingsService and FAIL CLOSED when unset,
		// instead of silently querying register ''. An unstubbed mock returns
		// null — which is exactly what the guard exists to catch — so the
		// configured path has to be stated for these tests to exercise it.
		$settingsService->method('resolveFinancialExtractionBinding')
			->willReturn(['register' => 'document', 'schema' => 'financialExtraction']);
		$settingsService->method('resolveGlAccountBookingBinding')
			->willReturn(['register' => 'document', 'schema' => 'glAccountBooking']);
		$settingsService->method('resolveGlAccountMappingRuleBinding')
			->willReturn(['register' => 'document', 'schema' => 'glAccountMappingRule']);

		$service = new GlAccountSuggestionService(
			settingsService: $settingsService,
			// A REAL resolver over the stubbed SettingsService, not a mock: it
			// is the piece that turns an unset binding into
			// RegisterNotConfiguredException, so mocking it away would remove
			// the behaviour these tests are meant to run through.
			registerResolver: new OpenRegisterResolver(settingsService: $settingsService),
			eventDispatcher: $this->eventDispatcher,
			container: $this->container,
			logger: $this->createMock(LoggerInterface::class),
			identityResolver: new SupplierIdentityResolver(),
			historyRanker: new HistoryRanker(),
			keywordMapper: new CategoryKeywordMapper(),
		);

		$this->objectService->method('find')->willReturn([
			'fields' => ['supplierName' => 'Onbekende Leverancier'],
		]);
		$this->objectService->method('searchObjects')->willReturn([]);

		$result = $service->suggest(extractionId: 'extraction-1', candidateAccounts: [], sourceApp: 'shillinq', requestedBy: 'annemarie');

		$this->assertSame([], $result['suggestedAccounts']);

	}//end testAiReRankNeverRunsOnEmptyCandidateSet()
}//end class
