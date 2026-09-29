<?php

/**
 * processFile() sends an image straight to Tesseract. It used to rasterise
 * every candidate as a PDF first and only then read an image.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-ocr-on-arrival/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use OCA\Filinq\Service\OcrService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * OcrService::processFile().
 */
class OcrServiceProcessFileTest extends TestCase {

	use OcrDoubles;

	/**
	 * An image never goes through the PDF path.
	 *
	 * @return void
	 */
	public function testAnImageIsNotRasterisedAsAPdf(): void {
		$ocr = $this->serviceFor(mimeType: 'image/jpeg');
		$ocr->expects($this->never())->method('extractTextFromPdf');
		$ocr->expects($this->once())->method('extractTextFromImage')->willReturn(['text' => 'foto', 'confidence' => 80.0]);

		$this->assertTrue($ocr->processFile(fileId: 812004)['ocrProcessed']);

	}//end testAnImageIsNotRasterisedAsAPdf()

	/**
	 * A PDF goes through the PDF path only.
	 *
	 * @return void
	 */
	public function testAPdfGoesThroughThePdfPath(): void {
		$ocr = $this->serviceFor(mimeType: 'application/pdf');
		$ocr->expects($this->never())->method('extractTextFromImage');
		$ocr->expects($this->once())->method('extractTextFromPdf')->willReturn(['text' => 'brief', 'confidence' => 80.0]);

		$this->assertSame('brief', $ocr->processFile(fileId: 812004)['text']);

	}//end testAPdfGoesThroughThePdfPath()

	/**
	 * The service over a session user whose folder holds file 812004.
	 *
	 * @param string $mimeType The file's MIME type.
	 *
	 * @return OcrService The service with its engine calls stubbed.
	 */
	private function serviceFor(string $mimeType): OcrService {
		$file = $this->ocrFile(mimeType: $mimeType);
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('noor');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnArgument(2);

		$service = $this->getMockBuilder(OcrService::class)
			->setConstructorArgs([new NullLogger(), $config, $root, $session])
			->onlyMethods(['isTesseractAvailable', 'extractTextFromImage', 'extractTextFromPdf'])
			->getMock();
		$service->method('isTesseractAvailable')->willReturn(true);

		return $service;

	}//end serviceFor()
}//end class
