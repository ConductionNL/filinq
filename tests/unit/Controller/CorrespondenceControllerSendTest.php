<?php

/**
 * Unit tests for CorrespondenceController::send() and ::sendPreview()
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
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CorrespondenceController;
use OCA\Filinq\Exception\CorrespondenceSendRefused;
use OCA\Filinq\Service\CorrespondenceSendService;
use OCA\Filinq\Service\CorrespondenceService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * The send route answers what the send did: 201 sent, 502 not sent, and the
 * refusals with their own status and a message without server internals.
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
 */
class CorrespondenceControllerSendTest extends TestCase {

	/**
	 * The request body every test sends.
	 *
	 * @var array<string, mixed>
	 */
	private const BODY = [
		'templateId' => 'tmpl-1',
		'dataRefs' => [['register' => 'brp', 'schema' => 'persoon', 'id' => 'p-1']],
		'options' => ['attachPdf' => 'true', 'caseReference' => 'Z/2026/001'],
	];

	/**
	 * The send service.
	 *
	 * @var CorrespondenceSendService&MockObject
	 */
	private CorrespondenceSendService $sender;

	/**
	 * The session, signed in unless a test says otherwise.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession $session;

	/**
	 * The controller under test.
	 *
	 * @var CorrespondenceController
	 */
	private CorrespondenceController $controller;

	/**
	 * Build the controller over a request carrying BODY.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => (self::BODY[$key] ?? $default)
		);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => 'nl:' . $text);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('officer');
		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn($user);
		$this->sender = $this->createMock(CorrespondenceSendService::class);

		$this->controller = new CorrespondenceController(
			appName: 'filinq',
			request: $request,
			corrSvc: $this->createMock(CorrespondenceService::class),
			userSession: $this->session,
			logger: $this->createMock(LoggerInterface::class),
			l10n: $l10n,
			sender: $this->sender,
		);

	}//end setUp()

	/**
	 * A sent letter is a 201 with the result; the service gets the session user and attachPdf as a boolean.
	 *
	 * @return void
	 */
	public function testASentLetterAnswers201(): void {
		$this->sender->expects($this->once())->method('send')
			->with(
				'tmpl-1',
				self::BODY['dataRefs'],
				['attachPdf' => true, 'caseReference' => 'Z/2026/001', 'userId' => 'officer']
			)
			->willReturn(['status' => 'sent', 'to' => 'j.jansen@example.nl']);

		$response = $this->controller->send();

		$this->assertSame(201, $response->getStatus());
		$this->assertSame('j.jansen@example.nl', $response->getData()['to']);

	}//end testASentLetterAnswers201()

	/**
	 * A letter the mail server did not take is a 502 that says it was not sent.
	 *
	 * @return void
	 */
	public function testAFailedSendAnswers502(): void {
		$this->sender->method('send')->willReturn(['status' => 'failed', 'to' => 'j.jansen@example.nl']);

		$response = $this->controller->send();

		$this->assertSame(502, $response->getStatus());
		$this->assertSame('failed', $response->getData()['status']);
		$this->assertSame('nl:The letter was not sent: the mail server did not accept it.', $response->getData()['error']);

	}//end testAFailedSendAnswers502()

	/**
	 * A recipient without an address is a 422 with the translated message, and nothing else.
	 *
	 * @return void
	 */
	public function testNoAddressAnswers422(): void {
		$this->sender->method('send')->willThrowException(
			new CorrespondenceSendRefused(message: CorrespondenceSendService::NO_ADDRESS, code: 422)
		);

		$response = $this->controller->send();

		$this->assertSame(422, $response->getStatus());
		$this->assertSame(['error' => 'nl:' . CorrespondenceSendService::NO_ADDRESS], $response->getData());

	}//end testNoAddressAnswers422()

	/**
	 * A recipient the caller cannot read is a 404.
	 *
	 * @return void
	 */
	public function testAnUnreadableRecipientAnswers404(): void {
		$this->sender->method('send')->willThrowException(
			new CorrespondenceSendRefused(message: CorrespondenceSendService::NOT_FOUND, code: 404)
		);

		$this->assertSame(404, $this->controller->send()->getStatus());

	}//end testAnUnreadableRecipientAnswers404()

	/**
	 * Without a session nothing is sent.
	 *
	 * @return void
	 */
	public function testWithoutASessionNothingIsSent(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$this->sender->expects($this->never())->method('send');
		$controller = new CorrespondenceController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			corrSvc: $this->createMock(CorrespondenceService::class),
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class),
			l10n: $this->createMock(IL10N::class),
			sender: $this->sender,
		);

		$this->assertSame(401, $controller->send()->getStatus());

	}//end testWithoutASessionNothingIsSent()

	/**
	 * The preview answers the address and the subject, and sends nothing.
	 *
	 * @return void
	 */
	public function testThePreviewAnswersAddressAndSubject(): void {
		$this->sender->expects($this->never())->method('send');
		$this->sender->expects($this->once())->method('preview')->with('tmpl-1', self::BODY['dataRefs'])
			->willReturn(['to' => 'j.jansen@example.nl', 'subject' => 'Uitnodiging gesprek']);

		$response = $this->controller->sendPreview();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['to' => 'j.jansen@example.nl', 'subject' => 'Uitnodiging gesprek'], $response->getData());

	}//end testThePreviewAnswersAddressAndSubject()

	/**
	 * Both routes are open to every signed-in user; the recipient read is the access check.
	 *
	 * @return void
	 */
	public function testBothRoutesDeclareNoAdminRequired(): void {
		foreach (['send', 'sendPreview'] as $method) {
			$attributes = (new ReflectionMethod(CorrespondenceController::class, $method))->getAttributes(NoAdminRequired::class);
			$this->assertCount(1, $attributes, $method . ' must declare #[NoAdminRequired]');
		}

	}//end testBothRoutesDeclareNoAdminRequired()
}//end class
