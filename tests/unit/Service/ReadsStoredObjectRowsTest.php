<?php

/**
 * Unit tests for the stored-object row readings shared by the repositories.
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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ReadsStoredObjectRows;
use PHPUnit\Framework\TestCase;

/**
 * Pins what each ReadsStoredObjectRows reading makes of an OpenRegister row.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\ReadsStoredObjectRows
 */
class ReadsStoredObjectRowsTest extends TestCase {

	/**
	 * The rows every variant is run over.
	 *
	 * @return array<string, mixed>
	 */
	public static function inputs(): array {
		$wrapped = ['object' => ['a' => 1, '@self' => ['x' => 1]], '@self' => ['id' => 's1'], 'id' => 'i1'];
		return [
			'plain with uuid' => ['uuid' => 'u1', 'a' => 1],
			'wrapped' => $wrapped,
			'self id' => ['a' => 1, '@self' => ['id' => 's2'], 'id' => 'i2'],
			'only id' => ['a' => 1, 'id' => 7],
			'no id' => ['a' => 1],
			'field uuid wins' => ['object' => ['uuid' => 'f'], 'uuid' => 'd'],
			'data uuid next' => ['object' => [], 'uuid' => 'd'],
			'object not an array' => ['object' => 'x', 'uuid' => 'u3'],
			'self not an array' => ['a' => 1, '@self' => 'z'],
			'serializable' => new class($wrapped) implements \JsonSerializable {
				public function __construct(
					private array $data,
				) {
				}

				public function jsonSerialize(): mixed {
					return $this->data;
				}
			},
			'serializes to scalar' => new class implements \JsonSerializable {
				public function jsonSerialize(): mixed {
					return 'flat';
				}
			},
			'entity' => new class implements \JsonSerializable {
				public function getObject(): array {
					return ['b' => 2, 'uuid' => 'stale'];
				}

				public function getUuid(): string {
					return 'e1';
				}

				public function jsonSerialize(): mixed {
					return ['rendered' => true, 'uuid' => 'r1'];
				}
			},
			'string' => 'str',
			'null' => null,
			'plain object' => new \stdClass(),
		];
	}

	/**
	 * What each variant makes of each input, recorded from the repositories.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function expected(): array {
		$json = <<<'JSON'
		{
			"fields": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"uuid":"s1"},
				"self id": {"a":1,"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"uuid":""},
				"serializable": {"a":1,"uuid":"s1"},
				"serializes to scalar": {},
				"entity": {"rendered":true,"uuid":"r1"},
				"string": {},
				"null": {},
				"plain object": {}
			},
			"fieldsKeepingSelf": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"@self":{"x":1},"uuid":"s1"},
				"self id": {"a":1,"@self":{"id":"s2"},"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"@self":"z","uuid":""},
				"serializable": {"a":1,"@self":{"x":1},"uuid":"s1"},
				"serializes to scalar": {},
				"entity": {"rendered":true,"uuid":"r1"},
				"string": {},
				"null": {},
				"plain object": {}
			},
			"payload": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"@self":{"x":1}},
				"self id": {"a":1,"@self":{"id":"s2"},"id":"i2"},
				"only id": {"a":1,"id":7},
				"no id": {"a":1},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"@self":"z"},
				"serializable": {"a":1,"@self":{"x":1}},
				"serializes to scalar": {},
				"entity": {"rendered":true,"uuid":"r1"},
				"string": {},
				"null": {},
				"plain object": {}
			},
			"entityFieldsOrBlankUuid": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"uuid":"s1"},
				"self id": {"a":1,"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"uuid":""},
				"serializable": {"a":1,"uuid":"s1"},
				"serializes to scalar": {"uuid":""},
				"entity": {"b":2,"uuid":"e1"},
				"string": {"uuid":""},
				"null": {"uuid":""},
				"plain object": {"uuid":""}
			},
			"entityFields": {
				"plain with uuid": {"uuid":"u1","a":1},
				"wrapped": {"a":1,"uuid":"s1"},
				"self id": {"a":1,"id":"i2","uuid":"s2"},
				"only id": {"a":1,"id":7,"uuid":"7"},
				"no id": {"a":1,"uuid":""},
				"field uuid wins": {"uuid":"f"},
				"data uuid next": {"uuid":"d"},
				"object not an array": {"object":"x","uuid":"u3"},
				"self not an array": {"a":1,"uuid":""},
				"serializable": {"a":1,"uuid":"s1"},
				"serializes to scalar": {},
				"entity": {"b":2,"uuid":"e1"},
				"string": {},
				"null": {},
				"plain object": {}
			}
		}
		JSON;
		return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Each reading, as the repositories call it: [recorded variant, trait method, blank uuid added].
	 *
	 * @return array<string, array{0: string, 1: string, 2: bool}>
	 */
	public static function readings(): array {
		return [
			'fields' => ['fields', 'storedFields', false],
			'fieldsKeepingSelf' => ['fieldsKeepingSelf', 'storedFieldsKeepingSelf', false],
			'payload' => ['payload', 'storedPayload', false],
			'entityFields' => ['entityFields', 'storedEntityFields', false],
			'entityFields, blank uuid (legal hold, subject erasure)' => ['entityFieldsOrBlankUuid', 'storedEntityFields', true],
		];
	}

	/**
	 * @dataProvider readings
	 *
	 * @param string $variant The recorded variant.
	 * @param string $method The trait method under test.
	 * @param bool $blankUuid Whether the caller adds a blank uuid.
	 */
	public function testEveryRowReadsAsRecorded(string $variant, string $method, bool $blankUuid): void {
		$reader = new class {
			use ReadsStoredObjectRows;

			public function read(string $method, mixed $row): array {
				return $this->$method(row: $row);
			}
		};
		foreach (self::inputs() as $name => $input) {
			$read = $reader->read(method: $method, row: $input);
			if ($blankUuid === true) {
				$read += ['uuid' => ''];
			}
			$this->assertSame(self::expected()[$variant][$name], $read, $variant . ': ' . $name);
		}
	}
}
