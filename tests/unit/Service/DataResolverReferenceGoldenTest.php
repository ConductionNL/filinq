<?php

/**
 * Golden test for how DataResolverService resolves data references and their nested references.
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
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use JsonSerializable;
use OCA\Filinq\Service\DataResolverService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every reference set through resolve(), compared with JSON recorded from the
 * implementation before resolveReference() was split.
 *
 * @covers \OCA\Filinq\Service\DataResolverService
 * @uses   \OCA\Filinq\Service\ListReferenceResolver
 */
class DataResolverReferenceGoldenTest extends TestCase {

	private const U1 = '11111111-1111-4111-8111-111111111111';
	private const U2 = '22222222-2222-4222-8222-222222222222';
	private const U3 = '33333333-3333-4333-8333-333333333333';

	/**
	 * The data references and the configured depth.
	 *
	 * @return array<string, array{0: array<int, array<string, mixed>>, 1: string}>
	 */
	public static function scenarios(): array {
		$zaak = ['register' => 'zaken', 'schema' => 'zaak', 'id' => self::U1];
		return [
			'one object with a nested reference' => [[$zaak], '3'],
			'depth 0 keeps the reference' => [[$zaak], '0'],
			'depth 1 stops one level down' => [[$zaak], '1'],
			'the same object twice' => [[$zaak, $zaak], '3'],
			'an object that is not found' => [[['register' => 'zaken', 'schema' => 'zaak', 'id' => 'gone']], '3'],
			'a plain array answer' => [[['register' => 'zaken', 'schema' => 'plain', 'id' => 'p1']], '3'],
			'a failing lookup' => [[['register' => 'zaken', 'schema' => 'zaak', 'id' => 'boom']], '3'],
			'a depth that is not a number' => [[$zaak], 'diep'],
			'the default depth' => [[$zaak], 'default'],
		];
	}//end scenarios()

	/**
	 * The resolution, the lookups and the log match the recorded ones.
	 *
	 * @param array<int, array<string, mixed>> $refs The data references.
	 * @param string $depth The configured depth.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testResolve(array $refs, string $depth): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$objects = [
			'zaken/zaak/' . self::U1 => ['id' => self::U1, 'titel' => 'Zaak', '_register' => 'zaken', 'besluit' => self::U2, 'uuid' => self::U3],
			'zaken/besluit/' . self::U2 => ['id' => self::U2, 'titel' => 'Besluit', '_register' => 'zaken', 'document' => self::U3],
			'zaken/document/' . self::U3 => ['id' => self::U3, 'titel' => 'Document', '_register' => 'zaken', 'zaak' => self::U1],
		];
		$calls = [];
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturnCallback(function (string $id = '', string $register = '', string $schema = '') use (&$calls, $objects) {
			$calls[] = [$id, $register, $schema];
			if ($id === 'boom') {
				throw new Exception('register offline');
			}

			if ($id === 'p1') {
				return ['id' => 'p1', 'titel' => 'Plat'];
			}

			$key = $register . '/' . $schema . '/' . $id;
			if (isset($objects[$key]) === false) {
				return null;
			}

			return new class($objects[$key]) implements JsonSerializable {
				/**
				 * Constructor.
				 *
				 * @param array<string, mixed> $data The serialized form.
				 */
				public function __construct(
					private array $data,
				) {
				}

				/**
				 * The serialized form.
				 *
				 * @return array<string, mixed>
				 */
				public function jsonSerialize(): array {
					return $this->data;
				}
			};
		});

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objectService);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'filinq' && $key === 'resolver.max_depth' ? ($depth === 'default' ? $default : $depth) : 'wrong')
		);

		$service = new DataResolverService($container, $apps, $logger, $config);
		$trace = ['result' => $service->resolve($refs), 'calls' => $calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/data-resolver-reference.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testResolve()
}//end class
