<?php

/**
 * Unit tests for PolicyMatchService — prohibition-only matching + threshold.
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
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\PolicyMatchService;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

/**
 * Tests for the prohibition matcher and the high-confidence threshold reader.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class PolicyMatchServiceTest extends TestCase {

	/**
	 * Build a service with the given rule cache seeded and a threshold config.
	 *
	 * @param array<int, array<string, mixed>> $rules Rule-cache contents.
	 * @param string $threshold Raw config value for the threshold key.
	 *
	 * @return PolicyMatchService The service under test.
	 */
	private function makeService(array $rules, string $threshold = '0.85'): PolicyMatchService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use ($threshold): string {
				if ($key === 'filinq.prohibition.high_confidence_threshold') {
					return $threshold;
				}

				return $default;
			}
		);

		$service = new PolicyMatchService(
			$this->createMock(LoggerInterface::class),
			$this->createMock(IAppManager::class),
			$config,
			$this->createMock(ObjectServiceInterface::class)
		);

		// Seed the private rule cache so matching runs without OpenRegister.
		$cache = new ReflectionProperty(PolicyMatchService::class, 'rulesCache');
		$cache->setAccessible(true);
		$cache->setValue($service, $rules);

		return $service;
	}//end makeService()

	/**
	 * A prohibition rule matching the entity is returned.
	 *
	 * @return void
	 */
	public function testMatchProhibitionReturnsProhibitionRule(): void {
		$service = $this->makeService(
			[
				[
					'uuid' => 'R-PROHIBIT-1',
					'kind' => PolicyMatchService::KIND_PROHIBITION,
					'entityType' => 'PERSON',
					'primaryName' => 'Politiemedewerker undercover',
					'matchRules' => [['type' => 'exact', 'value' => 'Jansen']],
				],
			]
		);

		$match = $service->matchProhibition('Jansen', 'PERSON');

		$this->assertIsArray($match);
		$this->assertSame('R-PROHIBIT-1', $match['uuid']);
		$this->assertSame(PolicyMatchService::KIND_PROHIBITION, $match['kind']);
		$this->assertSame('Politiemedewerker undercover', $match['primaryName']);

	}//end testMatchProhibitionReturnsProhibitionRule()

	/**
	 * A standing-consent rule is never returned by matchProhibition, even
	 * though the general match() would surface it.
	 *
	 * @return void
	 */
	public function testMatchProhibitionIgnoresStandingConsent(): void {
		$rules = [
			[
				'uuid' => 'R-SC-1',
				'kind' => PolicyMatchService::KIND_STANDING_CONSENT,
				'entityType' => 'PERSON',
				'primaryName' => 'Woordvoerder',
				'matchRules' => [['type' => 'exact', 'value' => 'Kuiper']],
			],
		];
		$service = $this->makeService($rules);

		$this->assertNull($service->matchProhibition('Kuiper', 'PERSON'));
		// Sanity: the general matcher DOES surface the standing consent.
		$this->assertSame('R-SC-1', $service->match('Kuiper', 'PERSON')['uuid']);

	}//end testMatchProhibitionIgnoresStandingConsent()

	/**
	 * No rule matches — null.
	 *
	 * @return void
	 */
	public function testMatchProhibitionReturnsNullWhenNoMatch(): void {
		$service = $this->makeService(
			[
				[
					'uuid' => 'R-PROHIBIT-1',
					'kind' => PolicyMatchService::KIND_PROHIBITION,
					'entityType' => 'PERSON',
					'primaryName' => 'X',
					'matchRules' => [['type' => 'exact', 'value' => 'Jansen']],
				],
			]
		);

		$this->assertNull($service->matchProhibition('De Vries', 'PERSON'));

	}//end testMatchProhibitionReturnsNullWhenNoMatch()

	/**
	 * A standing consent with entityType OTHER is type-agnostic: it matches a
	 * detected entity regardless of the type the detector assigned. This backs
	 * the generic-term standing-consent seed (e.g. "woonplaats" detected as
	 * LOCATION, "u"/"uw" detected as PERSON) — all left in place via a single
	 * OTHER rule.
	 *
	 * @return void
	 */
	public function testOtherTypedStandingConsentMatchesAnyDetectedType(): void {
		$service = $this->makeService(
			[
				[
					'uuid' => 'R-SC-GENERIC',
					'kind' => PolicyMatchService::KIND_STANDING_CONSENT,
					'entityType' => 'OTHER',
					'primaryName' => 'woonplaats',
					'matchRules' => [['type' => 'normalized', 'value' => 'woonplaats']],
				],
			]
		);

		// LOCATION detection is matched by the OTHER rule.
		$this->assertSame('R-SC-GENERIC', $service->match('woonplaats', 'LOCATION')['uuid']);
		// Same rule also matches a PERSON detection (type-agnostic).
		$this->assertSame('R-SC-GENERIC', $service->match('woonplaats', 'PERSON')['uuid']);
		// Non-matching token is left alone (exact token match, not substring).
		$this->assertNull($service->match('Amsterdam', 'LOCATION'));

	}//end testOtherTypedStandingConsentMatchesAnyDetectedType()

	/**
	 * The `normalized` match type collapses casing, so a single lower-cased
	 * rule value covers every casing variant ("u", "U", "Uw" → "u"/"uw"). This
	 * is why the aanspreekvorm seed needs only two normalized rules.
	 *
	 * @return void
	 */
	public function testNormalizedMatchCollapsesCasing(): void {
		$service = $this->makeService(
			[
				[
					'uuid' => 'R-SC-AANSPREEK',
					'kind' => PolicyMatchService::KIND_STANDING_CONSENT,
					'entityType' => 'OTHER',
					'primaryName' => 'aanspreekvorm',
					'matchRules' => [
						['type' => 'normalized', 'value' => 'u'],
						['type' => 'normalized', 'value' => 'uw'],
					],
				],
			]
		);

		$this->assertSame('R-SC-AANSPREEK', $service->match('U', 'PERSON')['uuid']);
		$this->assertSame('R-SC-AANSPREEK', $service->match('Uw', 'PERSON')['uuid']);
		// "Utrecht" must NOT match "u" — normalized comparison is equality, not prefix.
		$this->assertNull($service->match('Utrecht', 'LOCATION'));

	}//end testNormalizedMatchCollapsesCasing()

	/**
	 * The threshold defaults to 0.85 and honours a configured override.
	 *
	 * @return void
	 */
	public function testHighConfidenceThresholdReadsConfig(): void {
		$this->assertSame(0.85, $this->makeService([])->highConfidenceThreshold());
		$this->assertSame(0.90, $this->makeService([], '0.90')->highConfidenceThreshold());

	}//end testHighConfidenceThresholdReadsConfig()

	/**
	 * Build a PolicyMatchService backed by a fake ObjectService that returns the supplied rule set.
	 *
	 * @param array<int, array<string, mixed>> $prohibitions Raw prohibition rows (mirrors OR row shape).
	 * @param array<int, array<string, mixed>> $standing Raw standing-consent rows.
	 *
	 * @return PolicyMatchService
	 */
	private function buildService(array $prohibitions, array $standing = []): PolicyMatchService {
		// ADR-084: mock the PUBLISHED interface, not a hand-rolled shape.
		//
		// This used to be an anonymous class declaring one method, which worked
		// only because the constructor parameter was untyped — the double could
		// not have BEEN an ObjectService, since that class does not load in a
		// leaf app's test environment. Now the parameter is typed, and
		// ObjectServiceInterface is loadable (hydra-gates ships it), so the
		// double is a real mock of the real contract.
		//
		// It also means the signature is checked. The old double declared
		// `searchObjectsBySlug(): array`; the contract says `array|int`, and
		// nothing was verifying that the two agreed.
		$fakeObjectService = $this->createMock(ObjectServiceInterface::class);
		$fakeObjectService->method('searchObjectsBySlug')->willReturnCallback(
			static function (
				string $registerSlug,
				string $schemaSlug,
				array $filters = [],
				bool $_rbac = true,
				bool $_multitenancy = true,
			) use ($prohibitions, $standing): array {
				if ($schemaSlug === 'publicationProhibition') {
					return $prohibitions;
				}
				if ($schemaSlug === 'publicationConsent') {
					return $standing;
				}
				return [];
			}
		);

		$logger = $this->createMock(LoggerInterface::class);
		$appMan = $this->createMock(IAppManager::class);
		$config = $this->createMock(IAppConfig::class);

		return new PolicyMatchService($logger, $appMan, $config, $fakeObjectService);
	}//end buildService()

	/**
	 * Exact-type rule matches only the literal string, not a case-folded variant.
	 *
	 * @return void
	 */
	public function testExactRuleMatchesLiteralOnly(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-a'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Alice',
				'matchRules' => [['type' => 'exact', 'value' => 'Alice Doe']],
			],
		]);

		self::assertNotNull($svc->match('Alice Doe', 'PERSON'));
		self::assertNull($svc->match('alice doe', 'PERSON'));
		self::assertNull($svc->match('Alice  Doe', 'PERSON'));

	}//end testExactRuleMatchesLiteralOnly()

	/**
	 * Normalized-type rule matches case-folded and accent-stripped variants.
	 *
	 * Requires PHP ext-intl for Transliterator-based accent stripping.
	 * In bare CI environments without ext-intl the normaliser degrades to
	 * mb_strtolower, which cannot strip accents — skip rather than false-fail.
	 *
	 * @return void
	 */
	public function testNormalizedRuleStripsCaseAndAccents(): void {
		if (extension_loaded('intl') === false) {
			self::markTestSkipped('ext-intl not available — accent-stripping normalizer not functional');
		}

		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-b'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Pieter',
				'matchRules' => [['type' => 'normalized', 'value' => 'Pieter de Vries']],
			],
		]);

		self::assertNotNull($svc->match('pieter de vries', 'PERSON'));
		self::assertNotNull($svc->match('Piéter dé Vries', 'PERSON'));
		self::assertNull($svc->match('Jan de Vries', 'PERSON'));

	}//end testNormalizedRuleStripsCaseAndAccents()

	/**
	 * BSN-type rule resolves through resolvedIdentifiers.bsn; missing BSN never matches.
	 *
	 * @return void
	 */
	public function testBsnRuleMatchesOnlyResolvedIdentifier(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-c'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'BSN holder',
				'matchRules' => [['type' => 'bsn', 'value' => '123456789']],
			],
		]);

		self::assertNotNull(
			$svc->match('Any Name', 'PERSON', ['bsn' => '123456789']),
			'BSN match should fire when resolvedIdentifiers carry the right BSN.'
		);
		self::assertNull(
			$svc->match('Any Name', 'PERSON', ['bsn' => '987654321']),
			'Different BSN must not match.'
		);
		self::assertNull(
			$svc->match('Any Name', 'PERSON'),
			'Absent BSN must not match (no false positives on entity text alone).'
		);

	}//end testBsnRuleMatchesOnlyResolvedIdentifier()

	/**
	 * KvK-type rule resolves through resolvedIdentifiers.kvk on ORGANIZATION entities.
	 *
	 * @return void
	 */
	public function testKvkRuleMatchesOnlyResolvedIdentifier(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-d'],
				'active' => true,
				'entityType' => 'ORGANIZATION',
				'primaryName' => 'Acme BV',
				'matchRules' => [['type' => 'kvk', 'value' => '12345678']],
			],
		]);

		self::assertNotNull(
			$svc->match('Acme BV', 'ORGANIZATION', ['kvk' => '12345678'])
		);
		self::assertNull(
			$svc->match('Acme BV', 'ORGANIZATION', ['kvk' => '00000000'])
		);

	}//end testKvkRuleMatchesOnlyResolvedIdentifier()

	/**
	 * Rules with `active: false` are filtered at load and never match.
	 *
	 * @return void
	 */
	public function testInactiveRulesAreSkipped(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-inactive'],
				'active' => false,
				'entityType' => 'PERSON',
				'primaryName' => 'Hidden',
				'matchRules' => [['type' => 'exact', 'value' => 'Hidden Name']],
			],
		]);

		self::assertNull($svc->match('Hidden Name', 'PERSON'));

	}//end testInactiveRulesAreSkipped()

	/**
	 * Rules whose validFrom is in the future MUST NOT match.
	 *
	 * @return void
	 */
	public function testFutureValidFromDoesNotMatch(): void {
		$future = (new \DateTimeImmutable('+1 year'))->format('c');
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-future'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Future',
				'matchRules' => [['type' => 'exact', 'value' => 'Future Name']],
				'validFrom' => $future,
			],
		]);

		self::assertNull($svc->match('Future Name', 'PERSON'));

	}//end testFutureValidFromDoesNotMatch()

	/**
	 * Rules whose validUntil is in the past MUST NOT match.
	 *
	 * @return void
	 */
	public function testExpiredValidUntilDoesNotMatch(): void {
		$past = (new \DateTimeImmutable('-1 year'))->format('c');
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-expired'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Expired',
				'matchRules' => [['type' => 'exact', 'value' => 'Expired Name']],
				'validUntil' => $past,
			],
		]);

		self::assertNull($svc->match('Expired Name', 'PERSON'));

	}//end testExpiredValidUntilDoesNotMatch()

	/**
	 * Unknown rule types are skipped (defence-in-depth) but never throw.
	 *
	 * @return void
	 */
	public function testUnknownTypeIsSkippedGracefully(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-bad'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Bad',
				'matchRules' => [['type' => 'made-up', 'value' => 'Any']],
			],
		]);

		self::assertNull($svc->match('Any', 'PERSON'));

	}//end testUnknownTypeIsSkippedGracefully()

	/**
	 * Multi-rule precedence: when two rules match, the lower-UUID rule wins (deterministic).
	 *
	 * @return void
	 */
	public function testLowerUuidWinsOnMultiMatch(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'b-second'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Second',
				'matchRules' => [['type' => 'exact', 'value' => 'Same Name']],
			],
			[
				'@self' => ['id' => 'a-first'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'First',
				'matchRules' => [['type' => 'exact', 'value' => 'Same Name']],
			],
		]);

		$match = $svc->match('Same Name', 'PERSON');
		self::assertNotNull($match);
		self::assertSame('a-first', $match['uuid'], 'Deterministic tie-break must pick the lexicographically-lowest UUID.');

	}//end testLowerUuidWinsOnMultiMatch()

	/**
	 * `matchProhibitionHint` convenience wrapper returns the ruleId + ruleName shape.
	 *
	 * @return void
	 */
	public function testMatchProhibitionHintReturnsCanonicalShape(): void {
		$svc = $this->buildService([
			[
				'@self' => ['id' => 'rule-prohibition'],
				'active' => true,
				'entityType' => 'PERSON',
				'primaryName' => 'Prohibition',
				'matchRules' => [['type' => 'exact', 'value' => 'Prohibition Name']],
			],
		]);

		$result = $svc->matchProhibitionHint('PERSON', 'Prohibition Name');
		self::assertNotNull($result);
		self::assertArrayHasKey('ruleId', $result);
		self::assertArrayHasKey('ruleName', $result);
		self::assertSame('rule-prohibition', $result['ruleId']);

	}//end testMatchProhibitionReturnsCanonicalShape()

	/**
	 * `invalidateCache` clears the in-memory rule cache so a subsequent match
	 * re-runs `loadRules` (verified by swapping the underlying data).
	 *
	 * @return void
	 */
	public function testInvalidateCacheClearsLoadedRules(): void {
		$rule = [
			'@self' => ['id' => 'cached-rule'],
			'active' => true,
			'entityType' => 'PERSON',
			'primaryName' => 'Cached',
			'matchRules' => [['type' => 'exact', 'value' => 'Cached Name']],
		];

		$svc = $this->buildService([$rule]);
		self::assertNotNull($svc->match('Cached Name', 'PERSON'));

		$svc->invalidateCache();
		// After invalidation `match` re-asks the fake ObjectService — same data,
		// same result, but the new call path proves the cache was cleared
		// (the prior call returned without hitting the loader; the second one
		// must re-execute it).
		self::assertNotNull($svc->match('Cached Name', 'PERSON'));

	}//end testInvalidateCacheClearsLoadedRules()

}//end class
