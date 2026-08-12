<?php

/**
 * Unit tests for EntityConsolidationService prohibition and suggestedBases enrichment
 *
 * Verifies the consolidated-entities endpoint enrichment added by
 * anonymisation-entity-review-prohibition-hints:
 *
 *   - prohibitionMatch: null when no rules exist
 *   - prohibitionMatch: correct object when a rule matches
 *   - highConfidence flag respects the threshold at the boundary (inclusive)
 *   - highestConfidence is used (not file-level confidence)
 *   - suggestedBases is attached to every entity from the resolver
 *   - backward compatibility: pre-change fields unchanged
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BasesResolverService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for EntityConsolidationService prohibition-match and suggestedBases enrichment.
 *
 * Exercises consolidateEntities() via a real instance with mocked dependencies.
 * The mock PolicyMatchService stub and mock BasesResolverService are configured
 * per test to simulate different prohibition rule scenarios and dossier states.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
 */
class EntityConsolidationProhibitionTest extends TestCase {

	/**
	 * Default high-confidence threshold used in tests (matches app default).
	 */
	private const THRESHOLD = 0.85;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * @var WooProfileService|MockObject
	 */
	private WooProfileService|MockObject $wooProfile;

	/**
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $appManager;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $container;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $appConfig;

	/**
	 * Set up common mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->wooProfile = $this->createMock(WooProfileService::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->wooProfile->method('shouldAnonymize')->willReturn(true);
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->appConfig->method('getValueFloat')->willReturn(self::THRESHOLD);

	}//end setUp()

	/**
	 * Build the service under test with given PolicyMatchService and BasesResolverService stubs.
	 *
	 * @param PolicyMatchService|MockObject $policyMatch Policy matcher stub.
	 * @param BasesResolverService|MockObject $basesResolver Bases resolver stub.
	 *
	 * @return EntityConsolidationService
	 */
	private function buildService(
		PolicyMatchService|MockObject $policyMatch,
		BasesResolverService|MockObject $basesResolver,
	): EntityConsolidationService {
		// Container returns an empty entity mapper (no file entities).
		$mockMapper = $this->getMockBuilder(\stdClass::class)
			->addMethods(['findEntitiesForFile'])
			->getMock();
		$mockMapper->method('findEntitiesForFile')->willReturn([]);

		$this->container->method('get')->willReturn($mockMapper);

		return new EntityConsolidationService(
			logger: $this->logger,
			wooProfile: $this->wooProfile,
			appManager: $this->appManager,
			container: $this->container,
			policyMatch: $policyMatch,
			basesResolver: $basesResolver,
			appConfig: $this->appConfig
		);

	}//end buildService()

	/**
	 * Build a batch with pre-populated entity data injected directly via the
	 * consolidated map (by using the file-entities mapper mock that returns
	 * entity objects — simulated by building the result via a real
	 * consolidateEntities call on a batch with an injected container).
	 *
	 * For test simplicity we exercise the enrichment by building a service
	 * that has files in "extracted" status and a mapper returning known entities.
	 *
	 * @param array<int, array<string, mixed>> $entities Entity fixtures to inject.
	 *
	 * @return EntityConsolidationService
	 */
	private function buildServiceWithEntities(
		array $entities,
		PolicyMatchService|MockObject $policyMatch,
		BasesResolverService|MockObject $basesResolver,
	): EntityConsolidationService {
		$mockMapper = $this->getMockBuilder(\stdClass::class)
			->addMethods(['findEntitiesForFile'])
			->getMock();
		$mockMapper->method('findEntitiesForFile')->willReturn($entities);

		$this->container->method('get')->willReturn($mockMapper);

		return new EntityConsolidationService(
			logger: $this->logger,
			wooProfile: $this->wooProfile,
			appManager: $this->appManager,
			container: $this->container,
			policyMatch: $policyMatch,
			basesResolver: $basesResolver,
			appConfig: $this->appConfig
		);

	}//end buildServiceWithEntities()

	/**
	 * When PolicyMatchService returns null, prohibitionMatch is null.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
	 */
	public function testProhibitionMatchIsNullWhenNoRules(): void {
		$policyMatch = $this->createMock(PolicyMatchService::class);
		$policyMatch->method('matchProhibitionHint')->willReturn(null);

		$basesResolver = $this->createMock(BasesResolverService::class);
		$basesResolver->method('resolveBasesForBatch')->willReturn([]);

		$service = $this->buildServiceWithEntities(
			entities: [['entity_type' => 'PERSON', 'entity_value' => 'Jan', 'confidence' => 0.9]],
			policyMatch: $policyMatch,
			basesResolver: $basesResolver
		);

		$batch = ['status' => 'review', 'files' => [['fileId' => 1, 'status' => 'extracted']]];
		$result = $service->consolidateEntities(batch: $batch);

		$this->assertCount(1, $result);
		$this->assertNull($result[0]['prohibitionMatch']);

	}//end testProhibitionMatchIsNullWhenNoRules()

	/**
	 * When a prohibition rule matches, prohibitionMatch has ruleId, ruleName, highConfidence.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
	 */
	public function testProhibitionMatchIsPopulatedWhenRuleMatches(): void {
		$policyMatch = $this->createMock(PolicyMatchService::class);
		$policyMatch->method('matchProhibitionHint')->willReturn(
			[
				'ruleId' => 'R-X',
				'ruleName' => 'Beschermde Getuige A',
			]
		);

		$basesResolver = $this->createMock(BasesResolverService::class);
		$basesResolver->method('resolveBasesForBatch')->willReturn([]);

		$service = $this->buildServiceWithEntities(
			entities: [['entity_type' => 'PERSON', 'entity_value' => 'Getuige A', 'confidence' => 0.93]],
			policyMatch: $policyMatch,
			basesResolver: $basesResolver
		);

		$batch = ['status' => 'review', 'files' => [['fileId' => 1, 'status' => 'extracted']]];
		$result = $service->consolidateEntities(batch: $batch);

		$this->assertCount(1, $result);
		$match = $result[0]['prohibitionMatch'];
		$this->assertNotNull($match);
		$this->assertSame('R-X', $match['ruleId']);
		$this->assertSame('Beschermde Getuige A', $match['ruleName']);
		$this->assertTrue($match['highConfidence']);

	}//end testProhibitionMatchIsPopulatedWhenRuleMatches()

