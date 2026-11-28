<?php

/**
 * Unit tests for ReceiptCaptureService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Extraction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use DateTime;
use OCA\Filinq\Exception\ReceiptCaptureRefusedException;
use OCA\Filinq\Exception\UploadRefusedException;
use OCA\Filinq\Kernel\Contracts\FinancialExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadPolicyServiceInterface;
use OCA\Filinq\Service\Extraction\PhotoNormaliser;
use OCA\Filinq\Service\Extraction\ReceiptCaptureService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * REQ-ERP-001 and REQ-ERP-002: a camera photo is checked, turned upright,
 * kept in the user's own Files under Receipts/<yyyy>/<mm>/, then read.
 *
 * @covers \OCA\Filinq\Service\Extraction\ReceiptCaptureService
 */
class ReceiptCaptureServiceTest extends TestCase {

	/**
	 * The detected type the policy answers.
	 *
	 * @var string
	 */
	private string $detected = 'image/jpeg';

	/**
	 * What was written, as `<path>` => bytes.
	 *
	 * @var array<string, string>
	 */
	private array $written = [];

	/**
	 * The names that already exist, as paths.
	 *
	 * @var array<int, string>
	 */
	private array $existing = [];

	/**
	 * @var FinancialExtractionServiceInterface&MockObject
	 */
	private FinancialExtractionServiceInterface $extraction;

	/**
	 * @var PhotoNormaliser&MockObject
	 */
	private PhotoNormaliser $photos;

