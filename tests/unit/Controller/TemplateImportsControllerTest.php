<?php

/**
 * The import endpoints start an import as the signed-in user, from an
 * uploaded ZIP or a folder path, and answer its progress.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\TemplateImportsController;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Controller\TemplateImportsController
 * @uses   \OCA\Filinq\Controller\TemplateRequestHandler
 */
class TemplateImportsControllerTest extends TestCase {

	/**
	 * An uploaded ZIP starts an import as the signed-in user; 202.
	 *
	 * @return void
	 */
	public function testAnUploadStartsAnImport(): void {
		$zip = (string) tempnam(sys_get_temp_dir(), 'filinq-zip-');
		file_put_contents($zip, 'zip-bytes');
		$request = $this->createMock(IRequest::class);
		$request->method('getUploadedFile')->willReturn(['name' => 'estate.zip', 'tmp_name' => $zip, 'error' => UPLOAD_ERR_OK]);
		$request->method('getParams')->willReturn(['namespace' => 'filinq', '_route' => 'x']);
		$imports = $this->createMock(TemplateImportService::class);
		$imports->expects($this->once())->method('start')->with(['namespace' => 'filinq'], 'zip-bytes', 'anne')->willReturn(['id' => 'job-1', 'status' => 'queued']);

		$response = $this->controller(request: $request, imports: $imports, uid: 'anne')->create();
		unlink($zip);

		$this->assertSame([202, ['id' => 'job-1', 'status' => 'queued']], [$response->getStatus(), $response->getData()]);
	}//end testAnUploadStartsAnImport()

	/**
	 * Without an upload the folder path in the body is used; a refusal
	 * answers its code.
	 *
	 * @return void
	 */
	public function testWithoutAnUploadTheFolderIsUsed(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getUploadedFile')->willReturn(null);
		$request->method('getParams')->willReturn(['namespace' => 'filinq', 'path' => 'Sjablonen']);
		$imports = $this->createMock(TemplateImportService::class);
		$imports->expects($this->once())->method('start')->with(['namespace' => 'filinq', 'path' => 'Sjablonen'], null, 'anne')
			->willThrowException(new Exception('The folder "Sjablonen" was not found in your files.', 400));

		$response = $this->controller(request: $request, imports: $imports, uid: 'anne')->create();

		$this->assertSame([400, ['error' => 'The folder "Sjablonen" was not found in your files.']], [$response->getStatus(), $response->getData()]);
	}//end testWithoutAnUploadTheFolderIsUsed()

	/**
	 * A job's progress is answered; an unknown one is a 404; nobody signed in is a 401.
	 *
	 * @return void
	 */
	public function testAJobIsAnswered(): void {
		$imports = $this->createMock(TemplateImportService::class);
		$imports->method('get')->willReturnCallback(static function (string $jobId): array {
			if ($jobId !== 'job-1') {
				throw new Exception('Not found', 404);
			}

			return ['id' => 'job-1', 'status' => 'running', 'imported' => 12];
		});
		$controller = $this->controller(request: $this->createMock(IRequest::class), imports: $imports, uid: 'anne');

		$this->assertSame(['id' => 'job-1', 'status' => 'running', 'imported' => 12], $controller->show(jobId: 'job-1')->getData());
		$this->assertSame(404, $controller->show(jobId: 'job-9')->getStatus());
		$this->assertSame(401, $this->controller(request: $this->createMock(IRequest::class), imports: $imports, uid: null)->show(jobId: 'job-1')->getStatus());
	}//end testAJobIsAnswered()

	/**
	 * The controller with a signed-in user, or nobody.
	 *
	 * @param IRequest              $request The request.
	 * @param TemplateImportService $imports The imports.
	 * @param string|null           $uid     The user's id, or null.
	 *
	 * @return TemplateImportsController
	 */
	private function controller(IRequest $request, TemplateImportService $imports, ?string $uid): TemplateImportsController {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		return new TemplateImportsController('filinq', $request, $imports, new TemplateRequestHandler($this->createMock(LoggerInterface::class)), $session);
	}//end controller()
}//end class
