<?php

/**
 * Where AnonymizationLinkReader takes a link's uuid from.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Pins the uuid of a link read from an entity, an array row and the stored payload.
 */
class AnonymizationLinkUuidTest extends TestCase {

	/**
	 * Read one row through the private reader.
	 *
	 * @param mixed $row The stored row.
	 *
	 * @return array<string, mixed>
	 */
	private function withUuid(mixed $row): array {
		$reader = new AnonymizationLinkReader(
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(LoggerInterface::class)
		);

		return (new ReflectionMethod($reader, 'withUuid'))->invoke($reader, $row);

	}//end withUuid()

	/**
	 * The payload's own uuid wins; else the entity uuid, else the row's ids; else the payload's `@self` id.
	 *
	 * @return void
	 */
	public function testTheUuidSource(): void {
		$entity = new class {
			public function getUuid(): string { return 'entity-uuid'; }
			public function jsonSerialize(): array { return ['sourceFileId' => 7]; }
		};
		$blankEntity = new class {
			public function getUuid(): string { return ''; }
			public function jsonSerialize(): array { return ['@self' => ['id' => 'self-id']]; }
		};

		$this->assertSame('entity-uuid', $this->withUuid($entity)['uuid']);
		$this->assertSame('self-id', $this->withUuid($blankEntity)['uuid']);
		$this->assertSame('own', $this->withUuid(['uuid' => 'own', '@self' => ['id' => 'self']])['uuid']);
		$this->assertSame('row-self', $this->withUuid(['@self' => ['id' => 'row-self'], 'id' => 'i'])['uuid']);
		$this->assertSame('i', $this->withUuid(['id' => 'i'])['uuid']);
		$this->assertSame('', $this->withUuid(['sourceFileId' => 3])['uuid']);
		$this->assertSame(['uuid' => ''], $this->withUuid('nothing'));

	}//end testTheUuidSource()
}//end class
