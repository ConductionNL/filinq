<?php

/**
 * Unit tests for how the classification and email-document repositories read a stored row
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Classification;

use JsonSerializable;
use OCA\Filinq\Service\Classification\ClassificationResultRepository;
use OCA\Filinq\Service\EmailIngestion\EmailDocumentRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins normalise(): the payload under `object` (or the row itself), without
 * `@self`, with the uuid taken from the first place that has one.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Classification
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class StoredRowNormaliseCharacterisationTest extends TestCase {

	/**
	 * Every row shape and the fields it normalises to.
	 *
	 * @return array<string,array{0:mixed,1:array<string,mixed>}>
	 */
	public static function rows(): array {
		return [
			'not a row'              => ['x', ['uuid' => '']],
			'null'                   => [null, ['uuid' => '']],
			'empty row'              => [[], ['uuid' => '']],
			'payload uuid wins'      => [['object' => ['uuid' => 'p', 'a' => 1], 'uuid' => 'r', '@self' => ['id' => 's'], 'id' => 'i'], ['uuid' => 'p', 'a' => 1]],
			'row uuid next'          => [['object' => ['a' => 1], 'uuid' => 'r', '@self' => ['id' => 's'], 'id' => 'i'], ['a' => 1, 'uuid' => 'r']],
			'self id next'           => [['object' => ['a' => 1], '@self' => ['id' => 's'], 'id' => 'i'], ['a' => 1, 'uuid' => 's']],
			'row id last'            => [['object' => ['a' => 1], 'id' => 7], ['a' => 1, 'uuid' => '7']],
			'flat row drops @self'   => [['a' => 1, '@self' => ['id' => 's']], ['a' => 1, 'uuid' => 's']],
			'non-array object field' => [['object' => 'x', 'a' => 1], ['object' => 'x', 'a' => 1, 'uuid' => '']],
		];

	}//end rows()

	/**
	 * Both repositories normalise every row shape the same way.
	 *
	 * @param mixed                $row      The stored row.
	 * @param array<string,mixed> $expected The normalised fields.
	 *
	 * @return void
	 */
	#[DataProvider('rows')]
	public function testNormalise(mixed $row, array $expected): void {
		foreach ([ClassificationResultRepository::class, EmailDocumentRepository::class] as $class) {
			$this->assertSame($expected, $this->normalise(new ReflectionClass($class), $row), $class);
		}

	}//end testNormalise()

	/**
	 * A serialisable entity is read through jsonSerialize().
	 *
	 * @return void
	 */
	public function testSerialisableEntity(): void {
		$entity = new class implements JsonSerializable {

			/**
			 * The stored row.
			 *
			 * @return array<string,mixed>
			 */
			public function jsonSerialize(): array {
				return ['object' => ['a' => 1], '@self' => ['id' => 's']];

			}//end jsonSerialize()
		};
		foreach ([ClassificationResultRepository::class, EmailDocumentRepository::class] as $class) {
			$this->assertSame(['a' => 1, 'uuid' => 's'], $this->normalise(new ReflectionClass($class), $entity), $class);
		}

	}//end testSerialisableEntity()

	/**
	 * Call the repository's private normalise() on an instance built without
	 * its constructor.
	 *
	 * @param ReflectionClass<object> $class The repository.
	 * @param mixed                   $row   The stored row.
	 *
	 * @return mixed The normalised fields.
	 */
	private function normalise(ReflectionClass $class, mixed $row): mixed {
		return $class->getMethod('normalise')->invoke($class->newInstanceWithoutConstructor(), $row);

	}//end normalise()

}//end class
