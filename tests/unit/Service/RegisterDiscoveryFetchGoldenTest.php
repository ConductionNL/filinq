<?php

/**
 * Golden test for RegisterDiscoveryService::fetchAvailableRegisters over every register and schema shape.
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
 * @spec openspec/specs/admin-settings/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Error;
use Exception;
use JsonSerializable;
use OCA\Filinq\Service\RegisterDiscoveryService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Service\RegisterService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every register list through fetchAvailableRegisters(), compared with JSON
 * recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\RegisterDiscoveryService
 */
class RegisterDiscoveryFetchGoldenTest extends TestCase {

	/**
	 * The serialized registers findAll answers ('fails' or 'errors'), and the schemas by id
	 * ('missing' throws an Exception, 'broken' an Error).
	 *
	 * @return array<string, array{0: mixed, 1: array<int|string, mixed>}>
	 */
	public static function scenarios(): array {
		$schemas = [
			1 => ['id' => 1, 'title' => 'Zaak', 'properties' => ['a' => []]],
			2 => ['id' => 2, 'title' => 'Besluit'],
			3 => 'missing',
			4 => 'broken',
		];
		return [
			'expanded schemas' => [[['id' => 'r1', 'schemas' => [1, 2]], ['id' => 'r2', 'schemas' => []]], $schemas],
			'an orphan schema' => [[['id' => 'r1', 'schemas' => [1, 3, 2]]], $schemas],
			'a schema that errors' => [[['id' => 'r1', 'schemas' => [1, 4]]], $schemas],
			'no schemas key' => [[['id' => 'r1']], $schemas],
			'schemas not a list' => [[['id' => 'r1', 'schemas' => '1,2']], $schemas],
			'no registers' => [[], $schemas],
			'findAll fails' => ['fails', $schemas],
			'findAll errors' => ['errors', $schemas],
		];
	}//end scenarios()

	/**
	 * The registers, the lookups and the log match the recorded ones.
	 *
	 * @param mixed $registers The serialized registers.
	 * @param array<int|string, mixed> $schemas The schemas by id.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testFetch(mixed $registers, array $schemas): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, without the file and line of the failure.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				unset($context['file'], $context['line']);
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$calls = [];
		$registerService = $this->createMock(RegisterService::class);
		$registerService->method('findAll')->willReturnCallback(function (...$args) use (&$calls, $registers): array {
			$calls[] = ['findAll', $args];
			if ($registers === 'fails') {
				throw new Exception('register table gone');
			}

			if ($registers === 'errors') {
				throw new Error('Call to undefined method');
			}

			return array_map(fn (array $register) => $this->serializable($register), $registers);
		});
		$schemaMapper = $this->createMock(SchemaMapper::class);
		$schemaMapper->method('find')->willReturnCallback(function ($id) use (&$calls, $schemas) {
			$calls[] = ['find', $id];
			$schema = $schemas[$id] ?? 'missing';
			if ($schema === 'missing') {
				throw new Exception('schema ' . $id . ' not found');
			}

			if ($schema === 'broken') {
				throw new Error('schema table broken');
			}

			return $this->serializable($schema);
		});

		$service = new RegisterDiscoveryService($this->createMock(IAppConfig::class), $logger, $registerService, $schemaMapper);
		$trace = ['registers' => $service->fetchAvailableRegisters(), 'calls' => $calls, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/register-discovery-fetch.json';
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
	}//end testFetch()

	/**
	 * An entity double that serializes to the given array.
	 *
	 * @param array<string, mixed> $data The serialized form.
	 *
	 * @return JsonSerializable The double.
	 */
	private function serializable(array $data): JsonSerializable {
		return new class($data) implements JsonSerializable {
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
	}//end serializable()
}//end class
