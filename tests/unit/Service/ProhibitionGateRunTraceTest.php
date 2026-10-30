<?php

/**
 * Trace tests for the prohibition gate: what it refuses, logs and records.
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
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\ProhibitionGateService;
use OCA\Filinq\Service\ProhibitionOverrideAuthority;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Every path through run(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\ProhibitionGateService
 * @uses   \OCA\Filinq\Exception\ProhibitionGateException
 * @uses   \OCA\Filinq\Service\OpenRegisterServiceLocator
 * @uses   \OCA\Filinq\Service\ProhibitionOverrideCommitter
 */
class ProhibitionGateRunTraceTest extends TestCase {

	/**
	 * The audit entries written.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $audits = [];

	/**
	 * The scenarios: the world, the request entities and the overrides.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: array<int, array<string, mixed>>}>
	 */
	public static function scenarios(): array {
		$override = [['ruleId' => 'R-X', 'entityId' => 42, 'reason' => 'press office approved']];
		return [
			'no policy service, fail closed' => [['policy' => false], [], []],
			'no policy service, fail open' => [['policy' => false, 'failClosed' => false], [], []],
			'no entities, fail closed' => [['entities' => 'throw'], [], []],
			'no entities, fail open' => [['entities' => 'throw', 'failClosed' => false], [], []],
			'nothing prohibited' => [['hint' => null], [], []],
			'a high match left out' => [['confidence' => 0.95], [], []],
			'a high match in the request' => [['confidence' => 0.95], [['value' => 'JANSEN']], []],
			'a high match named by text' => [['confidence' => 0.95], [['text' => 'jansen']], []],
			'a high match with an override' => [['confidence' => 0.95], [], $override],
			'a low match without an override' => [['confidence' => 0.5], [], []],
			'a low match released' => [['confidence' => 0.5], [], $override],
			'a low match, override without the role' => [['confidence' => 0.5, 'mayOverride' => false], [], $override],
			'an override for another entity' => [['confidence' => 0.95], [], [['ruleId' => 'R-X', 'entityId' => 41]]],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param array<string, mixed>             $world           How the collaborators answer.
	 * @param array<int, array<string, mixed>> $requestEntities The entities the request anonymises.
	 * @param array<int, array<string, mixed>> $overrides       The overrides.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(array $world, array $requestEntities, array $overrides): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$trace = [];
		try {
			$this->gate($world, $logger)->run(fileId: 99, requestEntities: $requestEntities, overrides: $overrides, userId: 'anna');
			$trace['passed'] = true;
		} catch (ProhibitionGateException $e) {
			$trace['refused'] = [$e->getMissingProhibitionMatches(), $e->getRejectedOverrides(), $e->getBackendUnavailable()];
		}

		$trace['logs'] = $logger->lines;
		$trace['audits'] = array_map(
			static fn (array $audit): array => array_diff_key($audit, array_flip(['acknowledgedAt', 'createdAt', 'timestamp'])),
			$this->audits
		);

		$path = __DIR__ . '/golden/prohibition-gate-run-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testTrace()

	/**
	 * The gate over doubles.
	 *
	 * @param array<string, mixed> $world  How the collaborators answer.
	 * @param AbstractLogger       $logger The logger.
	 *
	 * @return ProhibitionGateService
	 */
	private function gate(array $world, AbstractLogger $logger): ProhibitionGateService {
		$matcher = $this->createMock(originalClassName: PolicyMatchService::class);
		$matcher->method('matchProhibitionHint')->willReturn(
			array_key_exists('hint', $world) === true ? $world['hint'] : ['ruleId' => 'R-X', 'ruleName' => 'Undercover']
		);

		$mapper = $this->createMock(originalClassName: EntityRelationMapper::class);
		if (($world['entities'] ?? '') === 'throw') {
			$mapper->method('findEntitiesForFile')->willThrowException(new RuntimeException('mapper offline'));
		} else {
			$mapper->method('findEntitiesForFile')->willReturn(
				[['relation_id' => 7, 'entity_id' => 42, 'entity_value' => 'Jansen', 'entity_type' => 'PERSON', 'confidence' => ($world['confidence'] ?? 0.5)]]
			);
		}

		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = []): array {
				$this->audits[] = $object;
				return $object;
			}
		);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($matcher, $mapper, $objects, $world): mixed {
				if ($id === 'OCA\Filinq\Service\PolicyMatchService' && ($world['policy'] ?? true) === false) {
					throw new RuntimeException('no policy service');
				}

				return match ($id) {
					'OCA\Filinq\Service\PolicyMatchService' => $matcher,
					'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
					'OCA\OpenRegister\Service\ObjectService' => $objects,
					default => null,
				};
			}
		);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueFloat')->willReturnArgument(2);
		$config->method('getValueBool')->willReturnCallback(
			static fn (string $app, string $key, bool $default = false): bool => $world['failClosed'] ?? $default
		);
		$config->method('getValueString')->willReturnArgument(2);

		$authority = $this->createMock(originalClassName: ProhibitionOverrideAuthority::class);
		$authority->method('mayOverride')->willReturnCallback(
			static fn (string $userId): bool => $userId === 'anna' && ($world['mayOverride'] ?? true)
		);

		return new ProhibitionGateService(
			logger: $logger,
			appConfig: $config,
			container: $container,
			locator: new OpenRegisterServiceLocator(appManager: $apps, container: $container),
			authority: $authority
		);
	}//end gate()
}//end class
