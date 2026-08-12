<?php

/**
 * Unit tests for AnonymizationService prohibition-match behaviour
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for AnonymizationService's prohibition-match attachment.
 *
 * The merge of Robert's branch REMOVED development's narrow private helpers
 * `attachProhibitionMatches` / `computeProhibitionMatch`. Their behaviour is
 * superseded by the private `applyPolicyDecisions()` pass (which consults
 * `PolicyMatchService::match()` and `PolicyMatchService::highConfidenceThreshold()`).
 * These tests therefore exercise that SURVIVING behaviour through the public
 * `extractAndDetectEntities()` entry point: every returned entity carries a
 * read-only `prohibitionMatch` hint (null, or `{ruleId, ruleName, highConfidence}`).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceProhibitionTest extends TestCase {
	use BuildsAnonymizationService;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $mockAppConfig;

	/**
	 * @var EntityDetectionService
	 */
	private EntityDetectionService $entityDetection;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockAppConfig = $this->createMock(IAppConfig::class);

		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

		$this->entityDetection = new EntityDetectionService(
			new AnonymizationResultParser()
		);

	}//end setUp()

	/**
	 * Build the service under test
	 *
	 * @return AnonymizationService
	 */
	private function makeService(): AnonymizationService {
		return $this->makeAnonymizationServiceFrom(
			[
				'logger' => $this->mockLogger,
				'container' => $this->mockContainer,
				'appManager' => $this->mockAppManager,
				'appConfig' => $this->mockAppConfig,
				'entityDetection' => $this->entityDetection,
			]
		);

	}//end makeService()

	/**
	 * Stub the container to return the OR services + an optional PolicyMatchService.
	 *
	 * Mirrors the merged `extractAndDetectEntities()` container dependencies:
	 * TextExtractionService, LegalBasisProposalService, EntityRelationMapper and
	 * (optionally) PolicyMatchService — the last is what `applyPolicyDecisions()`
	 * resolves and is omitted (throws on resolve) to simulate an unavailable
	 * policy layer.
	 *
	 * @param FakePolicyMatchService|null $policyService Fake matcher or null (not registered).
	 * @param array $rawEntities Entities returned by EntityRelationMapper.
	 *
	 * @return void
	 */
	private function stubContainerForExtract(?FakePolicyMatchService $policyService, array $rawEntities): void {
		$mockExtractor = $this->createMock(\OCA\OpenRegister\Service\TextExtractionService::class);

		$mockMapper = $this->createMock(\OCA\OpenRegister\Db\EntityRelationMapper::class);
		$mockMapper->method('findEntitiesForFile')->willReturn($rawEntities);

		$mockGrondslag = $this->createMock(\OCA\DocuDesk\Service\LegalBasisProposalService::class);
		$mockGrondslag->method('getEntityTypeWhitelist')->willReturn(null);
		// enrichEntitiesWithBases must return its entities argument unchanged.
		$mockGrondslag->method('enrichEntitiesWithBases')->willReturnArgument(0);

		$this->mockContainer->method('get')->willReturnCallback(
			function (string $class) use ($mockExtractor, $mockMapper, $mockGrondslag, $policyService) {
				if ($class === 'OCA\OpenRegister\Service\TextExtractionService') {
					return $mockExtractor;
				}

				if ($class === 'OCA\OpenRegister\Db\EntityRelationMapper') {
					return $mockMapper;
				}

				if ($class === 'OCA\DocuDesk\Service\LegalBasisProposalService') {
					return $mockGrondslag;
				}

				if ($class === 'OCA\DocuDesk\Service\PolicyMatchService') {
					if ($policyService === null) {
						throw new \Exception('PolicyMatchService not registered');
					}

					return $policyService;
				}

				throw new \Exception("Unknown service: $class");
			}
		);

	}//end stubContainerForExtract()

	/**
	 * Build a fake matcher whose match() returns a prohibition (or null) and
	 * whose highConfidenceThreshold() returns the given threshold.
	 *
	 * @param array|null $matchResult The match() return (kind=prohibition) or null.
	 * @param float $threshold The high-confidence threshold.
	 *
	 * @return FakePolicyMatchService
	 */
	private function makeMatcher(?array $matchResult, float $threshold = 0.85): FakePolicyMatchService {
		$matcher = new FakePolicyMatchService();
		$matcher->matchResult = $matchResult;
		$matcher->threshold = $threshold;
		return $matcher;
	}//end makeMatcher()

	/**
	 * When PolicyMatchService is not available, all prohibitionMatch fields are null.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
	 */
	public function testProhibitionMatchIsNullWhenServiceUnavailable(): void {
		$rawEntities = [
			['entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen', 'confidence' => 0.95],
		];

		$this->stubContainerForExtract(policyService: null, rawEntities: $rawEntities);

		$service = $this->makeService();
		$result = $service->extractAndDetectEntities(fileId: 1);

		$this->assertCount(1, $result['entities']);
		$this->assertNull($result['entities'][0]['prohibitionMatch']);

	}//end testProhibitionMatchIsNullWhenServiceUnavailable()

	/**
	 * When a prohibition rule matches and confidence is above threshold, highConfidence is true.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
	 */
	public function testProhibitionMatchHighConfidenceWhenAboveThreshold(): void {
		$matcher = $this->makeMatcher(
			matchResult: [
				'kind' => PolicyMatchService::KIND_PROHIBITION,
				'uuid' => 'R-X',
				'primaryName' => 'Beschermde Getuige A',
				'entityType' => 'PERSON',
			],
			threshold: 0.85
		);

		$rawEntities = [
			['entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen', 'confidence' => 0.96],
		];

		$this->stubContainerForExtract(policyService: $matcher, rawEntities: $rawEntities);

		$service = $this->makeService();
		$result = $service->extractAndDetectEntities(fileId: 1);

		$match = $result['entities'][0]['prohibitionMatch'];
		$this->assertNotNull($match);
		$this->assertSame('R-X', $match['ruleId']);
		$this->assertSame('Beschermde Getuige A', $match['ruleName']);
		$this->assertTrue($match['highConfidence']);

	}//end testProhibitionMatchHighConfidenceWhenAboveThreshold()

	/**
	 * When confidence is below threshold, highConfidence is false.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
	 */
	public function testProhibitionMatchLowConfidenceWhenBelowThreshold(): void {
		$matcher = $this->makeMatcher(
			matchResult: [
				'kind' => PolicyMatchService::KIND_PROHIBITION,
				'uuid' => 'R-Y',
				'primaryName' => 'Rule Y',
				'entityType' => 'PERSON',
			],
			threshold: 0.85
		);

		$rawEntities = [
			['entity_type' => 'PERSON', 'entity_value' => 'Jane Doe', 'confidence' => 0.62],
		];

		$this->stubContainerForExtract(policyService: $matcher, rawEntities: $rawEntities);

		$service = $this->makeService();
		$result = $service->extractAndDetectEntities(fileId: 1);

		$match = $result['entities'][0]['prohibitionMatch'];
		$this->assertNotNull($match);
		$this->assertFalse($match['highConfidence']);

	}//end testProhibitionMatchLowConfidenceWhenBelowThreshold()

	/**
	 * Threshold is inclusive: confidence exactly at threshold → highConfidence true.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
	 */
	public function testProhibitionMatchThresholdIsInclusive(): void {
		$matcher = $this->makeMatcher(
			matchResult: [
				'kind' => PolicyMatchService::KIND_PROHIBITION,
				'uuid' => 'R-Z',
				'primaryName' => 'Rule Z',
				'entityType' => 'PERSON',
			],
			threshold: 0.85
		);

		$rawEntities = [
			['entity_type' => 'PERSON', 'entity_value' => 'Exact Threshold', 'confidence' => 0.85],
		];

		$this->stubContainerForExtract(policyService: $matcher, rawEntities: $rawEntities);

		$service = $this->makeService();
		$result = $service->extractAndDetectEntities(fileId: 1);

		$match = $result['entities'][0]['prohibitionMatch'];
		$this->assertNotNull($match);
		$this->assertTrue($match['highConfidence']);

	}//end testProhibitionMatchThresholdIsInclusive()

	/**
	 * When no rule matches, prohibitionMatch is null even when service is available.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
	 */
	public function testProhibitionMatchNullWhenNoRuleMatches(): void {
		$matcher = $this->makeMatcher(matchResult: null, threshold: 0.85);

		$rawEntities = [
			['entity_type' => 'LOCATION', 'entity_value' => 'Amsterdam', 'confidence' => 0.99],
		];

		$this->stubContainerForExtract(policyService: $matcher, rawEntities: $rawEntities);

		$service = $this->makeService();
		$result = $service->extractAndDetectEntities(fileId: 1);

		$this->assertNull($result['entities'][0]['prohibitionMatch']);

	}//end testProhibitionMatchNullWhenNoRuleMatches()

	/**
	 * The high-confidence threshold governs the flag and is read from the
	 * matcher (PolicyMatchService::highConfidenceThreshold, which itself reads
	 * IAppConfig). A custom 0.60 threshold makes confidence 0.70 high-confidence.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
	 */
	public function testThresholdReadFromMatcher(): void {
		$matcher = $this->makeMatcher(
			matchResult: [
				'kind' => PolicyMatchService::KIND_PROHIBITION,
				'uuid' => 'R-1',
				'primaryName' => 'Rule 1',
				'entityType' => 'PERSON',
			],
			threshold: 0.60
		);

		$rawEntities = [
			['entity_type' => 'PERSON', 'entity_value' => 'Test Person', 'confidence' => 0.70],
		];

		$this->stubContainerForExtract(policyService: $matcher, rawEntities: $rawEntities);

		$service = $this->makeService();
		$result = $service->extractAndDetectEntities(fileId: 1);

		$this->assertTrue($result['entities'][0]['prohibitionMatch']['highConfidence']);

	}//end testThresholdReadFromMatcher()

}//end class

/**
 * Fake PolicyMatchService for the prohibition pass.
 *
 * Concrete (not a PHPUnit mock) so it exposes the exact merged surface
 * `applyPolicyDecisions()` relies on: `match()` and `highConfidenceThreshold()`.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 */
class FakePolicyMatchService {

	/**
	 * The result match() should return (or null for no match).
	 *
	 * @var array<string, mixed>|null
	 */
	public ?array $matchResult = null;

	/**
	 * The high-confidence threshold to report.
	 *
	 * @var float
	 */
	public float $threshold = 0.85;

	/**
	 * The configured high-confidence threshold.
	 *
	 * @return float
	 */
	public function highConfidenceThreshold(): float {
		return $this->threshold;
	}//end highConfidenceThreshold()

	/**
	 * Match a detected entity against the policy layer.
	 *
	 * @param string $entityText The entity text.
	 * @param string $entityType The entity type.
	 * @param array<string, mixed> $resolvedIdentifiers Optional structured identifiers.
	 *
	 * @return array<string, mixed>|null The configured match, or null.
	 */
	public function match(string $entityText, string $entityType, array $resolvedIdentifiers = []): ?array {
		return $this->matchResult;
	}//end match()

}//end class
