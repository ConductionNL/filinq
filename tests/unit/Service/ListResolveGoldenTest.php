<?php

/**
 * Golden test for ListReferenceResolver::resolveList: the query it sends and the rows it returns.
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
use OCA\Filinq\Service\ListReferenceResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;

/**
 * Every list reference through resolveList(), compared with JSON recorded
 * from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\ListReferenceResolver
 */
class ListResolveGoldenTest extends TestCase {

	/**
	 * The list reference, and the rows the search answers ('fails' for a failing search).
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: mixed}>
	 */
	public static function scenarios(): array {
		$base = ['register' => 'zaken', 'schema' => 'zaak'];
		$rows = [['id' => 1], 'json', ['id' => 2], 'plain'];
		return [
			'defaults' => [$base, $rows],
			'a filter' => [$base + ['filter' => ['status' => 'open']], $rows],
			'a limit' => [$base + ['limit' => 5], []],
			'a string limit' => [$base + ['limit' => '7'], []],
			'a null limit' => [$base + ['limit' => null], []],
			'an order' => [$base + ['order' => ['created' => 'desc']], []],
			'an order not an array' => [$base + ['order' => 'created'], []],
			'a filter that sets a limit' => [$base + ['filter' => ['_limit' => 999, '_order' => ['x' => 'asc']]], []],
			'everything' => [$base + ['filter' => ['a' => 1], 'limit' => 3, 'order' => ['b' => 'asc']], $rows],
			'numeric slugs' => [['register' => 4, 'schema' => 9], []],
			'no results key' => [$base, 'none'],
			'the search fails' => [$base, 'fails'],
		];
	}//end scenarios()

	/**
	 * The calls and the rows, or the failure, match the recorded ones.
	 *
	 * @param array<string, mixed> $ref The list reference.
	 * @param mixed $rows The rows.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testResolveList(array $ref, mixed $rows): void {
		$calls = [];
		$objects = $this->createMock(ObjectService::class);
		$objects->method('setRegister')->willReturnCallback(function ($register) use (&$calls, $objects) {
			$calls[] = ['setRegister', $register];
			return $objects;
		});
		$objects->method('setSchema')->willReturnCallback(function ($schema) use (&$calls, $objects) {
			$calls[] = ['setSchema', $schema];
			return $objects;
		});
		$objects->method('searchObjectsPaginated')->willReturnCallback(function (...$args) use (&$calls, $rows): array {
			$calls[] = ['searchObjectsPaginated', $args];
			if ($rows === 'fails') {
				throw new Exception('index gone');
			}

			if ($rows === 'none') {
				return ['total' => 0];
			}

			return ['results' => array_map(fn ($row) => $this->row($row), $rows)];
		});

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$resolver = new ListReferenceResolver($container, $apps);

		try {
			$items = (new ReflectionMethod($resolver, 'resolveList'))->invoke($resolver, $ref);
			$trace = ['items' => array_map(static fn ($item) => is_object($item) === true ? ['object' => get_object_vars($item)] : $item, $items)];
		} catch (Exception $e) {
			$trace = ['error' => [$e::class, $e->getMessage()]];
		}

		$trace['calls'] = $calls;

		$path = __DIR__ . '/golden/list-resolve.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, json_decode((string)json_encode($trace), true));
	}//end testResolveList()

	/**
	 * One search row: 'json' becomes a serializable object, 'plain' an object without jsonSerialize, anything else stays.
	 *
	 * @param mixed $row The row description.
	 *
	 * @return mixed The row.
	 */
	private function row(mixed $row): mixed {
		if ($row === 'json') {
			return new class implements JsonSerializable {
				/**
				 * A property that differs from the serialized form.
				 *
				 * @var string
				 */
				public string $id = 'unserialized';

				/**
				 * The serialized row.
				 *
				 * @return array<string, mixed>
				 */
				public function jsonSerialize(): array {
					return ['id' => 'serialized'];
				}
			};
		}

		if ($row === 'plain') {
			$object = new \stdClass();
			$object->id = 'plain';
			return $object;
		}

		return $row;
	}//end row()
}//end class
