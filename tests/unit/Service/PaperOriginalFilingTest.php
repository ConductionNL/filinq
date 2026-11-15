<?php

/**
 * Unit tests for PaperOriginalFiling.
 *
 * REQ-PAD-003: the scan of a paper original is filed in the folder of the
 * project's target record, and the original records the file, the batch and
 * what the sampling draw says. OpenRegister is doubled at its object
 * contract, the folder lookup at DossierFileService.
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
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-scan-of-an-original-is-filed-on-the-projects-record-req-pad-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DigitisationProjectService;
use OCA\Filinq\Service\DossierFileService;
use OCA\Filinq\Service\PaperOriginalFiling;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The original po-1 of project prj-1, whose target is dossier dos-1.
 */
class PaperOriginalFilingTest extends TestCase {

	/**
	 * Stored payloads by "register/schema/id".
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $objects = [];

	/**
	 * Saves, as [arguments].
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saves = [];

	private DossierFileService&MockObject $files;

	protected function setUp(): void {
		$this->objects = [
			'filinq/paperOriginal/po-1' => ['project' => 'prj-1', 'box' => 'Doos 12', 'shelfMark' => 'Doos 12 / 3', 'description' => 'Bouwvergunning: Dorpsstraat 4', 'status' => 'registered'],
			'filinq/digitisationProject/prj-1' => ['name' => 'Bouwarchief', 'checkShare' => 100, 'target' => ['register' => 'filinq', 'schema' => 'dossier', 'id' => 'dos-1']],
			'filinq/dossier/dos-1' => ['title' => 'Bouwarchief 1970-1990'],
		];
		$this->files = $this->createMock(DossierFileService::class);
	}//end setUp()

	/**
	 * The filing over the stored objects.
	 *
	 * @param int $checkShare Unused; the project's own share decides.
	 *
	 * @return PaperOriginalFiling
	 */
	private function filing(): PaperOriginalFiling {
		$objects = $this->createMock(ObjectServiceInterface::class);
		$objects->method('find')->willReturnCallback(
			function (int|string $id, ?ObjectFindOptions $options = null, bool $_rbac = true, bool $_multitenancy = true): ?ObjectEntityInterface {
				self::assertFalse($_rbac);
				$register = $options?->register;
				$schema = $options?->schema;
				$payload = ($this->objects[$register . '/' . $schema . '/' . $id] ?? null);
				if ($payload === null) {
					throw new \RuntimeException('Object not found');
				}

				$entity = $this->createMock(ObjectEntityInterface::class);
				$entity->method('getObject')->willReturn($payload);
				return $entity;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, ObjectSaveOptions $options = new ObjectSaveOptions(), bool $_rbac = true, bool $_multitenancy = true): ObjectEntityInterface {
				self::assertFalse($_rbac);
				$this->saves[] = ['object' => $object, 'register' => $options->target->register, 'schema' => $options->target->schema, 'uuid' => $options->uuid];
				return $this->createMock(ObjectEntityInterface::class);
			}
		);

		return new PaperOriginalFiling($objects, $this->files, new DigitisationProjectService(), new NullLogger());
	}//end filing()

	/**
	 * The original, its project and the target record's folder are found.
	 *
	 * @return void
	 */
	public function testThePlaceIsTheFolderOfTheProjectsTarget(): void {
		$folder = $this->createMock(Folder::class);
		$this->files->expects($this->once())->method('homeFolder')
			->with($this->isInstanceOf(ObjectEntityInterface::class), ['title' => 'Bouwarchief 1970-1990'])
			->willReturn($folder);

		$place = $this->filing()->placeFor(originalId: ' po-1 ');

		$this->assertSame($folder, $place['folder']);
		$this->assertSame('po-1', $place['original']['id']);
		$this->assertSame('prj-1', $place['project']['id']);
	}//end testThePlaceIsTheFolderOfTheProjectsTarget()

	/**
	 * A missing original, project, target or folder places nothing.
	 *
	 * @return void
	 */
	public function testAnythingMissingPlacesNothing(): void {
		$this->files->method('homeFolder')->willReturn(null);
		$this->assertNull($this->filing()->placeFor(originalId: 'po-1'), 'the target has no folder');
		$this->assertNull($this->filing()->placeFor(originalId: 'po-missing'));
		$this->assertNull($this->filing()->placeFor(originalId: ''));

		unset($this->objects['filinq/dossier/dos-1']);
		$this->assertNull($this->filing()->placeFor(originalId: 'po-1'), 'the target record is gone');
	}//end testAnythingMissingPlacesNothing()

	/**
	 * The file is named after the shelf mark and description, without characters a file name cannot hold.
	 *
	 * @return void
	 */
	public function testTheFileNameIsTheShelfMarkAndDescription(): void {
		$filing = $this->filing();

		$this->assertSame('Doos 12 - 3 - Bouwvergunning- Dorpsstraat 4.pdf', $filing->fileName(original: $this->objects['filinq/paperOriginal/po-1']));
		$this->assertSame('scan-po-2.pdf', $filing->fileName(original: ['id' => 'po-2']));
	}//end testTheFileNameIsTheShelfMarkAndDescription()

	/**
	 * The scan is recorded on the original: file, batch, moment and the draw's status.
	 *
	 * @return void
	 */
	public function testMarkScannedRecordsTheScanOnTheOriginal(): void {
		$place = ['original' => ['id' => 'po-1', '@self' => ['id' => 'po-1'], 'status' => 'registered'], 'project' => ['checkShare' => 100], 'folder' => $this->createMock(Folder::class)];

		$written = $this->filing()->markScanned(place: $place, scan: ['fileId' => 777, 'pages' => 3, 'batchId' => 'batch-1']);

		$this->assertSame('scanned', $written['status'], 'a 100 percent share puts every scan in the checking queue');
		$this->assertSame(777, $written['document']);
		$this->assertSame('batch-1', $written['scanBatch']);
		$this->assertNotSame('', $written['scannedAt']);
		$this->assertArrayNotHasKey('@self', $written);
		$this->assertSame([['object' => $written, 'register' => 'filinq', 'schema' => 'paperOriginal', 'uuid' => 'po-1']], $this->saves);

		$notDrawn = $this->filing()->markScanned(place: ['project' => ['checkShare' => 0]] + $place, scan: ['fileId' => 778, 'pages' => 3, 'batchId' => 'batch-1']);
		$this->assertSame('checked', $notDrawn['status']);
		$this->assertSame('passed', $notDrawn['check']['result']);
	}//end testMarkScannedRecordsTheScanOnTheOriginal()
}//end class
