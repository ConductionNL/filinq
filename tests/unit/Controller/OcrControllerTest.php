<?php

/**
 * The OCR routes: the error contract, the caller's own files only, and no
 * text in any answer.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\OcrController;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Tests\Unit\Service\Ocr\OcrDoubles;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * OcrController.
 */
class OcrControllerTest extends TestCase {

	use OcrDoubles;

	/**
	 * Manual OCR of a scanned PDF succeeds, and the answer holds no text.
	 *
	 * @return void
	 */
	public function testManualOcrOfAScannedPdfSucceeds(): void {
		$response = $this->controller(ocr: $this->ocrService())->run(fileId: 812004);
		$body = $response->getData();

		$this->assertSame(200, $response->getStatus());
		$this->assertTrue($body['ocrProcessed']);
		$this->assertSame(91.4, $body['confidence']);
		$this->assertSame(25, $body['textLength']);
		$this->assertSame('nld+eng', $body['languages']);
		$this->assertSame(300, $body['dpi']);
		$this->assertArrayNotHasKey('text', $body);
		$this->assertStringNotContainsString('Jansen', (string) json_encode($body));

	}//end testManualOcrOfAScannedPdfSucceeds()

	/**
	 * The admin toggle gives 409, a missing Tesseract 503, a Word file 400.
	 *
	 * @return void
	 */
	public function testTheErrorContract(): void {
		$cases = [
			409 => [$this->ocrService(config: ['ocr_enabled' => '0']), 'application/pdf', 'ocr_disabled'],
			503 => [$this->ocrService(tesseract: false), 'application/pdf', 'tesseract_unavailable'],
			400 => [$this->ocrService(), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'not_candidate'],
		];

		foreach ($cases as $status => [$ocr, $mimeType, $reason]) {
			$response = $this->controller(ocr: $ocr, mimeType: $mimeType)->run(fileId: 812004);

			$this->assertSame($status, $response->getStatus(), $reason);
			$this->assertSame($reason, $response->getData()['reason']);
			$this->assertFalse($response->getData()['ocrProcessed']);
		}

		$this->assertSame([], $this->ocrWritten);

	}//end testTheErrorContract()

	/**
	 * A file outside the caller's folder is 404, and OCR never runs on it.
	 *
	 * @return void
	 */
	public function testAnotherUsersFileIsNotFound(): void {
		$ocr = $this->ocrService();
		$ocr->expects($this->never())->method('extractTextFromPdf');

		$response = $this->controller(ocr: $ocr, found: false)->run(fileId: 999);

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(404, $this->controller(ocr: $ocr, found: false)->show(fileId: 999)->getStatus());

	}//end testAnotherUsersFileIsNotFound()

	/**
	 * The status reads the stored row after a run, and says a candidate can be read.
	 *
	 * @return void
	 */
	public function testTheStatusReflectsARealRun(): void {
		$controller = $this->controller(ocr: $this->ocrService());

		$before = $controller->show(fileId: 812004)->getData();
		$this->assertFalse($before['ocrProcessed']);
		$this->assertNull($before['ocrConfidence']);
		$this->assertTrue($before['ocrAvailable']);

		$controller->run(fileId: 812004);
		$after = $controller->show(fileId: 812004)->getData();

		$this->assertTrue($after['ocrProcessed']);
		$this->assertSame(91.4, $after['ocrConfidence']);
		$this->assertSame('manual', $after['result']['triggeredBy']);

	}//end testTheStatusReflectsARealRun()

	/**
	 * The index answers the capability and the caller's results only.
	 *
	 * @return void
	 */
	public function testTheIndexAnswersCapabilityAndResults(): void {
		$this->controller(ocr: $this->ocrService())->run(fileId: 812004);

		$data = $this->controller(ocr: $this->ocrService(), fileIds: '812004,5')->index()->getData();

		$this->assertSame(['enabled' => true, 'tesseractAvailable' => true, 'available' => true], $data['capability']);
		$results = (array) $data['results'];
		$this->assertSame([812004], array_keys($results));
		$this->assertSame(91.4, $results['812004']['confidence']);

		$hidden = (array) $this->controller(ocr: $this->ocrService(), fileIds: '812004', found: false)->index()->getData()['results'];
		$this->assertSame([], $hidden);

	}//end testTheIndexAnswersCapabilityAndResults()

	/**
	 * Build the controller.
	 *
	 * @param OcrService $ocr The engine.
	 * @param string $mimeType The file's MIME type.
	 * @param bool $found Whether the file is in the caller's folder.
	 * @param string $fileIds The fileIds query parameter.
	 *
	 * @return OcrController The controller.
	 */
	private function controller(OcrService $ocr, string $mimeType = 'application/pdf', bool $found = true, string $fileIds = ''): OcrController {
		$file = $this->ocrFile(mimeType: $mimeType);
		$folder = $this->createMock(Folder::class);
		$folder->method('getFirstNodeById')->willReturnCallback(
			static fn (int $id): ?File => ($found === true && $id === 812004) ? $file : null
		);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('noor');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => $key === 'fileIds' ? $fileIds : $default
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$repository = $this->ocrResultRepository();

		return new OcrController(
			'filinq',
			$request,
			$this->ocrRunServiceOver(ocr: $ocr, repository: $repository),
			$repository,
			$root,
			$session,
			$l10n,
			new NullLogger()
		);

	}//end controller()
}//end class
