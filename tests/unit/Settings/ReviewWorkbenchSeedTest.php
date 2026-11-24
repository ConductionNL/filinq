<?php

/**
 * The review workbench demo data: grondslagen and two rules that pre-apply.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
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
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\PolicyRuleNormaliser;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

/**
 * The seeded prohibition and standing consent are live rules with grondslagen that exist.
 *
 * @covers \OCA\Filinq\Service\PolicyRuleNormaliser
 * @uses   \OCA\Filinq\Service\PolicyMatchService
 * @uses   \OCA\Filinq\Service\PolicyEntity
 * @uses   \OCA\Filinq\Service\PolicyMatchRule
 * @uses   \OCA\Filinq\Service\TextNormaliser
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReviewWorkbenchSeedTest extends TestCase {

	/**
	 * The demo objects of one schema.
	 *
	 * @param string $schema The schema slug.
	 *
	 * @return list<array<string, mixed>> The objects.
	 */
	private static function demo(string $schema): array {
		$descriptor = json_decode(
			(string)file_get_contents(dirname(__DIR__, 3).'/lib/Settings/filinq_mock_register.json'),
			true
		);

		return array_values(
			array_filter(
				$descriptor['components']['objects'],
				static fn (array $row): bool => ($row['@self']['schema'] ?? '') === $schema
			)
		);
	}//end demo()

	/**
	 * Normalise a demo row as the matcher does, with a uuid standing in for the stored one.
	 *
	 * @param string               $kind The rule kind.
	 * @param array<string, mixed> $row  The demo row.
	 *
	 * @return array<string, mixed>|null The rule, or null when it would not be admitted.
	 */
	private static function rule(string $kind, array $row): ?array {
		return (new PolicyRuleNormaliser())->normaliseRule(kind: $kind, object: $row + ['id' => $row['@self']['slug']]);
	}//end rule()

	/**
	 * Every rule with `bases` names grondslagen that are seeded too.
	 *
	 * @return void
	 */
	public function testTheRuleBasesNameSeededGrondslagen(): void {
		$slugs = array_map(static fn (array $base): string => $base['@self']['slug'], self::demo(schema: 'base'));
		$this->assertContains('woo-art-5-1-2-e', $slugs);

		$withBases = 0;
		foreach (['publicationProhibition', 'publicationConsent'] as $schema) {
			foreach (self::demo(schema: $schema) as $row) {
				if (isset($row['bases']) === false) {
					continue;
				}

				$withBases++;
				$this->assertSame([], array_diff($row['bases'], $slugs), $row['@self']['slug']);
			}
		}

		$this->assertSame(2, $withBases);
	}//end testTheRuleBasesNameSeededGrondslagen()

	/**
	 * On a clean install the seeded rules pre-apply: the prohibition and the consent both match.
	 *
	 * @return void
	 */
	public function testTheSeededRulesMatchTheDemoEntities(): void {
		$rules = [];
		foreach (self::demo(schema: 'publicationProhibition') as $row) {
			$rules[] = self::rule(kind: PolicyMatchService::KIND_PROHIBITION, row: $row);
		}

		foreach (self::demo(schema: 'publicationConsent') as $row) {
			if (($row['scope'] ?? '') === 'entity') {
				$rules[] = self::rule(kind: PolicyMatchService::KIND_STANDING_CONSENT, row: $row);
			}
		}

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnArgument(2);
		$matcher = new PolicyMatchService(
			$this->createMock(LoggerInterface::class),
			$config,
			$this->createMock(ObjectServiceInterface::class)
		);
		$cache = new ReflectionProperty(PolicyMatchService::class, 'rulesCache');
		$cache->setAccessible(true);
		$cache->setValue($matcher, array_values(array_filter($rules)));

		$prohibition = $matcher->match(entityText: 'Beschermde Getuige A', entityType: 'PERSON');
		$consent = $matcher->matchStandingConsentHint(entityType: 'ORGANIZATION', entityValue: 'Gemeente Voorbeeldstad');

		$this->assertSame(PolicyMatchService::KIND_PROHIBITION, $prohibition['kind']);
		$this->assertSame(['woo-art-5-1-2-e'], $prohibition['bases']);
		$this->assertSame('Gemeente Voorbeeldstad', $consent['ruleName']);
		$this->assertSame(['woo-art-5-1-2-e'], $consent['bases']);
	}//end testTheSeededRulesMatchTheDemoEntities()
}//end class
