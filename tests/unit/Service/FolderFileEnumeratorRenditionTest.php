<?php

/**
 * FolderFileEnumerator leaves the review renditions filinq wrote out of a folder batch
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

use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\FolderFileEnumerator;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * A second folder batch over a folder detection already ran on must not take
 * the review renditions (`<name>.pdf` beside a converted source) as documents.
 *
 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-optional-recursive-folder-enumeration-req-ddras-011
 */
class FolderFileEnumeratorRenditionTest extends TestCase {


	/**
	 * A rendition named by its sibling's record is left out, on the flat and the recursive walk.
	 *
	 * @return void
	 */
	public function testARenditionOfASiblingIsLeftOut(): void {
		$folder = $this->folder(
			files: [
				$this->file(id: 10, name: 'brief.txt'),
				$this->file(id: 11, name: 'brief.pdf'),
				$this->file(id: 12, name: 'nota.docx'),
			]
		);
		$enumerator = $this->enumerator(renditions: [10 => ['fileId' => 11]]);

		$this->assertSame(expected: [10, 12], actual: $this->ids(files: $enumerator->enumerate(folder: $folder)));
		$this->assertSame(expected: [10, 12], actual: $this->ids(files: $enumerator->enumerateRecursive(folder: $folder)));

	}//end testARenditionOfASiblingIsLeftOut()


	/**
	 * A person's own PDF beside a same-named source stays in when the record names another file, or none.
	 *
	 * @return void
	 */
	public function testAPdfThatIsNotTheRenditionStays(): void {
		$folder = $this->folder(
			files: [
				$this->file(id: 20, name: 'besluit.docx'),
				$this->file(id: 21, name: 'besluit.pdf'),
				$this->file(id: 30, name: 'brief.txt'),
				$this->file(id: 31, name: 'brief.pdf'),
			]
		);
		$enumerator = $this->enumerator(renditions: [20 => ['fileId' => 99]]);

		$this->assertSame(expected: [20, 21, 30, 31], actual: $this->ids(files: $enumerator->enumerate(folder: $folder)));

	}//end testAPdfThatIsNotTheRenditionStays()


	/**
	 * Without the repository the listing is unchanged.
	 *
	 * @return void
	 */
	public function testWithoutTheRepositoryNothingIsLeftOut(): void {
		$folder = $this->folder(files: [$this->file(id: 10, name: 'brief.txt'), $this->file(id: 11, name: 'brief.pdf')]);
		$enumerator = new FolderFileEnumerator(
			layout: $this->createMock(originalClassName: OutputLayoutResolver::class),
			confidentialityLabel: $this->createMock(originalClassName: ConfidentialityLabelService::class),
			appConfig: $this->createMock(originalClassName: IAppConfig::class)
		);

		$this->assertSame(expected: [10, 11], actual: $this->ids(files: $enumerator->enumerate(folder: $folder)));

	}//end testWithoutTheRepositoryNothingIsLeftOut()


	/**
	 * The enumerator with a rendition repository answering from a map.
	 *
	 * @param array<int, array<string, mixed>> $renditions Rendition record by source file id.
	 *
	 * @return FolderFileEnumerator
	 */
	private function enumerator(array $renditions): FolderFileEnumerator {
		$repository = $this->createMock(originalClassName: ReviewRenditionRepository::class);
		$repository->method('findFor')->willReturnCallback(
			static fn (int $sourceFileId): ?array => ($renditions[$sourceFileId] ?? null)
		);

		return new FolderFileEnumerator(
			layout: $this->createMock(originalClassName: OutputLayoutResolver::class),
			confidentialityLabel: $this->createMock(originalClassName: ConfidentialityLabelService::class),
			appConfig: $this->createMock(originalClassName: IAppConfig::class),
			renditions: $repository
		);

	}//end enumerator()


	/**
	 * A folder listing these files.
	 *
	 * @param File[] $files The files.
	 *
	 * @return Folder
	 */
	private function folder(array $files): Folder {
		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('getDirectoryListing')->willReturn($files);
		return $folder;

	}//end folder()


	/**
	 * A file.
	 *
	 * @param int    $id   Its id.
	 * @param string $name Its name.
	 *
	 * @return File
	 */
	private function file(int $id, string $name): File {
		$file = $this->createMock(originalClassName: File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		return $file;

	}//end file()


	/**
	 * The ids of files.
	 *
	 * @param File[] $files The files.
	 *
	 * @return int[]
	 */
	private function ids(array $files): array {
		return array_map(static fn (File $file): int => (int) $file->getId(), array_values($files));

	}//end ids()
}//end class
