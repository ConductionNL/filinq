<?php

/**
 * Golden tests for the prohibition matches the gate builds from detected entities.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\ProhibitionGateService;
use OCA\Filinq\Service\ProhibitionOverrideAuthority;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * Pins which entities become a prohibition match (refused), which do not (allowed) and the fail mode.
 */
class ProhibitionMatchesGoldenTest extends TestCase {

	/**
	 * How often the fail mode was read.
	 *
	 * @var int
	 */
	private int $failModeReads = 0;

	/**
	 * Build matches through the private builder.
	 *
	 * @param array<int, mixed> $rawEntities The detected entities.
	 * @param object            $policy      The policy service double.
	 * @param bool              $failClosed  The configured fail mode.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function prohibitionMatches(array $rawEntities, object $policy, bool $failClosed = false): array {
		$this->failModeReads = 0;
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueBool')->willReturnCallback(
			function () use ($failClosed): bool {
				$this->failModeReads++;
				return $failClosed;
			}
		);

		$container = $this->createMock(ContainerInterface::class);
		$gate = new ProhibitionGateService(
			logger: $this->createMock(LoggerInterface::class),
			appConfig: $config,
			container: $container,
			locator: new OpenRegisterServiceLocator(appManager: $this->createMock(IAppManager::class), container: $container),
			authority: $this->createMock(ProhibitionOverrideAuthority::class)
		);

		return (new ReflectionMethod($gate, 'buildProhibitionMatches'))->invoke($gate, $rawEntities, $policy);

	}//end prohibitionMatches()

	/**
	 * A policy that prohibits values starting with "X" and throws on "boom".
	 *
	 * @return object
	 */
	private function policy(): object {
		return new class {
			/**
			 * @var array<int, array{0: string, 1: string}>
			 */
			public array $asked = [];

			public function matchProhibitionHint(string $entityType, string $entityValue): ?array {
				$this->asked[] = [$entityType, $entityValue];
				if ($entityValue === 'boom') {
					throw new RuntimeException('policy down');
				}

				if (str_starts_with($entityValue, 'X') === false) {
					return null;
				}

				return ['ruleId' => 7, 'ruleName' => 'Geen X'];
			}
		};

	}//end policy()

	/**
	 * Prohibited entities become matches with every field, in both spellings and both shapes; others do not.
	 *
	 * @return void
	 */
	public function testMatchesAreBuiltFromProhibitedEntities(): void {
		$serialisable = new class {
			public function jsonSerialize(): array {
				return ['entityValue' => 'Xavier', 'entityType' => 'PERSON', 'entityId' => '4', 'relationId' => 40, 'confidence' => '0.5'];
			}
		};
		$policy = $this->policy();

		$matches = $this->prohibitionMatches(
			[
				['entity_value' => 'Xenia', 'entity_type' => 'PERSON', 'entity_id' => 3, 'relation_id' => 30, 'confidence' => 0.9],
				['entity_value' => '', 'entity_type' => 'PERSON'],
				['entity_value' => 'Anna', 'entity_type' => 'PERSON', 'entity_id' => 5],
				$serialisable,
				['entityValue' => 'Xylo'],
			],
			$policy
		);

		$this->assertSame(
			[
				['ruleId' => '7', 'ruleName' => 'Geen X', 'entityId' => 3, 'entityRelationId' => 30, 'confidence' => 0.9, 'entityValue' => 'Xenia'],
				['ruleId' => '7', 'ruleName' => 'Geen X', 'entityId' => 4, 'entityRelationId' => 40, 'confidence' => 0.5, 'entityValue' => 'Xavier'],
				['ruleId' => '7', 'ruleName' => 'Geen X', 'entityId' => 0, 'entityRelationId' => 0, 'confidence' => 0.0, 'entityValue' => 'Xylo'],
			],
			$matches
		);
		$this->assertSame([['PERSON', 'Xenia'], ['PERSON', 'Anna'], ['PERSON', 'Xavier'], ['UNKNOWN', 'Xylo']], $policy->asked);
		$this->assertSame(1, $this->failModeReads);

		$this->assertSame([], $this->prohibitionMatches([], $this->policy()));
		$this->assertSame(1, $this->failModeReads);

	}//end testMatchesAreBuiltFromProhibitedEntities()

	/**
	 * A failing policy skips the entity when the gate fails open and refuses when it fails closed.
	 *
	 * @return void
	 */
	public function testAFailingPolicyFollowsTheFailMode(): void {
		$open = $this->prohibitionMatches([['entity_value' => 'boom'], ['entity_value' => 'Xenia']], $this->policy(), false);
		$this->assertSame(['Xenia'], array_column($open, 'entityValue'));

		$this->expectException(ProhibitionGateException::class);
		$this->prohibitionMatches([['entity_value' => 'boom'], ['entity_value' => 'Xenia']], $this->policy(), true);

	}//end testAFailingPolicyFollowsTheFailMode()
}//end class
