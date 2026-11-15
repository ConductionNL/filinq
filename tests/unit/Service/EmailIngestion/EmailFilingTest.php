<?php

/**
 * Unit tests for EmailFiling
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EmailIngestion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EmailIngestion;

use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\EmailIngestion\EmailFiling;
use OCA\Filinq\Service\EmailIngestion\EmailNotFiled;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An email is moved into its dossier's folder under a free name; an unreadable
 * dossier, a failed move or a conversion failure each end the way the inbox
 * expects.
 *
 * @covers \OCA\Filinq\Service\EmailIngestion\EmailFiling
 * @uses   \OCA\Filinq\Service\EmailIngestion\EmailNotFiled
 */
class EmailFilingTest extends TestCase {

	/**
	 * The dossier store.
	 *
	 * @var DossierObjectRepository&MockObject
	 */
	private DossierObjectRepository&MockObject $dossiers;

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The PDF conversion.
	 *
	 * @var PdfConversionService&MockObject
	 */
	private PdfConversionService&MockObject $conversion;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The dossier folder (id 40, path /alice/files/Dossier).
	 *
	 * @var Folder&MockObject
	 */
	private Folder&MockObject $folder;

	/**
	 * Set up: dossier d-1 lives in folder 40.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->dossiers = $this->getMockBuilder(DossierObjectRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['loadDossierContext'])
			->getMock();
		$this->dossiers->method('loadDossierContext')->willReturnCallback(static function (string $uuid): array {
			if ($uuid === 'broken') {
				throw new RuntimeException('register down');
			}

			return ['folderRef' => $uuid === 'd-1' ? '40' : '41'];
		});

		$this->folder = $this->createMock(Folder::class);
		$this->folder->method('getPath')->willReturn('/alice/files/Dossier/');
		$this->folder->method('getNonExistingName')->with('mail.eml')->willReturn('mail (2).eml');

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getFirstNodeById')->willReturnCallback(fn (int $id): mixed => match ($id) {
			40 => $this->folder,
			41 => $this->createMock(File::class),
			default => null,
		});

		$this->conversion = $this->createMock(PdfConversionService::class);
		$this->logger     = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The filing under test.
	 *
	 * @return EmailFiling
	 */
	private function filing(): EmailFiling {
		return new EmailFiling($this->dossiers, $this->rootFolder, $this->conversion, $this->logger);

	}//end filing()

	/**
	 * An email named mail.eml.
	 *
	 * @return File&MockObject
	 */
	private function email(): File&MockObject {
		$email = $this->createMock(File::class);
		$email->method('getName')->willReturn('mail.eml');
		$email->method('getId')->willReturn(9);

		return $email;

	}//end email()

	/**
	 * The email moves into the dossier folder under a free name.
	 *
	 * @return void
	 */
	public function testFileMovesTheEmailUnderAFreeName(): void {
		$moved = $this->createMock(File::class);
		$email = $this->email();
		$email->expects($this->once())->method('move')->with('/alice/files/Dossier/mail (2).eml')->willReturn($moved);

		$this->assertSame($moved, $this->filing()->file($email, 'd-1'));

	}//end testFileMovesTheEmailUnderAFreeName()

	/**
	 * A failed move is logged and the email is not filed.
	 *
	 * @return void
	 */
	public function testAFailedMoveIsNotFiled(): void {
		$email = $this->email();
		$email->method('move')->willThrowException(new RuntimeException('quota'));
		$this->logger->expects($this->once())
			->method('warning')
			->with('[EmailFiling] could not move an email into its dossier', ['fileId' => 9, 'dossier' => 'd-1', 'exception' => 'quota']);

		$this->expectException(EmailNotFiled::class);
		$this->expectExceptionMessage('filing-failed');

		$this->filing()->file($email, 'd-1');

	}//end testAFailedMoveIsNotFiled()

	/**
	 * A move that lands on something other than a file is not filed.
	 *
	 * @return void
	 */
	public function testAMoveToANonFileIsNotFiled(): void {
		$email = $this->email();
		$email->method('move')->willReturn($this->createMock(Folder::class));

		$this->expectException(EmailNotFiled::class);
		$this->expectExceptionMessage('filing-failed');

		$this->filing()->file($email, 'd-1');

	}//end testAMoveToANonFileIsNotFiled()

	/**
	 * An unreadable dossier or a folder ref that is not a folder means no dossier folder.
	 *
	 * @return void
	 */
	public function testNoDossierFolder(): void {
		$this->logger->expects($this->once())
			->method('warning')
			->with('[EmailFiling] the dossier of an inbox cannot be read', ['dossier' => 'broken', 'exception' => 'register down']);

		foreach (['broken', 'd-2'] as $dossier) {
			$email = $this->email();
			$email->expects($this->never())->method('move');
			try {
				$this->filing()->file($email, $dossier);
				$this->fail('Filed into ' . $dossier);
			} catch (EmailNotFiled $e) {
				$this->assertSame('dossier-folder-unavailable', $e->getMessage());
			}
		}

	}//end testNoDossierFolder()

	/**
	 * Conversion returns the PDF's id, or an empty string when it fails.
	 *
	 * @return void
	 */
	public function testConvertReturnsThePdfIdOrNothing(): void {
		$pdf = $this->createMock(File::class);
		$pdf->method('getId')->willReturn(77);
		$email = $this->email();
		$this->conversion->method('convertToPdfReporting')->willReturnOnConsecutiveCalls(
			['file' => $pdf],
			$this->throwException(new RuntimeException('no converter'))
		);
		$this->logger->expects($this->once())
			->method('info')
			->with('[EmailFiling] email filed but not converted', ['fileId' => 9, 'exception' => 'no converter']);

		$this->assertSame('77', $this->filing()->convert($email));
		$this->assertSame('', $this->filing()->convert($email));

	}//end testConvertReturnsThePdfIdOrNothing()

	/**
	 * Only a numeric id of an existing file resolves.
	 *
	 * @return void
	 */
	public function testFileByIdResolvesOnlyFiles(): void {
		$file = $this->createMock(File::class);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(fn (int $id): mixed => match ($id) {
			5 => $file,
			6 => $this->createMock(Folder::class),
			default => null,
		});
		$filing = new EmailFiling($this->dossiers, $root, $this->conversion, $this->logger);

		$this->assertSame($file, $filing->fileById('5'));
		$this->assertNull($filing->fileById('6'));
		$this->assertNull($filing->fileById('8'));
		$this->assertNull($filing->fileById('5a'));

	}//end testFileByIdResolvesOnlyFiles()
}//end class
