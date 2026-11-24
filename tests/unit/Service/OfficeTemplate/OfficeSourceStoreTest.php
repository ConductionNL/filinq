<?php

/**
 * Office template sources are kept in Filinq's app data folder, named by
 * checksum, stored once per content, and read back by file id.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore
 */
class OfficeSourceStoreTest extends TestCase {

	private IRootFolder&MockObject $root;

	private Folder&MockObject $sources;

	/**
	 * A root whose app data holds `filinq/office-templates`; `filinq` exists
	 * already and `office-templates` is made on first use.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->root = $this->createMock(IRootFolder::class);
		$appData = $this->createMock(Folder::class);
		$filinq = $this->createMock(Folder::class);
		$this->sources = $this->createMock(Folder::class);
		$this->root->method('getAppDataDirectoryName')->willReturn('appdata_oc123');
		$this->root->method('get')->with('appdata_oc123')->willReturn($appData);
		$appData->method('get')->with('filinq')->willReturn($filinq);
		$filinq->method('get')->with('office-templates')->willThrowException(new NotFoundException());
		$filinq->expects($this->atLeastOnce())->method('newFolder')->with('office-templates')->willReturn($this->sources);
	}//end setUp()

	/**
	 * A new content is written under its checksum and its id returned.
	 *
	 * @return void
	 */
	public function testNewContentIsStoredUnderItsChecksum(): void {
		$hash = hash('sha256', 'docx-bytes');
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(77);
		$this->sources->method('get')->with($hash.'.docx')->willThrowException(new NotFoundException());
		$this->sources->expects($this->once())->method('newFile')->with($hash.'.docx', 'docx-bytes')->willReturn($file);

		$this->assertSame(['fileId' => 77, 'contentHash' => $hash], (new OfficeSourceStore($this->root))->store(bytes: 'docx-bytes', extension: 'docx'));
	}//end testNewContentIsStoredUnderItsChecksum()

	/**
	 * Content stored before is not written again; a given name replaces the checksum in the file name.
	 *
	 * @return void
	 */
	public function testKnownContentIsNotWrittenAgain(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(5);
		$this->sources->method('get')->with('docx-hash.odt')->willReturn($file);
		$this->sources->expects($this->never())->method('newFile');

		$result = (new OfficeSourceStore($this->root))->store(bytes: 'odt-bytes', extension: 'odt', name: 'docx-hash');

		$this->assertSame(['fileId' => 5, 'contentHash' => hash('sha256', 'odt-bytes')], $result);
	}//end testKnownContentIsNotWrittenAgain()

	/**
	 * A source is read back by id; an unknown id, or a folder with that id, reads as null.
	 *
	 * @return void
	 */
	public function testASourceIsReadBackById(): void {
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn('docx-bytes');
		$this->sources->method('getById')->willReturnMap([
			[77, [$this->createMock(Folder::class), $file]],
			[78, []],
		]);
		$store = new OfficeSourceStore($this->root);

		$this->assertSame('docx-bytes', $store->read(fileId: 77));
		$this->assertNull($store->read(fileId: 78));
	}//end testASourceIsReadBackById()
}//end class
