<?php

/**
 * The signing folder's HTTP surface, asserted from the caller.
 *
 * 🔴 THESE ARE CALLER-SIDE ASSERTIONS ON PURPOSE. SigningFolderService has its
 * own suite, and nothing in it can see whether a route ever reaches it.
 *
 * 🔴 AN EMPTY SELECTION IS REFUSED RATHER THAN SIGNED. "Sign nothing" answering
 * 200 is how a clerk believes a batch was signed that never was.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SigningFolderController;
use OCA\Filinq\Service\SigningFolderService;
use OCA\Filinq\Service\SigningMandateService;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * `SigningFolderController`.
 *
 * @covers \OCA\Filinq\Controller\SigningFolderController
 */
class SigningFolderControllerTest extends TestCase {

	/**
	 * A folder-service double.
	 *
	 * `onlyMethods` so it cannot answer a question the real service lacks.
	 *
	 * @return SigningFolderService The double.
	 */
	private function folderService(): SigningFolderService {
		return $this->getMockBuilder(SigningFolderService::class)
			->disableOriginalConstructor()
			->onlyMethods(['signSelection'])
			->getMock();
	}//end folderService()

	/**
	 * A controller over the given service, session and request body.
	 *
	 * @param SigningFolderService      $service    The folder service.
	 * @param array<string, mixed>|null $requestIds What `requestIds` holds, or null for nothing.
	 * @param bool                      $loggedIn   Whether anybody is logged in.
	 *
	 * @return SigningFolderController The controller.
	 */
	private function controller(
		SigningFolderService $service,
		?array $requestIds = null,
		bool $loggedIn = true,
	): SigningFolderController {
		$session = $this->createMock(IUserSession::class);
		if ($loggedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('ondertekenaar');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturn($requestIds ?? []);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new SigningFolderController(
			'filinq',
			$request,
			$service,
			$this->getMockBuilder(SigningMandateService::class)
				->disableOriginalConstructor()
				->onlyMethods(['declarations'])
				->getMock(),
			$session,
			$this->createMock(LoggerInterface::class),
			$l10n
		);
	}//end controller()

	/**
	 * A selection is signed for the person asking, and the result comes back.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function testASelectionIsSignedForThePersonAsking(): void {
		$service = $this->folderService();
		$service->expects($this->once())
			->method('signSelection')
			->with(['req-1', 'req-2'], 'ondertekenaar')
			->willReturn(['signed' => ['req-1'], 'refused' => ['req-2']]);

		$response = $this->controller($service, ['req-1', 'req-2'])->signFolder();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['req-1'], $response->getData()['signed']);
	}//end testASelectionIsSignedForThePersonAsking()

	/**
	 * An empty selection is refused rather than reported as signed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function testAnEmptySelectionIsRefused(): void {
		$service = $this->folderService();
		$service->expects($this->never())->method('signSelection');

		$response = $this->controller($service, [])->signFolder();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}//end testAnEmptySelectionIsRefused()

	/**
	 * With nobody logged in nothing is signed, in nobody's name.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function testNothingIsSignedForNobody(): void {
		$service = $this->folderService();
		$service->expects($this->never())->method('signSelection');

		$response = $this->controller($service, ['req-1'], false)->signFolder();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}//end testNothingIsSignedForNobody()
}//end class
