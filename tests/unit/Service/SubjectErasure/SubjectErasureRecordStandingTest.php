<?php

/**
 * Unit tests for SubjectErasureRecordStanding.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRecordStanding;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Whether the record holding a file keeps it from erasure.
 *
 * @covers \OCA\Filinq\Service\SubjectErasure\SubjectErasureRecordStanding
 */
class SubjectErasureRecordStandingTest extends TestCase {

	/**
	 * A record uuid.
	 *
	 * @var string
	 */
	private const UUID = '3f2b8c1e-9a4d-4e6f-8b2a-1c3d5e7f9a0b';

	/**
	 * The record loader double.
	 *
	 * @var LegalHoldRecordFreeze&MockObject
	 */
	private LegalHoldRecordFreeze $records;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->records = $this->createMock(LegalHoldRecordFreeze::class);
	}//end setUp()

	/**
	 * A file inside a folder with the given name.
	 *
	 * @param string $folder The folder name.
	 *
	 * @return File
	 */
	private function fileIn(string $folder): File {
		$parent = $this->createMock(Folder::class);
		$parent->method('getName')->willReturn($folder);
		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);

		return $file;
	}//end fileIn()

	/**
	 * A record entity with the given retention block.
	 *
	 * @param array<string, mixed>|null $retention The retention block.
	 *
	 * @return object
	 */
	private static function record(?array $retention): object {
		return new class($retention) {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed>|null $retention The retention block.
			 */
			public function __construct(private ?array $retention) {
			}//end __construct()

			/**
			 * The retention block.
			 *
			 * @return array<string, mixed>|null
			 */
			public function getRetention(): ?array {
				return $this->retention;
			}//end getRetention()
		};
	}//end record()

	/**
	 * The standing under test.
	 *
	 * @return SubjectErasureRecordStanding
	 */
	private function standing(): SubjectErasureRecordStanding {
		return new SubjectErasureRecordStanding($this->records);
	}//end standing()

	/**
	 * No file, a parent that cannot be read, or a folder that is not a record uuid is clear without a lookup.
	 *
	 * @return void
	 */
	public function testAFileOutsideARecordIsClear(): void {
		$this->records->expects(self::never())->method('load');
		$broken = $this->createMock(File::class);
		$broken->method('getParent')->willThrowException(new RuntimeException('no parent'));

		$clear = ['legal_hold' => false, 'retention' => false];
		self::assertSame($clear, $this->standing()->forFile(null));
		self::assertSame($clear, $this->standing()->forFile($broken));
		self::assertSame($clear, $this->standing()->forFile($this->fileIn('Documents')));
	}//end testAFileOutsideARecordIsClear()

	/**
	 * A record that does not exist, or loads as nothing, is clear.
	 *
	 * @return void
	 */
	public function testAMissingRecordIsClear(): void {
		$this->records->expects(self::exactly(2))->method('load')->with(self::UUID)
			->willReturnOnConsecutiveCalls(self::throwException(new DoesNotExistException('gone')), null);

		$clear = ['legal_hold' => false, 'retention' => false];
		self::assertSame($clear, $this->standing()->forFile($this->fileIn(self::UUID)));
		self::assertSame($clear, $this->standing()->forFile($this->fileIn(self::UUID)));
	}//end testAMissingRecordIsClear()

	/**
	 * A record that cannot be read counts as kept on both grounds; an upper-case uuid folder is looked up as named.
	 *
	 * @return void
	 */
	public function testAnUnreadableRecordCountsAsKept(): void {
		$upper = strtoupper(self::UUID);
		$this->records->expects(self::once())->method('load')->with($upper)->willThrowException(new RuntimeException('db down'));
		$reason = 'the record ' . $upper . ' that holds the file could not be read, so it counts as kept';

		self::assertSame(
			['legal_hold' => $reason, 'retention' => $reason],
			$this->standing()->forFile($this->fileIn($upper))
		);
	}//end testAnUnreadableRecordCountsAsKept()

	/**
	 * An active legal hold names the record and its reason; an inactive one does not.
	 *
	 * @return void
	 */
	public function testAnActiveLegalHoldKeepsTheFile(): void {
		$this->records->method('load')->willReturnOnConsecutiveCalls(
			self::record(['legalHold' => ['active' => true, 'reason' => 'litigation']]),
			self::record(['legalHold' => ['active' => true]]),
			self::record(['legalHold' => ['active' => 'yes', 'reason' => 'x']]),
			self::record(null),
		);

		self::assertSame(['legal_hold' => 'record ' . self::UUID . ' litigation', 'retention' => false], $this->standing()->forFile($this->fileIn(self::UUID)));
		self::assertSame(['legal_hold' => 'record ' . self::UUID, 'retention' => false], $this->standing()->forFile($this->fileIn(self::UUID)));
		self::assertSame(['legal_hold' => false, 'retention' => false], $this->standing()->forFile($this->fileIn(self::UUID)));
		self::assertSame(['legal_hold' => false, 'retention' => false], $this->standing()->forFile($this->fileIn(self::UUID)));
	}//end testAnActiveLegalHoldKeepsTheFile()

	/**
	 * A permanent appraisal keeps the file, read from archiefnominatie before waardering.
	 *
	 * @return void
	 */
	public function testAPermanentAppraisalKeepsTheFile(): void {
		$this->records->method('load')->willReturnOnConsecutiveCalls(
			self::record(['archiefnominatie' => ' Blijvend_Bewaren ']),
			self::record(['waardering' => 'B']),
			self::record(['archiefnominatie' => 'vernietigen', 'waardering' => 'bewaren']),
		);

		self::assertSame(
			['legal_hold' => false, 'retention' => 'record ' . self::UUID . ' is appraised blijvend_bewaren'],
			$this->standing()->forFile($this->fileIn(self::UUID))
		);
		self::assertSame(
			['legal_hold' => false, 'retention' => 'record ' . self::UUID . ' is appraised b'],
			$this->standing()->forFile($this->fileIn(self::UUID))
		);
		self::assertSame(['legal_hold' => false, 'retention' => false], $this->standing()->forFile($this->fileIn(self::UUID)));
	}//end testAPermanentAppraisalKeepsTheFile()
}//end class
