<?php

/**
 * The reaper, and the two files it must never confuse.
 *
 * 🔴 THE DANGEROUS BUG HERE IS NOT LEAVING A FRAGMENT BEHIND, IT IS TAKING A
 * DOCUMENT WITH IT. A fragment left for one more night costs disk. A document
 * reaped because it happened to be empty, or small, or old, is gone, and the
 * person who uploaded it finds out weeks later. So the tests that matter most
 * are the ones asserting what the reaper LEAVES.
 *
 * 🔑 AND A FRAGMENT THAT COULD NOT BE DELETED MUST NOT BE COUNTED AS REMOVED,
 * or the reclaimed bytes become a number nobody can check against the disk and
 * a read-only mount reports a clean sweep every night for ever.
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
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\UploadFragmentReaper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * `UploadFragmentReaper`.
 *
 * @covers \OCA\Filinq\Service\UploadFragmentReaper
 */
class UploadFragmentReaperTest extends TestCase {

	/**
	 * The moment every test measures age against.
	 *
	 * @var int
	 */
	private const NOW = 1770000000;

	/**
	 * One day, in seconds.
	 *
	 * @var int
	 */
	private const A_DAY = 86400;

	/**
	 * A file double.
	 *
	 * @param string $name     The file name.
	 * @param int    $ageHours How long ago it was last written.
	 * @param int    $size     Its size in bytes.
	 * @param bool   $deletes  Whether delete() succeeds.
	 *
	 * @return File The double.
	 */
	private function file(string $name, int $ageHours, int $size = 1024, bool $deletes = true): File {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn('/admin/files/DocuDesk/' . $name);
		$file->method('getMTime')->willReturn(self::NOW - ($ageHours * 3600));
		$file->method('getSize')->willReturn($size);

		if ($deletes === false) {
			$file->method('delete')->willThrowException(new NotPermittedException('read-only mount'));
		}

		return $file;
	}//end file()

	/**
	 * A folder holding the given children.
	 *
	 * @param array<int, mixed> $children The children.
	 *
	 * @return Folder The double.
	 */
	private function folder(array $children): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn($children);

		return $folder;
	}//end folder()

	/**
	 * A fragment past the declared age is removed, and its bytes are reported.
	 *
	 * @return void
	 */
	public function testReapsFragmentsPastTheDeclaredAge(): void {
		$reaper = new UploadFragmentReaper(new NullLogger());

		$outcome = $reaper->reap(
			folder: $this->folder(
				[
					$this->file('advies.pdf.part', 48, 3000),
					$this->file('bijlage.docx.filepart', 30, 1500),
				]
			),
			maxAgeSeconds: self::A_DAY,
			now: self::NOW
		);

		self::assertSame(2, $outcome['removed']);
		self::assertSame(4500, $outcome['bytes']);
		self::assertSame([], $outcome['refused']);
	}//end testReapsFragmentsPastTheDeclaredAge()

	/**
	 * A document is never a fragment, however old or empty it is.
	 *
	 * @return void
	 */
	public function testLeavesDocumentsAlone(): void {
		$reaper = new UploadFragmentReaper(new NullLogger());

		$never = $this->file('besluit.pdf', 9000, 0);
		$never->expects(self::never())->method('delete');

		$outcome = $reaper->reap(
			folder: $this->folder([$never]),
			maxAgeSeconds: self::A_DAY,
			now: self::NOW
		);

		self::assertSame(0, $outcome['removed']);
		self::assertSame(0, $outcome['bytes']);
	}//end testLeavesDocumentsAlone()

	/**
	 * A fragment still inside the declared age is left for the upload to finish.
	 *
	 * @return void
	 */
	public function testKeepsAFragmentStillInsideTheAge(): void {
		$reaper = new UploadFragmentReaper(new NullLogger());

		$inFlight = $this->file('scan.pdf.part', 2, 800);
		$inFlight->expects(self::never())->method('delete');

		$outcome = $reaper->reap(
			folder: $this->folder([$inFlight]),
			maxAgeSeconds: self::A_DAY,
			now: self::NOW
		);

		self::assertSame(0, $outcome['removed']);
		self::assertSame(1, $outcome['kept']);
	}//end testKeepsAFragmentStillInsideTheAge()

	/**
	 * A fragment that could not be deleted is reported, not counted as removed.
	 *
	 * @return void
	 */
	public function testARefusedDeleteIsNotCountedAsRemoved(): void {
		$reaper = new UploadFragmentReaper(new NullLogger());

		$outcome = $reaper->reap(
			folder: $this->folder([$this->file('advies.pdf.part', 48, 3000, false)]),
			maxAgeSeconds: self::A_DAY,
			now: self::NOW
		);

		self::assertSame(0, $outcome['removed']);
		self::assertSame(0, $outcome['bytes']);
		self::assertCount(1, $outcome['refused']);
		self::assertSame('read-only mount', $outcome['refused'][0]['reason']);
		self::assertStringContainsString('advies.pdf.part', $outcome['refused'][0]['path']);
	}//end testARefusedDeleteIsNotCountedAsRemoved()

	/**
	 * Subfolders are swept too: a fragment two levels down is still a fragment.
	 *
	 * @return void
	 */
	public function testSweepsSubfolders(): void {
		$reaper = new UploadFragmentReaper(new NullLogger());

		$inner = $this->folder([$this->file('nota.pdf.part', 48, 200)]);
		$outer = $this->folder([$inner, $this->file('brief.pdf', 48, 900)]);

		$outcome = $reaper->reap(folder: $outer, maxAgeSeconds: self::A_DAY, now: self::NOW);

		self::assertSame(1, $outcome['removed']);
		self::assertSame(200, $outcome['bytes']);
	}//end testSweepsSubfolders()

	/**
	 * The sync client's transfer marker is recognised mid-name, not only at the tail.
	 *
	 * @return void
	 */
	public function testRecognisesTheTransferMarkerMidName(): void {
		$reaper = new UploadFragmentReaper(new NullLogger());

		self::assertTrue($reaper->isFragmentName(name: 'advies.pdf.ocTransferId873492.part'));
		self::assertTrue($reaper->isFragmentName(name: 'advies.pdf.ocTransferId873492'));
		self::assertFalse($reaper->isFragmentName(name: 'advies.pdf'));
		self::assertFalse($reaper->isFragmentName(name: 'partij-overzicht.pdf'));
	}//end testRecognisesTheTransferMarkerMidName()
}//end class
