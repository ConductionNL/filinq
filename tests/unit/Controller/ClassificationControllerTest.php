<?php

/**
 * Tests for ClassificationController
 *
 * The controller over the real decision service: the caller's session decides
 * which files are reachable, the body carries only the three correction keys,
 * a refusal keeps its status and anything else is a generic 500.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/Classification/ClassificationDecisionFixture.php';

use OCA\Filinq\Controller\ClassificationController;
use OCA\Filinq\Service\Classification\ClassificationDecisionService;
use OCA\Filinq\Tests\Unit\Service\Classification\ClassificationDecisionFixture;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The classification endpoints.
 */
class ClassificationControllerTest extends TestCase {
	use ClassificationDecisionFixture;

	/**
	 * The request parameters.
	 *
	 * @var array<string, mixed>
	 */
	private array $params = [];

	/**
	 * Fresh stores.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->resetFixture();
		$this->params = [];

	}//end setUp()

	/**
	 * The pending list is the caller's.
	 *
	 * @return void
	 */
	public function testPendingListsTheCallersSuggestions(): void {
		$this->suggestion(fileId: 1);
		$this->suggestion(fileId: 2);
		$this->reachable(userId: 'annemarie', fileId: 2);

		$response = $this->controller(userId: 'annemarie')->pending();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame([2], array_column($response->getData()['results'], 'fileId'));

	}//end testPendingListsTheCallersSuggestions()

	/**
	 * Confirm passes only documentType, correspondent and dossier from the body.
	 *
	 * @return void
	 */
	public function testConfirmTakesOnlyTheCorrectionKeys(): void {
		$this->suggestion(fileId: 812010);
		$this->reachable(userId: 'annemarie', fileId: 812010);
		$this->params = ['fileId' => 812010, 'documentType' => 'besluit', 'status' => 'rejected', 'confirmedBy' => 'mallory', '_route' => 'filinq.classification.confirm'];

		$response = $this->controller(userId: 'annemarie')->confirm(fileId: 812010);
		$data = $response->getData();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('confirmed', $data['status']);
		$this->assertSame('besluit', $data['confirmedDocumentType']);
		$this->assertSame('annemarie', $data['confirmedBy']);

	}//end testConfirmTakesOnlyTheCorrectionKeys()

	/**
	 * Reject answers the closed record.
	 *
	 * @return void
	 */
	public function testRejectClosesTheSuggestion(): void {
		$this->suggestion(fileId: 812010);
		$this->reachable(userId: 'annemarie', fileId: 812010);

		$response = $this->controller(userId: 'annemarie')->reject(fileId: 812010);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('rejected', $response->getData()['status']);
		$this->assertSame([], $this->documentSaves);

	}//end testRejectClosesTheSuggestion()

	/**
	 * One file's record for the document card; a file the caller cannot open, or without a record, is a 404.
	 *
	 * @return void
	 */
	public function testShowAnswersTheFilesRecord(): void {
		$this->suggestion(fileId: 1);
		$this->suggestion(fileId: 2);
		$this->reachable(userId: 'annemarie', fileId: 1);
		$this->reachable(userId: 'bram', fileId: 2);

		$controller = $this->controller(userId: 'annemarie');
		$response = $controller->show(fileId: 1);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('suggested', $response->getData()['status']);
		$this->assertSame(404, $controller->show(fileId: 2)->getStatus());
		$this->assertSame(404, $controller->show(fileId: 9)->getStatus());

	}//end testShowAnswersTheFilesRecord()

	/**
	 * Another user's file is a 404, a decided one a 409, an unknown type a 422.
	 *
	 * @return void
	 */
	public function testRefusalsKeepTheirStatus(): void {
		$this->suggestion(fileId: 1);
		$this->suggestion(fileId: 2, fields: ['status' => 'rejected']);
		$this->suggestion(fileId: 3);
		$this->reachable(userId: 'bram', fileId: 1);
		$this->reachable(userId: 'annemarie', fileId: 2);
		$this->reachable(userId: 'annemarie', fileId: 3);

		$controller = $this->controller(userId: 'annemarie');
		$this->assertSame(404, $controller->confirm(fileId: 1)->getStatus());
		$this->assertSame(404, $controller->reject(fileId: 1)->getStatus());
		$this->assertSame(409, $controller->reject(fileId: 2)->getStatus());
		$this->params = ['documentType' => 'memo'];
		$response = $controller->confirm(fileId: 3);
		$this->assertSame(422, $response->getStatus());
		$this->assertSame(['error' => 'Unknown document type'], $response->getData());

	}//end testRefusalsKeepTheirStatus()

	/**
	 * Without a session no file is reachable.
	 *
	 * @return void
	 */
	public function testWithoutASessionNothingIsReachable(): void {
		$this->suggestion(fileId: 1);
		$this->reachable(userId: 'annemarie', fileId: 1);

		$controller = $this->controller(userId: null);

		$this->assertSame([], $controller->pending()->getData()['results']);
		$this->assertSame(404, $controller->confirm(fileId: 1)->getStatus());

	}//end testWithoutASessionNothingIsReachable()

	/**
	 * An unexpected failure is a 500 with a generic body.
	 *
	 * @return void
	 */
	public function testAnUnexpectedFailureIsAGeneric500(): void {
		$decisions = $this->createMock(ClassificationDecisionService::class);
		$decisions->method('pending')->willThrowException(new RuntimeException('database password is hunter2'));

		$controller = new ClassificationController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			decisions: $decisions,
			userSession: $this->session(userId: 'annemarie'),
			logger: new NullLogger(),
		);
		$response = $controller->pending();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'failed'], $response->getData());

	}//end testAnUnexpectedFailureIsAGeneric500()

	/**
	 * The controller over the real decision service.
	 *
	 * @param string|null $userId The caller, null without a session.
	 *
	 * @return ClassificationController The controller.
	 */
	private function controller(?string $userId): ClassificationController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturnCallback(fn (): array => $this->params);

		return new ClassificationController(
			appName: 'filinq',
			request: $request,
			decisions: $this->decisions(),
			userSession: $this->session(userId: $userId),
			logger: new NullLogger(),
		);

	}//end controller()

	/**
	 * A session for a user, or none.
	 *
	 * @param string|null $userId The user.
	 *
	 * @return IUserSession The session.
	 */
	private function session(?string $userId): IUserSession {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($userId !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($userId);
		}

		$session->method('getUser')->willReturn($user);

		return $session;

	}//end session()
}//end class