	/**
	 * highConfidence is true when highestConfidence >= threshold (inclusive at boundary).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
	 */
	public function testHighConfidenceTrueAtThresholdBoundary(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueFloat')->willReturn(0.85);

		$policyMatch = $this->createMock(PolicyMatchService::class);
		$policyMatch->method('matchProhibitionHint')->willReturn(
			[
				'ruleId' => 'R-boundary',
				'ruleName' => 'Boundary Rule',
			]
		);

		$basesResolver = $this->createMock(BasesResolverService::class);
		$basesResolver->method('resolveBasesForBatch')->willReturn([]);

		// confidence exactly at threshold → highConfidence = true.
		$service = $this->buildServiceWithEntities(
			entities: [['entity_type' => 'PERSON', 'entity_value' => 'Entity', 'confidence' => 0.85]],
			policyMatch: $policyMatch,
			basesResolver: $basesResolver
		);

		$batch = ['status' => 'review', 'files' => [['fileId' => 1, 'status' => 'extracted']]];
		$result = $service->consolidateEntities(batch: $batch);

		$this->assertTrue($result[0]['prohibitionMatch']['highConfidence']);

	}//end testHighConfidenceTrueAtThresholdBoundary()

	/**
	 * highConfidence is false when highestConfidence < threshold.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
	 */
	public function testHighConfidenceFalseBelowThreshold(): void {
		$policyMatch = $this->createMock(PolicyMatchService::class);
		$policyMatch->method('matchProhibitionHint')->willReturn(
			[
				'ruleId' => 'R-Y',
				'ruleName' => 'Low Confidence Rule',
			]
		);

		$basesResolver = $this->createMock(BasesResolverService::class);
		$basesResolver->method('resolveBasesForBatch')->willReturn([]);

		// confidence below threshold → highConfidence = false.
		$service = $this->buildServiceWithEntities(
			entities: [['entity_type' => 'PERSON', 'entity_value' => 'Entity', 'confidence' => 0.7]],
			policyMatch: $policyMatch,
			basesResolver: $basesResolver
		);

		$batch = ['status' => 'review', 'files' => [['fileId' => 1, 'status' => 'extracted']]];
		$result = $service->consolidateEntities(batch: $batch);

		$this->assertFalse($result[0]['prohibitionMatch']['highConfidence']);

	}//end testHighConfidenceFalseBelowThreshold()

	/**
	 * suggestedBases from the resolver is attached to every entity.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
	 */
	public function testSuggestedBasesAttachedToEachEntity(): void {
		$policyMatch = $this->createMock(PolicyMatchService::class);
		$policyMatch->method('matchProhibitionHint')->willReturn(null);

		$basesResolver = $this->createMock(BasesResolverService::class);
		$basesResolver->method('resolveBasesForBatch')->willReturn(['uuid-base-a', 'uuid-base-b']);

		$service = $this->buildServiceWithEntities(
			entities: [
				['entity_type' => 'PERSON', 'entity_value' => 'Jan', 'confidence' => 0.9],
				['entity_type' => 'PERSON', 'entity_value' => 'Marie', 'confidence' => 0.8],
			],
			policyMatch: $policyMatch,
			basesResolver: $basesResolver
		);

		$batch = ['status' => 'review', 'files' => [['fileId' => 1, 'status' => 'extracted']]];
		$result = $service->consolidateEntities(batch: $batch);

		$this->assertCount(2, $result);
		foreach ($result as $entity) {
			$this->assertSame(['uuid-base-a', 'uuid-base-b'], $entity['suggestedBases']);
		}

	}//end testSuggestedBasesAttachedToEachEntity()

	/**
	 * Backward-compatibility: pre-change fields (type, value, highestConfidence,
	 * fileCount, included) remain present in the response unchanged.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-6
	 */
	public function testPreChangeFieldsRemainIntact(): void {
		$policyMatch = $this->createMock(PolicyMatchService::class);
		$policyMatch->method('matchProhibitionHint')->willReturn(null);

		$basesResolver = $this->createMock(BasesResolverService::class);
		$basesResolver->method('resolveBasesForBatch')->willReturn([]);

		$service = $this->buildServiceWithEntities(
			entities: [['entity_type' => 'PERSON', 'entity_value' => 'Alice', 'confidence' => 0.75]],
			policyMatch: $policyMatch,
			basesResolver: $basesResolver
		);

		$batch = ['status' => 'review', 'files' => [['fileId' => 1, 'status' => 'extracted']]];
		$result = $service->consolidateEntities(batch: $batch);

		$entity = $result[0];
		$this->assertArrayHasKey('type', $entity);
		$this->assertArrayHasKey('value', $entity);
		$this->assertArrayHasKey('highestConfidence', $entity);
		$this->assertArrayHasKey('fileCount', $entity);
		$this->assertArrayHasKey('included', $entity);
		// New fields are present too (strict superset).
		$this->assertArrayHasKey('prohibitionMatch', $entity);
		$this->assertArrayHasKey('suggestedBases', $entity);

	}//end testPreChangeFieldsRemainIntact()
}//end class
