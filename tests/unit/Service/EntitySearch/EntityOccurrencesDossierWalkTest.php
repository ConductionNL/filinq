<?php

/**
 * The edges of how EntityOccurrences finds the dossier a file sits in.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EntitySearch\EntityOccurrences;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * No dossiers, a parent that cannot be read, a dossier far up the tree and a walk that never ends.
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntityOccurrences
 */
class EntityOccurrencesDossierWalkTest extends TestCase {

	/**
	 * Ask the private dossier walk.
	 *
	 * @param File                              $node     The file.
	 * @param array<int, array<string, mixed>>  $dossiers The dossiers by folder id.
	 *
	 * @return array<string, mixed>|null
	 */
	private function dossierOf(File $node, array $dossiers): ?array {
		$occurrences = new EntityOccurrences(
			$this->createMock(IRootFolder::class),
			$this->createMock(AnonymizationLinkReader::class),
			$this->createMock(DocumentObjectServiceResolver::class),
			$this->createMock(ContainerInterface::class)
		);

		return (new ReflectionMethod($occurrences, 'dossierOf'))->invoke($occurrences, $node, $dossiers);

	}//end dossierOf()

	/**
	 * A folder with an id whose parent is the given one.
	 *
	 * @param int         $id     The folder id.
	 * @param Folder|null $parent The parent, or null for one that loops to itself.
	 *
	 * @return Folder
	 */
	private function folder(int $id, ?Folder $parent): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn($id);
		$folder->method('getParent')->willReturn($parent ?? $folder);

		return $folder;

	}//end folder()

	/**
	 * Without dossiers the tree is not walked at all.
	 *
	 * @return void
	 */
	public function testNoDossiersMeansNoWalk(): void {
		$file = $this->createMock(File::class);
		$file->expects($this->never())->method('getParent');

		$this->assertNull($this->dossierOf($file, []));

	}//end testNoDossiersMeansNoWalk()

	/**
	 * A dossier is found many levels up, and an unreadable parent answers null.
	 *
	 * @return void
	 */
	public function testADossierFarUpIsFoundAndAFailingParentIsNull(): void {
		$top = $this->folder(7, null);
		$parent = $top;
		for ($id = 100; $id < 163; $id++) {
			$parent = $this->folder($id, $parent);
		}

		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);
		$this->assertSame(['name' => 'top'], $this->dossierOf($file, [7 => ['name' => 'top']]));

		$broken = $this->createMock(File::class);
		$broken->method('getParent')->willThrowException(new RuntimeException('gone'));
		$this->assertNull($this->dossierOf($broken, [7 => ['name' => 'top']]));

	}//end testADossierFarUpIsFoundAndAFailingParentIsNull()

	/**
	 * The walk stops after 64 levels, so a dossier deeper than that is not found.
	 *
	 * @return void
	 */
	public function testTheWalkStopsAfterSixtyFourLevels(): void {
		$top = $this->folder(7, null);
		$parent = $top;
		for ($id = 100; $id < 164; $id++) {
			$parent = $this->folder($id, $parent);
		}

		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);
		$this->assertNull($this->dossierOf($file, [7 => ['name' => 'top']]));

		$loop = $this->folder(5, null);
		$looping = $this->createMock(File::class);
		$looping->method('getParent')->willReturn($loop);
		$this->assertNull($this->dossierOf($looping, [7 => ['name' => 'top']]));

	}//end testTheWalkStopsAfterSixtyFourLevels()
}//end class