	/**
	 * @var UploadPolicyServiceInterface&MockObject
	 */
	private UploadPolicyServiceInterface $policy;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->extraction = $this->createMock(FinancialExtractionServiceInterface::class);
		$this->photos = $this->createMock(PhotoNormaliser::class);
		$this->policy = $this->createMock(UploadPolicyServiceInterface::class);
		$this->policy->method('check')->willReturnCallback(fn (string $fileName, string $contents): array => ['detectedType' => $this->detected]);
	}//end setUp()

	/**
	 * An in-memory folder at a path of the user's Files.
	 *
	 * @param string $path The path.
	 *
	 * @return Folder The folder.
	 */
	private function folder(string $path): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('nodeExists')->willReturnCallback(
			fn (string $name): bool => in_array(ltrim($path . '/' . $name, '/'), $this->existing, true)
		);
		$folder->method('get')->willReturnCallback(fn (string $name): Folder => $this->folder(path: ltrim($path . '/' . $name, '/')));
		$folder->method('newFolder')->willReturnCallback(fn (string $name): Folder => $this->folder(path: ltrim($path . '/' . $name, '/')));
		$folder->method('newFile')->willReturnCallback(
			function (string $name, $content) use ($path): File {
				$this->written[ltrim($path . '/' . $name, '/')] = (string)$content;
				$file = $this->createMock(File::class);
				$file->method('getId')->willReturn(4711);
				return $file;
			}
		);

		return $folder;
	}//end folder()

	/**
	 * The service, for alice, at 14 November 2026 09:30:00.
	 *
	 * @return ReceiptCaptureService The service.
	 */
	private function service(): ReceiptCaptureService {
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturnCallback(fn (): Folder => $this->folder(path: ''));
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new DateTime('2026-11-14 09:30:00'));

		return new ReceiptCaptureService(uploadPolicy: $this->policy, photos: $this->photos, rootFolder: $root, extraction: $this->extraction, time: $time);
	}//end service()

	/**
	 * A photo is stored upright under Receipts/2026/11 and read as a receipt with its file id.
	 *
	 * @return void
	 */
	public function testAPhotoIsStoredUprightAndRead(): void {
		$this->photos->expects($this->once())->method('upright')->with('sideways')->willReturn('upright');
		$this->extraction->expects($this->once())->method('extractFinancial')->with(
			['fileId' => 4711, 'capturedFileId' => 4711, 'docType' => 'supplier-invoice', 'sourceApp' => 'shillinq', 'callbackEvent' => true],
			'alice'
		)->willReturn(['id' => 'ext-1', 'capturedFileId' => 4711]);

		$result = $this->service()->capture(userId: 'alice', fileName: 'IMG_0042.JPG', contents: 'sideways', request: ['docType' => 'supplier-invoice', 'sourceApp' => 'shillinq', 'callbackEvent' => 'true']);

		$this->assertSame(['id' => 'ext-1', 'capturedFileId' => 4711], $result);
		$this->assertSame(['Receipts/2026/11/2026-11-14-093000.jpg' => 'upright'], $this->written);
	}//end testAPhotoIsStoredUprightAndRead()

	/**
	 * A HEIC photo is stored as JPEG; a PDF as it came; a taken name gets a suffix.
	 *
	 * @return void
	 */
	public function testHeicIsStoredAsJpegAndAPdfAsItCame(): void {
		$this->photos->expects($this->once())->method('heicToJpeg')->with('heic-bytes')->willReturn('jpeg-bytes');
		$this->photos->expects($this->never())->method('upright');
		$this->extraction->method('extractFinancial')->willReturn([]);
		$this->existing = ['Receipts', 'Receipts/2026', 'Receipts/2026/11', 'Receipts/2026/11/2026-11-14-093000.pdf'];

		$this->detected = 'image/heic';
		$this->service()->capture(userId: 'alice', fileName: 'IMG_0042.HEIC', contents: 'heic-bytes', request: []);
		$this->detected = 'application/pdf';
		$this->service()->capture(userId: 'alice', fileName: 'scan.pdf', contents: '%PDF-1.7', request: []);

		$this->assertSame(
			['Receipts/2026/11/2026-11-14-093000.jpg' => 'jpeg-bytes', 'Receipts/2026/11/2026-11-14-093000-2.pdf' => '%PDF-1.7'],
			$this->written
		);
	}//end testHeicIsStoredAsJpegAndAPdfAsItCame()

	/**
	 * Without a docType the capture reads a receipt and asks for no event.
	 *
	 * @return void
	 */
	public function testTheDefaultsAreAReceiptWithoutEvent(): void {
		$this->detected = 'image/png';
		$this->extraction->expects($this->once())->method('extractFinancial')->with(
			['fileId' => 4711, 'capturedFileId' => 4711, 'docType' => 'receipt', 'sourceApp' => '', 'callbackEvent' => false],
			'alice'
		)->willReturn([]);

		$this->service()->capture(userId: 'alice', fileName: 'bon.png', contents: 'png', request: []);
	}//end testTheDefaultsAreAReceiptWithoutEvent()

	/**
	 * A ZIP named .jpg, and a photo above 20 MB, are refused before anything is stored.
	 *
	 * @return void
	 */
	public function testAZipNamedJpgAndAnOversizePhotoAreRefused(): void {
		$this->extraction->expects($this->never())->method('extractFinancial');

		$this->detected = 'application/zip';
		try {
			$this->service()->capture(userId: 'alice', fileName: 'bon.jpg', contents: "PK\x03\x04", request: []);
			$this->fail('A ZIP must be refused.');
		} catch (ReceiptCaptureRefusedException $e) {
			$this->assertSame([415, 'Only a JPEG, PNG or HEIC photo or a PDF can be captured.'], [$e->getCode(), $e->getMessage()]);
		}

		try {
			$this->service()->capture(userId: 'alice', fileName: 'big.jpg', contents: str_repeat('x', ReceiptCaptureService::MAX_BYTES + 1), request: []);
			$this->fail('A photo above 20 MB must be refused.');
		} catch (ReceiptCaptureRefusedException $e) {
			$this->assertSame(413, $e->getCode());
		}

		$this->assertSame([], $this->written);
	}//end testAZipNamedJpgAndAnOversizePhotoAreRefused()

	/**
	 * A HEIC photo the server cannot read, and a file the upload policy refuses, store nothing.
	 *
	 * @return void
	 */
	public function testARefusalFromTheNormaliserOrThePolicyStoresNothing(): void {
		$this->extraction->expects($this->never())->method('extractFinancial');
		$this->detected = 'image/heif';
		$this->photos->method('heicToJpeg')->willThrowException(new ReceiptCaptureRefusedException(PhotoNormaliser::MESSAGE_HEIC, 415));

		try {
			$this->service()->capture(userId: 'alice', fileName: 'IMG.HEIF', contents: 'heif', request: []);
			$this->fail('An unreadable HEIC must be refused.');
		} catch (ReceiptCaptureRefusedException $e) {
			$this->assertSame(PhotoNormaliser::MESSAGE_HEIC, $e->getMessage());
		}

		$policy = $this->createMock(UploadPolicyServiceInterface::class);
		$refusal = 'This file is a application/zip, which the policy does not allow.';
		$policy->method('check')->willThrowException(new UploadRefusedException(message: $refusal));
		$this->policy = $policy;
		try {
			$this->service()->capture(userId: 'alice', fileName: 'bon.jpg', contents: "PK\x03\x04", request: []);
			$this->fail(message: 'A file the policy refuses must be refused.');
		} catch (ReceiptCaptureRefusedException $e) {
			// The route answers 415 with the policy's reason, as REQ-ERP-002 asks; found live, where the policy refused first.
			$this->assertSame(
				expected: [415, $refusal],
				actual: [$e->getCode(), $e->getMessage()]
			);
		}

		$this->assertSame(expected: [], actual: $this->written);
	}//end testARefusalFromTheNormaliserOrThePolicyStoresNothing()
}//end class
