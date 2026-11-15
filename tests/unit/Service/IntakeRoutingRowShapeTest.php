<?php

/**
 * How IntakeRoutingService reads a stored routing row
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
 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRoutingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Pins every row shape the routing lookup accepts and where the uuid comes from.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class IntakeRoutingRowShapeTest extends TestCase {

	/**
	 * Normalise one row through the private reader.
	 *
	 * @param mixed $row The stored row.
	 *
	 * @return array<string, mixed>|null
	 */
	private function normalise(mixed $row): ?array {
		$service = new IntakeRoutingService(
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(LoggerInterface::class)
		);

		return (new ReflectionMethod($service, 'normalise'))->invoke($service, $row);

	}//end normalise()

	/**
	 * Arrays, serialisable objects and wrapped `object` payloads are read; anything else is not.
	 *
	 * @return void
	 */
	public function testRowShapes(): void {
		$serialisable = new class {
			public function jsonSerialize(): array { return ['object' => ['team' => 'a'], 'uuid' => 'outer']; }
		};
		$scalarSerialisable = new class {
			public function jsonSerialize(): string { return 'x'; }
		};

		$this->assertSame(['team' => 'a', 'uuid' => 'outer'], $this->normalise($serialisable));
		$this->assertNull($this->normalise($scalarSerialisable));
		$this->assertNull($this->normalise(new \stdClass()));
		$this->assertNull($this->normalise('row'));
		$this->assertNull($this->normalise(null));
		$this->assertSame(['object' => 'text', 'uuid' => ''], $this->normalise(['object' => 'text']));
		$this->assertSame(['team' => 'b', 'uuid' => ''], $this->normalise(['team' => 'b']));

	}//end testRowShapes()

	/**
	 * The uuid comes from the fields, then the row, then `@self`, then `id`, and is always a string.
	 *
	 * @return void
	 */
	public function testUuidOrder(): void {
		$this->assertSame(['uuid' => 'f'], $this->normalise(['object' => ['uuid' => 'f'], 'uuid' => 'r', 'id' => 'i']));
		$this->assertSame(['uuid' => 'r'], $this->normalise(['object' => [], 'uuid' => 'r', '@self' => ['id' => 's']]));
		$this->assertSame(['uuid' => 's'], $this->normalise(['object' => [], '@self' => ['id' => 's'], 'id' => 'i']));
		$this->assertSame(['uuid' => 'i'], $this->normalise(['object' => [], 'id' => 'i']));
		$this->assertSame(['id' => 42, 'uuid' => '42'], $this->normalise(['id' => 42]));
		$this->assertSame(['uuid' => 'r', '@self' => ['id' => 's']], $this->normalise(['uuid' => 'r', '@self' => ['id' => 's']]));

	}//end testUuidOrder()
}//end class
