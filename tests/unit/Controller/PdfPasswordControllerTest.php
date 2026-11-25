<?php

/**
 * Unit tests for PdfPasswordController: each outcome its own answer, the
 * caller's own folder the only way to a file.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#1-service-and-route
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\PdfPasswordController;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\Intake\IntakePasswordRemoval;
use OCA\Filinq\Service\PdfPasswordRemovalService;
use OCA\Filinq\Service\SessionUserFile;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\PdfPasswordController
 * @uses   \OCA\Filinq\Service\SessionUserFile
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 */
class PdfPasswordControllerTest extends TestCase {

	/**
	 * A controller over these collaborators.
	 *
	 * @param File|null                 $file     What the caller's folder holds under the id.
	 * @param mixed                     $password The password in the request.
	 * @param PdfPasswordRemovalService $removal  The service.
	 *
	 * @return PdfPasswordController
	 */
	private function controller(?File $file, mixed $password, PdfPasswordRemovalService $removal, ?IntakePasswordRemoval $intake = null): PdfPasswordController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key, $default = null) => ($key === 'password' ? $password : $default));
		// SessionUserFile is final: a real one over the caller's own folder.
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->with(41)->willReturn($file === null ? [] : [$file]);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('clerk')->willReturn($folder);
		$files = new SessionUserFile($root, $session);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new PdfPasswordController($request, $removal, $files, $l10n, $intake);
	}

	public function testAFileTheCallerCannotOpenIs404AndNothingRuns(): void {
		$removal = $this->createMock(PdfPasswordRemovalService::class);
		$removal->expects($this->never())->method('unlock');

		$this->assertSame(Http::STATUS_NOT_FOUND, $this->controller(null, 'pw', $removal)->remove(41)->getStatus());
	}

	public function testWithoutAPasswordNothingRuns(): void {
		$removal = $this->createMock(PdfPasswordRemovalService::class);
		$removal->expects($this->never())->method('unlock');
		$file = $this->createMock(File::class);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller($file, '', $removal)->remove(41)->getStatus());
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller($file, ['pw'], $removal)->remove(41)->getStatus());
	}

	public function testEachOutcomeHasItsAnswer(): void {
		$file = $this->createMock(File::class);
		$cases = [
			[['outcome' => 'unlocked', 'fileId' => 42, 'name' => 'a (unlocked).pdf', 'validationStatus' => 'ok', 'validationFindings' => []], Http::STATUS_OK, ['fileId' => 42, 'name' => 'a (unlocked).pdf', 'validationStatus' => 'ok', 'validationFindings' => []]],
			[['outcome' => 'wrong-password'], Http::STATUS_UNPROCESSABLE_ENTITY, ['error' => 'The password is not correct']],
			[['outcome' => 'final', 'message' => 'x'], Http::STATUS_CONFLICT, ['error' => 'This document is final and cannot be changed']],
			[['outcome' => 'no-tool'], Http::STATUS_SERVICE_UNAVAILABLE, ['error' => 'qpdf is not installed on this server']],
			[['outcome' => 'failed', 'message' => 'This file is not a PDF.'], Http::STATUS_BAD_REQUEST, ['error' => 'This file is not a PDF.']],
			[['outcome' => 'failed', 'message' => 'The password could not be removed from this PDF.'], Http::STATUS_INTERNAL_SERVER_ERROR, ['error' => 'The password could not be removed from this PDF.']],
		];
		foreach ($cases as [$outcome, $status, $body]) {
			$removal = $this->createMock(PdfPasswordRemovalService::class);
			$removal->expects($this->once())->method('unlock')->with($file, 'Geheim-2026!')->willReturn($outcome);

			$response = $this->controller($file, 'Geheim-2026!', $removal)->remove(41);
			$this->assertSame($status, $response->getStatus());
			$this->assertSame($body, $response->getData());
		}
	}

	/**
	 * An intake document's file is unlocked through the intake service and answers the copy and the document.
	 *
	 * @return void
	 */
	public function testAnIntakeDocumentIsUnlockedThroughTheIntakeService(): void {
		$removal = $this->createMock(PdfPasswordRemovalService::class);
		$removal->expects($this->never())->method('unlock');
		$intake = $this->createMock(IntakePasswordRemoval::class);
		$intake->expects($this->once())->method('unlock')->with('doc-1', 'geheim')
			->willReturn(['outcome' => 'unlocked', 'fileId' => 77, 'name' => 'scan (unlocked).pdf', 'intakeDocument' => ['file' => 77, 'lockedOriginal' => 41]]);

		$response = $this->controller(null, 'geheim', $removal, $intake)->removeForIntake('doc-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['fileId' => 77, 'name' => 'scan (unlocked).pdf', 'intakeDocument' => ['file' => 77, 'lockedOriginal' => 41]], $response->getData());
	}

	/**
	 * Without a password nothing runs; a refusal keeps its status; a wrong password is 422.
	 *
	 * @return void
	 */
	public function testIntakeRefusalsKeepTheirStatus(): void {
		$removal = $this->createMock(PdfPasswordRemovalService::class);
		$intake = $this->createMock(IntakePasswordRemoval::class);
		$intake->expects($this->never())->method('unlock');
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller(null, '', $removal, $intake)->removeForIntake('doc-1')->getStatus());

		$refusing = $this->createMock(IntakePasswordRemoval::class);
		$refusing->method('unlock')->willThrowException(new IntakeRefusedException(message: 'You may not change intake documents.', status: 403));
		$response = $this->controller(null, 'geheim', $removal, $refusing)->removeForIntake('doc-1');
		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'You may not change intake documents.'], $response->getData());

		$wrong = $this->createMock(IntakePasswordRemoval::class);
		$wrong->method('unlock')->willReturn(['outcome' => 'wrong-password']);
		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $this->controller(null, 'fout', $removal, $wrong)->removeForIntake('doc-1')->getStatus());
	}
}
