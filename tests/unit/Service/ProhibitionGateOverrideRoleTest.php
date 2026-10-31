<?php

/**
 * The anonymise call releases a publish-never match only for the override role.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\ProhibitionGateService;
use OCA\Filinq\Service\ProhibitionOverrideAuthority;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * ProhibitionGateService::run() with an override below the threshold.
 */
class ProhibitionGateOverrideRoleTest extends TestCase {

	/**
	 * prohibitionOverrideAudit entries the fake ObjectService stored.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $audits = [];

	/**
	 * The gate over one prohibited occurrence at confidence 0.5 (below 0.85).
	 *
	 * @param bool $mayOverride Whether the acting user holds the override role.
	 *
	 * @return ProhibitionGateService
	 */
	private function gate(bool $mayOverride): ProhibitionGateService {
		$matcher = $this->createMock(originalClassName: PolicyMatchService::class);
		$matcher->method('matchProhibitionHint')->willReturn(['ruleId' => 'R-X', 'ruleName' => 'Undercover']);

		$mapper = $this->createMock(originalClassName: EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturn(
			[['relation_id' => 7, 'entity_id' => 42, 'entity_value' => 'Jansen', 'entity_type' => 'PERSON', 'confidence' => 0.5]]
		);

		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = []): array {
				$this->audits[] = $object;
				return $object;
			}
		);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id): mixed => match ($id) {
				'OCA\Filinq\Service\PolicyMatchService' => $matcher,
				'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
				'OCA\OpenRegister\Service\ObjectService' => $objects,
				default => null,
			}
		);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueFloat')->willReturnArgument(2);
		$config->method('getValueBool')->willReturnArgument(2);
		$config->method('getValueString')->willReturnArgument(2);

		$authority = $this->createMock(originalClassName: ProhibitionOverrideAuthority::class);
		$authority->method('mayOverride')->willReturn($mayOverride);

		return new ProhibitionGateService(
			logger: $this->createMock(originalClassName: LoggerInterface::class),
			appConfig: $config,
			container: $container,
			locator: new OpenRegisterServiceLocator(appManager: $apps, container: $container),
			authority: $authority
		);

	}//end gate()

	/**
	 * Someone with file access but without the override role is refused, and nothing is recorded.
	 *
	 * @return void
	 */
	public function testAnOverrideWithoutTheRoleIsRejected(): void {
		try {
			$this->gate(mayOverride: false)->run(
				fileId: 99,
				requestEntities: [],
				overrides: [['ruleId' => 'R-X', 'entityId' => 42, 'reason' => 'the press office approved it']],
				userId: 'bob'
			);
			$this->fail(message: 'An override without the role was accepted.');
		} catch (ProhibitionGateException $e) {
			$rejected = $e->getRejectedOverrides();
			$this->assertCount(expectedCount: 1, haystack: $rejected);
			$this->assertSame(expected: 'R-X', actual: $rejected[0]['ruleId']);
			$this->assertStringContainsString(needle: 'policy admin', haystack: $rejected[0]['reason']);
		}

		$this->assertSame(expected: [], actual: $this->audits);

	}//end testAnOverrideWithoutTheRoleIsRejected()

	/**
	 * The override role releases the match and its audit entry is written.
	 *
	 * @return void
	 */
	public function testAnOverrideWithTheRoleIsReleasedAndAudited(): void {
		$this->gate(mayOverride: true)->run(
			fileId: 99,
			requestEntities: [],
			overrides: [['ruleId' => 'R-X', 'entityId' => 42, 'reason' => 'the press office approved it']],
			userId: 'anna'
		);

		$this->assertCount(expectedCount: 1, haystack: $this->audits);
		$this->assertSame(expected: 'anna', actual: $this->audits[0]['acknowledgedBy']);
		$this->assertSame(expected: 'R-X', actual: $this->audits[0]['ruleId']);

	}//end testAnOverrideWithTheRoleIsReleasedAndAudited()
}//end class
