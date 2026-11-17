<?php

/**
 * Tests for TemplatesController::forObject(), the generate-document leaf's list.
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
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Controller\TemplatesController;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The templates that apply to one host object.
 *
 * @covers \OCA\Filinq\Controller\TemplatesController
 * @uses   \OCA\Filinq\Integration\TemplateLeafRow
 */
class TemplatesControllerForObjectTest extends TestCase {

	/**
	 * The request.
	 *
	 * @var IRequest&MockObject
	 */
	private IRequest&MockObject $request;

	/**
	 * The template service.
	 *
	 * @var TemplateServiceInterface&MockObject
	 */
	private TemplateServiceInterface&MockObject $templates;

	/**
	 * The request handler, which builds error answers.
	 *
	 * @var TemplateRequestHandler&MockObject
	 */
	private TemplateRequestHandler&MockObject $handler;

	/**
	 * The user session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * Set up the mocks, with a signed-in user.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->request   = $this->createMock(IRequest::class);
		$this->templates = $this->createMock(TemplateServiceInterface::class);
		$this->handler   = $this->createMock(TemplateRequestHandler::class);
		$this->session   = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));

	}//end setUp()

	/**
	 * The controller under test, asked about one register and schema.
	 *
	 * @param string $register The register query parameter.
	 * @param string $schema   The schema query parameter.
	 *
	 * @return TemplatesController
	 */
	private function controller(string $register = 'dossiq', string $schema = 'case'): TemplatesController {
		$this->request->method('getParam')->willReturnMap([
			['register', '', $register],
			['schema', '', $schema],
		]);

		return new TemplatesController(
			'filinq',
			$this->request,
			$this->templates,
			$this->handler,
			$this->session,
			$this->createMock(LoggerInterface::class)
		);

	}//end controller()

	/**
	 * The scoped templates come back as leaf rows, nothing more of them.
	 *
	 * @return void
	 */
	public function testAnswersTheScopedTemplatesAsLeafRows(): void {
		$this->templates->expects($this->once())->method('listForObject')
			->with('dossiq', 'case')
			->willReturn([
				['id' => 't-1', 'name' => 'Besluit', 'format' => 'pdf', 'description' => 'Een besluit', 'content' => '<p>{{ x }}</p>'],
				['uuid' => 't-2', 'name' => 'Brief'],
			]);

		$response = $this->controller()->forObject();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			[
				'results' => [
					['id' => 't-1', 'name' => 'Besluit', 'format' => 'pdf', 'description' => 'Een besluit'],
					['id' => 't-2', 'name' => 'Brief', 'format' => '', 'description' => ''],
				],
			],
			$response->getData()
		);

	}//end testAnswersTheScopedTemplatesAsLeafRows()

	/**
	 * Without a register or schema nothing is listed: the answer would be
	 * every template, not the ones for this object.
	 *
	 * @return void
	 */
	public function testRefusesAMissingSchema(): void {
		$this->templates->expects($this->never())->method('listForObject');

		$response = $this->controller(schema: '  ')->forObject();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'register and schema are required'], $response->getData());

	}//end testRefusesAMissingSchema()

	/**
	 * An anonymous caller gets a 401 and no list.
	 *
	 * @return void
	 */
	public function testRefusesAnAnonymousCaller(): void {
		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn(null);
		$this->templates->expects($this->never())->method('listForObject');

		$response = $this->controller()->forObject();

		$this->assertSame(401, $response->getStatus());

	}//end testRefusesAnAnonymousCaller()

	/**
	 * A register that is not configured yet is an empty list, flagged.
	 *
	 * @return void
	 */
	public function testAnUnconfiguredRegisterIsAnEmptyList(): void {
		$this->templates->method('listForObject')->willThrowException(new RegisterNotConfiguredException('no register'));

		$response = $this->controller()->forObject();

		$this->assertSame(['results' => [], 'notConfigured' => true], $response->getData());

	}//end testAnUnconfiguredRegisterIsAnEmptyList()

	/**
	 * Any other failure goes through the shared error answer.
	 *
	 * @return void
	 */
	public function testAFailureUsesTheSharedErrorAnswer(): void {
		$failure = new Exception('down');
		$this->templates->method('listForObject')->willThrowException($failure);
		$error = new JSONResponse(['error' => 'x'], 500);
		$this->handler->expects($this->once())->method('buildErrorResponse')
			->with($failure, 'Failed to list templates: ')
			->willReturn($error);

		$this->assertSame($error, $this->controller()->forObject());

	}//end testAFailureUsesTheSharedErrorAnswer()
}//end class
