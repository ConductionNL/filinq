<?php

/**
 * Unit tests for DossierManagementController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DossierManagementController;
use OCA\Filinq\Service\DossierManagementService;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for DossierManagementController.
 *
 * The HTTP boundary is what makes a refusal actionable. A lifecycle rejection
 * collapsed into a 500 tells the operator nothing; these tests pin that the
 * service's status code and message reach the client intact, and that an
 * unauthenticated caller is refused before any handler runs.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * phpcs:disable CustomSniffs.Functions.NamedParameters
 */
final class DossierManagementControllerTest extends TestCase {

	/**
	 * The aggregation service.
	 *
	 * @var DossierManagementService&MockObject
	 */
	private DossierManagementService&MockObject $service;

	/**
	 * The current session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * The controller under test.
	 *
	 * @var DossierManagementController
	 */
	private DossierManagementController $controller;

	/**
	 * Build the controller over mocks, signed in as alice.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->createMock(DossierManagementService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn($this->createMock(IUser::class));

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$this->controller = new DossierManagementController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->service,
			$this->userSession,
			$l10n,
			$this->createMock(LoggerInterface::class),
		);

	}//end setUp()

	/**
	 * The index returns the service's rows under `results`.
	 *
	 * @return void
	 */
	public function testIndexReturnsRows(): void {
		$this->service->method('index')->willReturn([['id' => 'd1', 'name' => 'Woo 2026-001']]);

		$response = $this->controller->index();

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame('d1', $response->getData()['results'][0]['id']);

	}//end testIndexReturnsRows()

	/**
	 * Creating answers 201, not 200.
	 *
	 * @return void
	 */
	public function testCreateAnswers201(): void {
		$this->service->method('create')->willReturn(['id' => 'd2', 'name' => 'New']);

		$response = $this->controller->create('New');

		self::assertSame(Http::STATUS_CREATED, $response->getStatus());

	}//end testCreateAnswers201()

	/**
	 * A refused lifecycle transition reaches the client as 409 with its message.
	 *
	 * The whole point of passing the service's code through: a 500 would tell
	 * the operator only that something broke, when what actually happened is a
	 * rule they can read and act on.
	 *
	 * @return void
	 */
	public function testRefusedTransitionKeepsItsStatusAndMessage(): void {
		$this->service->method('transition')->willThrowException(
			new RuntimeException('A dossier cannot move from "open" to "published".', 409)
		);

		$response = $this->controller->transition('d1', 'published');

		self::assertSame(409, $response->getStatus());
		self::assertSame(
			'A dossier cannot move from "open" to "published".',
			$response->getData()['error']
		);

	}//end testRefusedTransitionKeepsItsStatusAndMessage()

	/**
	 * An absent or unreadable dossier answers 404.
	 *
	 * @return void
	 */
	public function testUnreadableDossierAnswers404(): void {
		$this->service->method('detail')->willThrowException(
			new RuntimeException('Dossier not found, or you cannot read it: d9', 404)
		);

		self::assertSame(404, $this->controller->show('d9')->getStatus());

	}//end testUnreadableDossierAnswers404()

	/**
	 * A nonsense exception code does not become a nonsense HTTP status.
	 *
	 * `RuntimeException` codes are free-form. Passing one through unchecked
	 * would let a code of 0 or 7 reach the client as an HTTP status.
	 *
	 * @return void
	 */
	public function testOutOfRangeExceptionCodeBecomes500(): void {
		$this->service->method('detail')->willThrowException(new RuntimeException('odd', 7));

		self::assertSame(
			Http::STATUS_INTERNAL_SERVER_ERROR,
			$this->controller->show('d1')->getStatus()
		);

	}//end testOutOfRangeExceptionCodeBecomes500()

	/**
	 * An unexpected failure is logged and answers a generic 500.
	 *
	 * The internal message must not reach the client.
	 *
	 * @return void
	 */
	public function testUnexpectedFailureIsGeneric(): void {
		$this->service->method('detail')->willThrowException(
			new \LogicException('SQLSTATE[42S02]: table filinq_secret missing')
		);

		$response = $this->controller->show('d1');

		self::assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		self::assertStringNotContainsString('SQLSTATE', $response->getData()['error']);

	}//end testUnexpectedFailureIsGeneric()

	/**
	 * An unauthenticated caller is refused before the handler runs.
	 *
	 * @return void
	 */
	public function testUnauthenticatedIsRefusedBeforeTheHandler(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);

		$service = $this->createMock(DossierManagementService::class);
		$service->expects(self::never())->method('index');

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$controller = new DossierManagementController(
			'filinq',
			$this->createMock(IRequest::class),
			$service,
			$session,
			$l10n,
			$this->createMock(LoggerInterface::class),
		);

		self::assertSame(Http::STATUS_UNAUTHORIZED, $controller->index()->getStatus());

	}//end testUnauthenticatedIsRefusedBeforeTheHandler()

	/**
	 * The removal mode is reported so the confirmation can name the consequence.
	 *
	 * @return void
	 */
	public function testRemovalModeIsReported(): void {
		$this->service->method('removalMode')->willReturn('unlink');

		self::assertSame('unlink', $this->controller->removalMode('d1', 77)->getData()['mode']);

	}//end testRemovalModeIsReported()

}//end class
