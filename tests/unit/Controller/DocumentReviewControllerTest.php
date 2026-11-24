<?php

/**
 * Unit tests for DocumentReviewController.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DocumentReviewController;
use OCA\Filinq\Exception\DocumentReviewRefusedException;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Service\Review\DocumentReviewService;
use OCA\Filinq\Tests\Unit\Support\BuildsInMemoryReviewGate;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Authentication, file access and the answers of the three review endpoints.
 *
 * @covers \OCA\Filinq\Controller\DocumentReviewController
 * @uses   \OCA\Filinq\Exception\DocumentReviewRefusedException
 * @uses   \OCA\Filinq\Service\Review\DocumentReviewService
 * @uses   \OCA\Filinq\Service\Review\CertaintyThresholds
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentReviewControllerTest extends TestCase {
	use BuildsInMemoryReviewGate;

	/**
	 * @var DocumentReviewService&MockObject
	 */
	private DocumentReviewService&MockObject $reviews;

	/**
	 * @var AnonymizeRequestServiceInterface&MockObject
	 */
	private AnonymizeRequestServiceInterface&MockObject $access;

	/**
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * The controller under test.
	 *
	 * @var DocumentReviewController
	 */
	private DocumentReviewController $controller;

	/**
	 * Build the controller with a signed-in user who may open file 5.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->reviews = $this->createMock(DocumentReviewService::class);
		$this->access = $this->createMock(AnonymizeRequestServiceInterface::class);
		$this->session = $this->createMock(IUserSession::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('w.devries');
		$this->session->method('getUser')->willReturn($user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$this->controller = new DocumentReviewController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			reviews: $this->reviews,
			userSession: $this->session,
			access: $this->access,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end setUp()

	/**
	 * Show answers the status with the gate mode.
	 *
	 * @return void
	 */
	public function testShow(): void {
		$this->access->expects($this->once())->method('verifyFileAccess')->with(5)->willReturn(null);
		$this->reviews->method('status')->with(5)->willReturn(['fileId' => 5, 'checked' => true]);
		$this->reviews->method('gateMode')->willReturn('advisory');

		$response = $this->controller->show(fileId: 5);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['fileId' => 5, 'checked' => true, 'gateMode' => 'advisory'], $response->getData());
	}//end testShow()

	/**
	 * Check signs with the session's user, trims the note and answers 201.
	 *
	 * @return void
	 */
	public function testCheckSignsWithTheSession(): void {
		$this->access->method('verifyFileAccess')->willReturn(null);
		$this->reviews->expects($this->once())->method('check')
			->with(5, 'w.devries', 'p. 4')
			->willReturn(['fileId' => 5, 'checked' => true]);

		$response = $this->controller->check(fileId: 5, note: '  p. 4 ');

		$this->assertSame(201, $response->getStatus());
		$this->assertSame(['fileId' => 5, 'checked' => true], $response->getData());
	}//end testCheckSignsWithTheSession()

	/**
	 * An unextracted document is answered 409 with the reason.
	 *
	 * @return void
	 */
	public function testCheckRefusalIsAConflict(): void {
		$this->access->method('verifyFileAccess')->willReturn(null);
		$this->reviews->method('check')->willThrowException(new DocumentReviewRefusedException(reason: 'not_extracted', message: 'x'));

		$response = $this->controller->check(fileId: 5);

		$this->assertSame(409, $response->getStatus());
		$this->assertSame('not_extracted', $response->getData()['reason']);
	}//end testCheckRefusalIsAConflict()

	/**
	 * Uncheck answers the new status with whether a mark was removed.
	 *
	 * @return void
	 */
	public function testUncheck(): void {
		$this->access->method('verifyFileAccess')->willReturn(null);
		$this->reviews->expects($this->once())->method('uncheck')->with(5)->willReturn(true);
		$this->reviews->method('status')->willReturn(['checked' => false]);

		$response = $this->controller->uncheck(fileId: 5);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['checked' => false, 'removed' => true], $response->getData());
	}//end testUncheck()

	/**
	 * An uncheck the schema refuses (code 403) is answered 403 with the reason, not as a failure.
	 *
	 * @return void
	 */
	public function testARefusedUncheckIsForbidden(): void {
		$this->access->method('verifyFileAccess')->willReturn(null);
		$this->reviews->method('uncheck')->willThrowException(new RuntimeException('refused', 403));
		$this->reviews->expects($this->never())->method('status');

		$response = $this->controller->uncheck(fileId: 5);

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'You may not remove the reviewed mark of this document.'], $response->getData());
	}//end testARefusedUncheckIsForbidden()

	/**
	 * A file outside the caller's tree is refused before the service is touched, on every endpoint.
	 *
	 * @return void
	 */
	public function testNoAccessIsRefusedFirst(): void {
		$this->access->method('verifyFileAccess')->willReturn(['status' => 404, 'body' => ['error' => 'File not found']]);
		$this->reviews->expects($this->never())->method($this->anything());

		foreach (['show', 'check', 'uncheck'] as $method) {
			$response = $this->controller->{$method}(fileId: 5);
			$this->assertSame(404, $response->getStatus(), $method);
			$this->assertSame(['error' => 'File not found'], $response->getData(), $method);
		}
	}//end testNoAccessIsRefusedFirst()

	/**
	 * Without a session every endpoint answers 401 and checks nothing.
	 *
	 * @return void
	 */
	public function testUnauthenticatedIsRefused(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$this->access->expects($this->never())->method('verifyFileAccess');
		$this->reviews->expects($this->never())->method($this->anything());
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$controller = new DocumentReviewController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			reviews: $this->reviews,
			userSession: $session,
			access: $this->access,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class)
		);

		foreach (['show', 'check', 'uncheck'] as $method) {
			$this->assertSame(401, $controller->{$method}(fileId: 5)->getStatus(), $method);
		}
	}//end testUnauthenticatedIsRefused()

	/**
	 * A store failure is answered 500 on every endpoint.
	 *
	 * @return void
	 */
	public function testFailuresAreServerErrors(): void {
		$this->access->method('verifyFileAccess')->willReturn(null);
		$this->reviews->method('status')->willThrowException(new RuntimeException('down'));
		$this->reviews->method('check')->willThrowException(new RuntimeException('down'));
		$this->reviews->method('uncheck')->willThrowException(new RuntimeException('down'));

		foreach (['show', 'check', 'uncheck'] as $method) {
			$this->assertSame(500, $this->controller->{$method}(fileId: 5)->getStatus(), $method);
		}
	}//end testFailuresAreServerErrors()

	/**
	 * The controller over the real review gate, for a user who may open every file.
	 *
	 * @return DocumentReviewController The controller.
	 */
	private function overTheRealGate(): DocumentReviewController {
		$access = $this->createMock(AnonymizeRequestServiceInterface::class);
		$access->method('verifyFileAccess')->willReturn(null);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new DocumentReviewController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			reviews: $this->inMemoryReviewGate(),
			userSession: $this->session,
			access: $access,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end overTheRealGate()

	/**
	 * A check made on one rendition does not hold once another rendition is current.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 */
	public function testACheckOnAnotherRenditionDoesNotHold(): void {
		$this->gateRelations[5] = [['id' => 1, 'entityId' => 10, 'skipAnonymization' => false, 'confidence' => 0.99, 'detectionMethod' => 'presidio']];
		$this->gateRenditions[5] = ['sourceFileId' => 5, 'fileId' => 6, 'sha256' => 'a1', 'kind' => 'converted', 'backend' => 'libreoffice', 'textLayer' => 'native'];
		$controller = $this->overTheRealGate();

		$checked = $controller->check(fileId: 5);
		$this->assertSame(201, $checked->getStatus());
		$this->assertTrue($checked->getData()['checked']);
		$this->assertSame('a1', $this->gateMarks[5]['renditionSha256']);

		$this->gateRenditions[5]['sha256'] = 'b2';
		$status = $controller->show(fileId: 5)->getData();

		$this->assertFalse($status['checked']);
		$this->assertTrue($status['stale']);
		$this->assertSame('b2', $status['rendition']['sha256']);
	}//end testACheckOnAnotherRenditionDoesNotHold()

	/**
	 * Without a text layer the check is refused until the reviewer confirms reading by eye.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 */
	public function testNoTextLayerNeedsTheByEyeConfirmation(): void {
		$this->gateRenditions[5] = ['sourceFileId' => 5, 'fileId' => 5, 'sha256' => 'c3', 'kind' => 'ocr-text-layer', 'backend' => '', 'textLayer' => 'none'];
		$controller = $this->overTheRealGate();

		$refused = $controller->check(fileId: 5);
		$this->assertSame(409, $refused->getStatus());
		$this->assertSame('no_text_layer', $refused->getData()['reason']);
		$this->assertSame(
			'Detection could not read the pages of this document. Confirm that you read every page by eye before marking it reviewed.',
			$refused->getData()['error']
		);
		$this->assertSame([], $this->gateMarks);

		$checked = $controller->check(fileId: 5, note: '', byEye: true);
		$this->assertSame(201, $checked->getStatus());
		$this->assertTrue($this->gateMarks[5]['readByEye']);
		$this->assertTrue($checked->getData()['checked']);
	}//end testNoTextLayerNeedsTheByEyeConfirmation()

	/**
	 * An undecided uncertain finding blocks the check and is named; once decided, the check holds.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function testAnUndecidedUncertainFindingBlocksTheCheck(): void {
		$this->gateFloor = 0.6;
		$this->gateCertainFrom = '0.9';
		$this->gateRelations[5] = [
			['id' => 1, 'entityId' => 10, 'skipAnonymization' => false, 'confidence' => 0.95, 'detectionMethod' => 'presidio'],
			['id' => 2, 'entityId' => 11, 'skipAnonymization' => false, 'confidence' => 0.65, 'detectionMethod' => 'presidio'],
		];
		$controller = $this->overTheRealGate();

		$status = $controller->show(fileId: 5)->getData();
		$this->assertSame(0.6, $status['thresholds']['floor']);
		$this->assertSame(0.9, $status['thresholds']['certainFrom']);
		$this->assertSame(1, $status['uncertainCount']);

		$refused = $controller->check(fileId: 5);
		$this->assertSame(409, $refused->getStatus());
		$this->assertSame('uncertain_undecided', $refused->getData()['reason']);
		$this->assertSame([['relationId' => 2, 'entityId' => 11, 'confidence' => 0.65]], $refused->getData()['undecidedFindings']);
		$this->assertSame([], $this->gateMarks);

		// The reviewer included it in the workbench; a non-numeric id is ignored.
		$this->assertSame(201, $controller->check(fileId: 5, note: '', byEye: false, decided: ['2', 'x'])->getStatus());
		$this->assertTrue($controller->show(fileId: 5)->getData()['checked']);
	}//end testAnUndecidedUncertainFindingBlocksTheCheck()
}//end class
