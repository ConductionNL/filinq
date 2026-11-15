<?php

/**
 * Characterization tests for TemplateVersionsController::versions() and restoreVersion()
 *
 * Pins the paging parameters, the editor handed to a restore, and the error mapping.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Controller\TemplateVersionsController;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateVersionServiceInterface;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of template version listing and restore.
 *
 * @covers \OCA\Filinq\Controller\TemplateVersionsController
 * @uses   \OCA\Filinq\Controller\TemplateRequestHandler
 * @uses   \OCA\Filinq\Controller\AnswersNotAuthenticated
 */
class TemplateVersionsControllerHistoryTraceTest extends TestCase {

	private TemplateVersionServiceInterface&MockObject $versions;

	private TemplateServiceInterface&MockObject $templates;

	private IRequest&MockObject $request;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->versions = $this->createMock(TemplateVersionServiceInterface::class);
		$this->templates = $this->createMock(TemplateServiceInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller, with or without a session user.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return TemplateVersionsController The controller.
	 */
	private function controller(bool $signedIn = true): TemplateVersionsController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		return new TemplateVersionsController(
			'filinq',
			$this->request,
			$this->templates,
			new TemplateRequestHandler($this->logger),
			$this->versions,
			$session
		);

	}//end controller()

	/**
	 * Listing passes _limit and _offset as integers and answers the result.
	 *
	 * @return void
	 */
	public function testVersionsPassesPagingThrough(): void {
		$this->request->method('getParam')->willReturnMap(
			[
				['_limit', '20', '5'],
				['_offset', '0', '10'],
			]
		);
		$this->versions->expects($this->once())
			->method('getVersions')
			->with('tpl-1', 5, 10)
			->willReturn(['results' => [['uuid' => 'v1']], 'total' => 1]);

		$response = $this->controller()->versions(id: 'tpl-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['results' => [['uuid' => 'v1']], 'total' => 1], $response->getData());

	}//end testVersionsPassesPagingThrough()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testVersionsWithoutASessionIs401(): void {
		$this->versions->expects($this->never())->method('getVersions');

		$response = $this->controller(signedIn: false)->versions(id: 'tpl-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testVersionsWithoutASessionIs401()

	/**
	 * A failing listing goes through the request handler: status from the code, logged with the prefix.
	 *
	 * @return void
	 */
	public function testAFailingListingIsMapped(): void {
		$this->request->method('getParam')->willReturnArgument(1);
		$this->versions->method('getVersions')->willThrowException(new Exception('Template not found', 404));
		$this->logger->expects($this->once())
			->method('error')
			->with('Failed to list versions: Template not found', $this->anything());

		$response = $this->controller()->versions(id: 'tpl-1');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Template not found'], $response->getData());

	}//end testAFailingListingIsMapped()

	/**
	 * A restore names the session user as editor and hands over the template service.
	 *
	 * @return void
	 */
	public function testRestoreNamesTheSessionUserAsEditor(): void {
		$this->versions->expects($this->once())
			->method('restoreVersion')
			->with('tpl-1', 'v3', 'anna', $this->identicalTo($this->templates))
			->willReturn(['uuid' => 'tpl-1', 'version' => 4]);

		$response = $this->controller()->restoreVersion(id: 'tpl-1', versionId: 'v3');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['uuid' => 'tpl-1', 'version' => 4], $response->getData());

	}//end testRestoreNamesTheSessionUserAsEditor()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testRestoreWithoutASessionIs401(): void {
		$this->versions->expects($this->never())->method('restoreVersion');

		$response = $this->controller(signedIn: false)->restoreVersion(id: 'tpl-1', versionId: 'v3');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testRestoreWithoutASessionIs401()

	/**
	 * A failure without an HTTP code is a 500 carrying the message.
	 *
	 * @return void
	 */
	public function testAFailingRestoreIsA500(): void {
		$this->versions->method('restoreVersion')->willThrowException(new Exception('locked', 1));
		$this->logger->expects($this->once())
			->method('error')
			->with('Failed to restore version: locked', $this->anything());

		$response = $this->controller()->restoreVersion(id: 'tpl-1', versionId: 'v3');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'locked'], $response->getData());

	}//end testAFailingRestoreIsA500()
}//end class
