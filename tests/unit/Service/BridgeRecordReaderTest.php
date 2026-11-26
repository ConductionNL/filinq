<?php

/**
 * Unit tests for BridgeRecordReader
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BridgeRecordReader;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;

/**
 * One read of one bridge schema, as the server, flattened to fields; anything
 * but a list of rows reads as no rows.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\BridgeRecordReader
 * @uses   \OCA\Filinq\Service\StoredRecordFields
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BridgeRecordReaderTest extends TestCase {

	/**
	 * A reader over an object service that answers $answer and records what it was asked.
	 *
	 * @param mixed                   $answer What the slug search returns.
	 * @param array<int, array<mixed>> $calls  The recorded calls, by reference.
	 *
	 * @return BridgeRecordReader
	 */
	private function reader(mixed $answer, array &$calls): BridgeRecordReader {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters, bool $_rbac) use ($answer, &$calls): mixed {
				$calls[] = [$registerSlug, $schemaSlug, $filters, $_rbac];
				return $answer;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new BridgeRecordReader(objectResolver: $resolver);

	}//end reader()

	/**
	 * The rows come back as flat fields with their uuid, read in the filinq register without the caller's rights.
	 *
	 * @return void
	 */
	public function testRowsAreReadAsTheServerAndFlattened(): void {
		$calls  = [];
		$reader = $this->reader(
			answer: [
				'a' => ['object' => ['stagedFileRef' => 10, 'vernietigingsdatum' => '2030-01-01'], '@self' => ['uuid' => 'd-1']],
				'b' => ['stagedFileRef' => 11, 'uuid' => 'd-2'],
			],
			calls: $calls
		);

		$rows = $reader->rows(schema: 'externalDocument', filters: ['stagedFileRef' => 10]);

		$this->assertSame(
			[
				['stagedFileRef' => 10, 'vernietigingsdatum' => '2030-01-01', 'uuid' => 'd-1'],
				['stagedFileRef' => 11, 'uuid' => 'd-2'],
			],
			$rows
		);
		$this->assertSame([['filinq', 'externalDocument', ['stagedFileRef' => 10], false]], $calls);

	}//end testRowsAreReadAsTheServerAndFlattened()

	/**
	 * An answer that is not a list of rows reads as no rows.
	 *
	 * @return void
	 */
	public function testAnAnswerThatIsNotAListReadsAsNoRows(): void {
		$calls  = [];
		$reader = $this->reader(answer: 0, calls: $calls);

		$this->assertSame([], $reader->rows(schema: 'bridgeSource', filters: []));
		$this->assertCount(1, $calls);

	}//end testAnAnswerThatIsNotAListReadsAsNoRows()
}//end class
