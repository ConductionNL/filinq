<?php

/**
 * Unit tests for SealedFileWriter.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Waarmerk;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\SealedArtifact;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCA\Filinq\Service\WaarmerkService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * The sealed file, then its record, or nothing.
 *
 * @covers \OCA\Filinq\Service\Waarmerk\SealedFileWriter
 * @uses   \OCA\Filinq\Kernel\SealedArtifact
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class SealedFileWriterTest extends TestCase {

	/**
	 * The file is written under a unique name and its id goes on the record.
	 *
	 * @return void
	 */
	public function testWritesThenRecords(): void {
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: ['status' => 'active']);
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(33);
		$file->method('getName')->willReturn('besluit-gewaarmerkt (2).pdf');
		$folder = $this->createMock(Folder::class);
		$folder->method('getNonExistingName')->with('besluit-gewaarmerkt.pdf')->willReturn('besluit-gewaarmerkt (2).pdf');
		$folder->expects($this->once())->method('newFile')->with('besluit-gewaarmerkt (2).pdf', '%PDF sealed')->willReturn($file);
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->expects($this->once())->method('record')->with($artifact, 33)->willReturn(['uuid' => 'w-1']);

		$result = (new SealedFileWriter(waarmerk: $waarmerk))->write(folder: $folder, name: 'besluit-gewaarmerkt.pdf', artifact: $artifact);
		$this->assertSame(['waarmerk' => ['uuid' => 'w-1'], 'fileId' => 33, 'fileName' => 'besluit-gewaarmerkt (2).pdf'], $result);
	}//end testWritesThenRecords()

	/**
	 * When the record fails the written file is deleted and the failure propagates.
	 *
	 * @return void
	 */
	public function testARecordFailureRemovesTheFile(): void {
		$file = $this->createMock(File::class);
		$file->expects($this->once())->method('delete');
		$folder = $this->createMock(Folder::class);
		$folder->method('getNonExistingName')->willReturnArgument(0);
		$folder->method('newFile')->willReturn($file);
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->method('record')->willThrowException(new WaarmerkRefusedException(message: 'The waarmerk could not be stored', status: 503));

		$this->expectExceptionMessage('could not be stored');
		(new SealedFileWriter(waarmerk: $waarmerk))->write(folder: $folder, name: 'x.pdf', artifact: new SealedArtifact(pdf: '%PDF', record: []));
	}//end testARecordFailureRemovesTheFile()
}//end class
