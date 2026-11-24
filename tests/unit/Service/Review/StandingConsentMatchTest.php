<?php

/**
 * Unit tests for the standing-consent match attached next to the prohibition match.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
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
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\BasesResolverService;
use OCA\Filinq\Service\EntityConsolidationService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\PolicyRuleNormaliser;
use OCA\Filinq\Service\ProhibitionGateService;
use OCA\Filinq\Service\ProhibitionPolicyService;
use OCA\Filinq\Service\RelationSkipDecisionService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\WooProfileService;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

/**
 * Standing-consent hints: single-file policy pass, batch consolidation and the matcher.
 *
 * Uses a real PolicyMatchService with a seeded rule cache, so the prohibition
 * precedence under test is the matcher's own, not a stub's.
 *
 * @covers \OCA\Filinq\Service\PolicyMatchService
 * @covers \OCA\Filinq\Service\ProhibitionPolicyService
 * @covers \OCA\Filinq\Service\EntityConsolidationService
 * @covers \OCA\Filinq\Service\PolicyRuleNormaliser
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 * @uses   \OCA\Filinq\Service\PolicyEntity
 * @uses   \OCA\Filinq\Service\PolicyMatchRule
 * @uses   \OCA\Filinq\Service\TextNormaliser
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class StandingConsentMatchTest extends TestCase {

	/**
	 * A prohibition rule for "Beschermde Getuige A".
	 *
	 * @var array<string, mixed>
	 */
	private const PROHIBITION = [
		'uuid' => 'p-1',
		'kind' => PolicyMatchService::KIND_PROHIBITION,
		'entityType' => 'PERSON',
		'matchRules' => [['type' => 'normalized', 'value' => 'Beschermde Getuige A']],
		'validFrom' => null,
		'validUntil' => null,
		'primaryName' => 'Beschermde getuige',
		'bases' => ['woo-art-5-1-2-e'],
	];

	/**
	 * A standing consent for "Gemeente Voorbeeldstad".
	 *
	 * @var array<string, mixed>
	 */
	private const CONSENT = [
		'uuid' => 'c-1',
		'kind' => PolicyMatchService::KIND_STANDING_CONSENT,
		'entityType' => 'ORGANIZATION',
		'matchRules' => [['type' => 'normalized', 'value' => 'Gemeente Voorbeeldstad']],
		'validFrom' => null,
		'validUntil' => null,
		'primaryName' => 'Gemeente Voorbeeldstad',
		'bases' => ['woo-art-5-1-1-c'],
	];

	/**
	 * Build a real matcher with the given rules seeded into its cache.
	 *
	 * @param array<int, array<string, mixed>> $rules Rule-cache contents.
	 *
	 * @return PolicyMatchService The matcher.
	 */
	private function matcher(array $rules): PolicyMatchService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);
		$matcher = new PolicyMatchService(
			$this->createMock(LoggerInterface::class),
			$config,
			$this->createMock(ObjectServiceInterface::class)
		);
		$cache = new ReflectionProperty(PolicyMatchService::class, 'rulesCache');
		$cache->setAccessible(true);
		$cache->setValue($matcher, $rules);

		return $matcher;
	}//end matcher()

	/**
	 * Build the single-file policy service over a matcher and a relation mapper.
	 *
	 * @param PolicyMatchService $matcher The matcher the container hands out.
	 *
	 * @return ProhibitionPolicyService The service under test.
	 */
	private function policyService(PolicyMatchService $matcher): ProhibitionPolicyService {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($matcher);

		return new ProhibitionPolicyService(
			logger: $this->createMock(LoggerInterface::class),
			container: $container,
			locator: $this->createMock(OpenRegisterServiceLocator::class),
			gate: $this->createMock(ProhibitionGateService::class),
			skipDecisions: $this->createMock(RelationSkipDecisionService::class)
		);
	}//end policyService()

	/**
	 * A relation mapper double that records skip writes.
	 *
	 * @return object The mapper.
	 */
	private function relationMapper(): object {
		return new class {
			/**
			 * Relation ids written with skipAnonymization.
			 *
			 * @var list<int>
			 */
			public array $skipped = [];

			/**
			 * Return a relation stub.
			 *
			 * @param int $id Relation id.
			 *
			 * @return object The relation.
			 */
			public function find(int $id): object {
				$relation = new \stdClass();
				$relation->id = $id;
				return $relation;
			}

			/**
			 * Record a decision write.
			 *
			 * @param object $relation The relation.
			 * @param array<string, mixed> $metadata The decision fields.
			 *
			 * @return void
			 */
			public function updateDecisionMetadata(object $relation, array $metadata): void {
				if (($metadata['skipAnonymization'] ?? false) === true) {
					$this->skipped[] = $relation->id;
				}
			}
		};
	}//end relationMapper()

	/**
	 * A standing-consent winner carries the hint, is pre-excluded and gets the rule's bases.
	 *
	 * @return void
	 */
	public function testAStandingConsentMatchPreExcludesTheEntity(): void {
		$mapper = $this->relationMapper();
		$result = $this->policyService(matcher: $this->matcher(rules: [self::CONSENT]))->applyPolicyDecisions(
			entities: [['type' => 'ORGANIZATION', 'value' => 'Gemeente Voorbeeldstad', 'confidence' => 0.9, 'relationId' => 7, 'bases' => null]],
			entityRelationMapper: $mapper
		);

		$this->assertSame(['ruleId' => 'c-1', 'ruleName' => 'Gemeente Voorbeeldstad'], $result[0]['standingConsentMatch']);
		$this->assertNull($result[0]['prohibitionMatch']);
		$this->assertFalse($result[0]['included']);
		$this->assertSame(['woo-art-5-1-1-c'], $result[0]['bases']);
		$this->assertSame('rule', $result[0]['basesSource']);
		$this->assertSame([7], $mapper->skipped);
		$this->assertTrue($result[0]['skipAnonymization']);
	}//end testAStandingConsentMatchPreExcludesTheEntity()

	/**
	 * A prohibition winner is pre-included; its rule bases fill an empty decision only.
	 *
	 * @return void
	 */
	public function testAProhibitionMatchPreIncludesAndKeepsExistingBases(): void {
		$service = $this->policyService(matcher: $this->matcher(rules: [self::PROHIBITION]));
		$result = $service->applyPolicyDecisions(
			entities: [
				['type' => 'PERSON', 'value' => 'Beschermde Getuige A', 'confidence' => 0.9, 'relationId' => 1, 'bases' => []],
				['type' => 'PERSON', 'value' => 'Beschermde Getuige A', 'confidence' => 0.5, 'relationId' => 2, 'bases' => ['reviewer-pick']],
			],
			entityRelationMapper: $this->relationMapper()
		);

		$this->assertSame('p-1', $result[0]['prohibitionMatch']['ruleId']);
		$this->assertTrue($result[0]['prohibitionMatch']['highConfidence']);
		$this->assertNull($result[0]['standingConsentMatch']);
		$this->assertTrue($result[0]['included']);
		$this->assertSame(['woo-art-5-1-2-e'], $result[0]['bases']);
		$this->assertSame(['reviewer-pick'], $result[1]['bases']);
		$this->assertArrayNotHasKey('basesSource', $result[1]);
		$this->assertFalse($result[1]['prohibitionMatch']['highConfidence']);
	}//end testAProhibitionMatchPreIncludesAndKeepsExistingBases()

	/**
	 * When both kinds match, the prohibition wins and no standing-consent hint is set.
	 *
	 * @return void
	 */
	public function testTheProhibitionWinsWhenBothKindsMatch(): void {
		$consent = array_merge(self::CONSENT, ['entityType' => 'PERSON', 'matchRules' => self::PROHIBITION['matchRules']]);
		$mapper = $this->relationMapper();
		$result = $this->policyService(matcher: $this->matcher(rules: [$consent, self::PROHIBITION]))->applyPolicyDecisions(
			entities: [['type' => 'PERSON', 'value' => 'Beschermde Getuige A', 'confidence' => 0.9, 'relationId' => 3]],
			entityRelationMapper: $mapper
		);

		$this->assertSame('p-1', $result[0]['prohibitionMatch']['ruleId']);
		$this->assertNull($result[0]['standingConsentMatch']);
		$this->assertTrue($result[0]['included']);
		$this->assertSame([], $mapper->skipped);
	}//end testTheProhibitionWinsWhenBothKindsMatch()

	/**
	 * An unmatched entity gets both hints as null and keeps its shape.
	 *
	 * @return void
	 */
	public function testAnUnmatchedEntityGetsNullHints(): void {
		$result = $this->policyService(matcher: $this->matcher(rules: [self::CONSENT]))->applyPolicyDecisions(
			entities: [['type' => 'PERSON', 'value' => 'Jan Jansen', 'confidence' => 0.9]],
			entityRelationMapper: $this->relationMapper()
		);

		$this->assertNull($result[0]['prohibitionMatch']);
		$this->assertNull($result[0]['standingConsentMatch']);
		$this->assertArrayNotHasKey('included', $result[0]);
	}//end testAnUnmatchedEntityGetsNullHints()

	/**
	 * Without a matcher both hints are still present, as null.
	 *
	 * @return void
	 */
	public function testNoMatcherStillAddsBothKeys(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new \Exception('gone'));
		$service = new ProhibitionPolicyService(
			logger: $this->createMock(LoggerInterface::class),
			container: $container,
			locator: $this->createMock(OpenRegisterServiceLocator::class),
			gate: $this->createMock(ProhibitionGateService::class),
			skipDecisions: $this->createMock(RelationSkipDecisionService::class)
		);

		$result = $service->applyPolicyDecisions(entities: [['value' => 'x']], entityRelationMapper: null);

		$this->assertSame(['value' => 'x', 'prohibitionMatch' => null, 'standingConsentMatch' => null], $result[0]);
	}//end testNoMatcherStillAddsBothKeys()

	/**
	 * The standing-consent hint carries the rule's id, name and bases; a prohibition yields none.
	 *
	 * @return void
	 */
	public function testMatchStandingConsentHint(): void {
		$matcher = $this->matcher(rules: [self::CONSENT, self::PROHIBITION]);

		$this->assertSame(
			['ruleId' => 'c-1', 'ruleName' => 'Gemeente Voorbeeldstad', 'bases' => ['woo-art-5-1-1-c']],
			$matcher->matchStandingConsentHint(entityType: 'ORGANIZATION', entityValue: 'gemeente voorbeeldstad')
		);
		$this->assertNull($matcher->matchStandingConsentHint(entityType: 'PERSON', entityValue: 'Beschermde Getuige A'));
		$this->assertNull($matcher->matchStandingConsentHint(entityType: 'PERSON', entityValue: 'Niemand'));
	}//end testMatchStandingConsentHint()

	/**
	 * A rule cached without `bases` (pre-change) still matches, with an empty bases list.
	 *
	 * @return void
	 */
	public function testARuleWithoutBasesStillMatches(): void {
		$legacy = self::CONSENT;
		unset($legacy['bases']);

		$this->assertSame(
			['ruleId' => 'c-1', 'ruleName' => 'Gemeente Voorbeeldstad', 'bases' => []],
			$this->matcher(rules: [$legacy])->matchStandingConsentHint(entityType: 'ORGANIZATION', entityValue: 'Gemeente Voorbeeldstad')
		);
	}//end testARuleWithoutBasesStillMatches()

	/**
	 * The normaliser keeps the string slugs of `bases` and yields [] when absent.
	 *
	 * @return void
	 */
	public function testTheNormaliserReadsBases(): void {
		$normaliser = new PolicyRuleNormaliser();
		$row = ['id' => 'u-1', 'active' => true, 'primaryName' => 'X', 'matchRules' => [['type' => 'exact', 'value' => 'X']]];

		$this->assertSame(['a', 'b'], $normaliser->normaliseRule(kind: 'prohibition', object: $row + ['bases' => ['a', 3, '', 'b']])['bases']);
		$this->assertSame([], $normaliser->normaliseRule(kind: 'prohibition', object: $row)['bases']);
		$this->assertSame([], $normaliser->normaliseRule(kind: 'prohibition', object: $row + ['bases' => 'a'])['bases']);
	}//end testTheNormaliserReadsBases()

	/**
	 * Batch consolidation: consent pre-excludes, prohibition wins, unmatched stays null.
	 *
	 * @return void
	 */
	public function testConsolidatedEntitiesCarryStandingConsentMatch(): void {
		$consentOnPerson = array_merge(self::CONSENT, ['uuid' => 'c-2', 'entityType' => 'PERSON', 'matchRules' => self::PROHIBITION['matchRules']]);
		$mapper = $this->getMockBuilder(\stdClass::class)->addMethods(['findEntitiesForFile'])->getMock();
		$mapper->method('findEntitiesForFile')->willReturn(
			[
				['entity_type' => 'ORGANIZATION', 'entity_value' => 'Gemeente Voorbeeldstad', 'confidence' => 0.95],
				['entity_type' => 'PERSON', 'entity_value' => 'Beschermde Getuige A', 'confidence' => 0.9],
				['entity_type' => 'PERSON', 'entity_value' => 'Jan Jansen', 'confidence' => 0.8],
			]
		);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($mapper);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$woo = $this->createMock(WooProfileService::class);
		$woo->method('shouldAnonymize')->willReturn(true);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueFloat')->willReturn(0.85);
		$bases = $this->createMock(BasesResolverService::class);
		$bases->method('resolveBasesForBatch')->willReturn([]);

		$service = new EntityConsolidationService(
			openRegister: new OpenRegisterAccess(apps: $apps, container: $container),
			logger: $this->createMock(LoggerInterface::class),
			wooProfile: $woo,
			policyMatch: $this->matcher(rules: [self::CONSENT, $consentOnPerson, self::PROHIBITION]),
			basesResolver: $bases,
			appConfig: $appConfig
		);

		$result = $service->consolidateEntities(batch: ['files' => [['fileId' => 1, 'status' => 'extracted']]]);
		$byValue = array_column($result, null, 'value');

		$this->assertSame(['ruleId' => 'c-1', 'ruleName' => 'Gemeente Voorbeeldstad'], $byValue['Gemeente Voorbeeldstad']['standingConsentMatch']);
		$this->assertFalse($byValue['Gemeente Voorbeeldstad']['included']);
		$this->assertNull($byValue['Beschermde Getuige A']['standingConsentMatch']);
		$this->assertSame('p-1', $byValue['Beschermde Getuige A']['prohibitionMatch']['ruleId']);
		$this->assertTrue($byValue['Beschermde Getuige A']['included']);
		$this->assertNull($byValue['Jan Jansen']['standingConsentMatch']);
		$this->assertTrue($byValue['Jan Jansen']['included']);
	}//end testConsolidatedEntitiesCarryStandingConsentMatch()
}//end class
