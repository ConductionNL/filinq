<?php

/**
 * Unit tests for PaperOriginalImport.
 *
 * REQ-PAD-001 / design D5: a CSV box list registers one paper original per
 * row through OpenRegister's object API, and a row that cannot be imported is
 * listed with its line and reason while the others are saved.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\PaperOriginalImport;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\PaperOriginalImport
 */
class PaperOriginalImportTest extends TestCase {

	/**
	 * Saves, as [object, register, schema, rbac].
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saves = [];

	/**
	 * The import over an object API that refuses shelf marks in $refused.
	 *
	 * @param array<int, string> $refused Shelf marks OpenRegister refuses.
	 *
	 * @return PaperOriginalImport
	 */
	private function importer(array $refused = []): PaperOriginalImport {
		$objects = $this->createMock(ObjectServiceInterface::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, ?array $extend = [], string|int|null $register = null, string|int|null $schema = null, ?string $uuid = null, bool $_rbac = true) use ($refused): ObjectEntityInterface {
				if (in_array($object['shelfMark'], $refused, true) === true) {
					throw new RuntimeException('You may not create this object');
				}

				$this->saves[] = ['object' => $object, 'register' => $register, 'schema' => $schema, 'uuid' => $uuid, 'rbac' => $_rbac];
				return $this->createMock(ObjectEntityInterface::class);
			}
		);

		return new PaperOriginalImport($objects);
	}//end importer()

	/**
	 * A box list with one bad row imports the others and lists the bad one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/tasks.md#task-3.2
	 */
	public function testABadRowIsListedAndTheOthersImported(): void {
		$csv = "box,shelfMark,description,dateFrom,dateTo\n"
			. "117,117/1,Bouwvergunning Dorpsstraat 4,1987,1992\n"
			. "117,,Zonder signatuur,1990,\n"
			. "117,117/3,Sloopvergunning Kerkweg 2,,\n";

		$result = $this->importer()->import(projectId: 'prj-1', csv: $csv);

		self::assertSame(2, $result['imported']);
		self::assertSame([['line' => 3, 'reason' => 'the shelf mark is empty']], $result['failures']);
		self::assertSame(
			[
				'object' => ['project' => 'prj-1', 'box' => '117', 'shelfMark' => '117/1', 'status' => 'registered', 'description' => 'Bouwvergunning Dorpsstraat 4', 'dateRange' => '1987-1992'],
				'register' => 'filinq',
				'schema' => 'paperOriginal',
				'uuid' => null,
				'rbac' => true,
			],
			$this->saves[0]
		);
		self::assertSame(['project' => 'prj-1', 'box' => '117', 'shelfMark' => '117/3', 'status' => 'registered', 'description' => 'Sloopvergunning Kerkweg 2'], $this->saves[1]['object']);
	}//end testABadRowIsListedAndTheOthersImported()

	/**
	 * A semicolon list with a BOM, header case and blank lines is read, and a
	 * row with the wrong cell count or a refused save is listed.
	 *
	 * @return void
	 */
	public function testASemicolonListAndRefusedRows(): void {
		$csv = "\xEF\xBB\xBFBox;ShelfMark\r\n\r\nA;A-1\r\nA;A-2;extra\r\n;A-3\r\nA;A-4\r\n";

		$result = $this->importer(refused: ['A-4'])->import(projectId: 'prj-1', csv: $csv);

		self::assertSame(1, $result['imported']);
		self::assertSame(
			[
				['line' => 4, 'reason' => 'expected 2 columns, found 3'],
				['line' => 5, 'reason' => 'the box is empty'],
				['line' => 6, 'reason' => 'You may not create this object'],
			],
			$result['failures']
		);
		self::assertSame(['project' => 'prj-1', 'box' => 'A', 'shelfMark' => 'A-1', 'status' => 'registered'], $this->saves[0]['object']);
	}//end testASemicolonListAndRefusedRows()

	/**
	 * A list without a required column, with an unknown column, empty, or
	 * without a project is refused before anything is saved.
	 *
	 * @return void
	 */
	public function testAnUnusableListIsRefused(): void {
		$cases = [
			['prj-1', "box,description\n1,x\n", 'The box list has no "shelfMark" column.'],
			['prj-1', "box,shelfMark,colour\n1,2,3\n", 'Unknown column "colour"; a box list has the columns box, shelfMark, description, dateFrom, dateTo.'],
			['prj-1', "\n \n", 'The box list is empty.'],
			[' ', "box,shelfMark\n1,2\n", 'An import names the project the originals belong to.'],
		];

		foreach ($cases as [$project, $csv, $message]) {
			try {
				$this->importer()->import(projectId: $project, csv: $csv);
				self::fail('Expected a refusal for: ' . $message);
			} catch (InvalidArgumentException $e) {
				self::assertSame($message, $e->getMessage());
			}
		}

		self::assertSame([], $this->saves);
	}//end testAnUnusableListIsRefused()

	/**
	 * A list longer than the cap is refused.
	 *
	 * @return void
	 */
	public function testAListOverTheCapIsRefused(): void {
		$csv = "box,shelfMark\n" . str_repeat("1,2\n", (PaperOriginalImport::MAX_ROWS + 1));

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A box list holds at most 5000 rows.');

		$this->importer()->import(projectId: 'prj-1', csv: $csv);
	}//end testAListOverTheCapIsRefused()
}//end class
