<?php

/**
 * Unit tests for SanitizationController
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
 * @spec openspec/changes/document-sanitization/tasks.md#3-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SanitizationController;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Sanitization\DocumentSanitizationService;
use OCA\Filinq\Service\Sanitization\SanitizationRecordRepository;
use OCA\Filinq\Service\Sanitization\SanitizationStatus;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A file outside the caller's folder is 404 with a generic body, for both
 * routes; no session is 401.
 */
class SanitizationControllerTest extends TestCase {

	/**
	 * The controller over real services and an empty user folder.
	 *
	 * @param bool $signedIn Whether a user is signed in.
	 *
	 * @return SanitizationController
	 */
	private function controller(bool $signedIn=true): SanitizationController {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([]);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$records = $this->createMock(SanitizationRecordRepository::class);
		$records->expects($this->never())->method('save');
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('mallory');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($signedIn === true ? $user : null);

		return new SanitizationController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			sanitizer: new DocumentSanitizationService(
				rootFolder: $root,
				locator: $this->createMock(OpenRegisterServiceLocator::class),
				records: $records,
				logger: $this->createMock(LoggerInterface::class)
			),
			status: new SanitizationStatus(rootFolder: $root, records: $records),
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end controller()

	/**
	 * Somebody else's file id is 404 with the same body, whatever exists.
	 *
	 * @return void
	 */
	public function testAFileOutsideTheCallersFolderIsNotFound(): void {
		foreach (['sanitize', 'status'] as $method) {
			$response = $this->controller()->{$method}(fileId: 813001);
			$this->assertSame(404, $response->getStatus());
			$this->assertSame(['error' => 'not_found'], $response->getData());
		}

	}//end testAFileOutsideTheCallersFolderIsNotFound()

	/**
	 * No session is 401.
	 *
	 * @return void
	 */
	public function testNoSessionIsUnauthorized(): void {
		$this->assertSame(401, $this->controller(signedIn: false)->sanitize(fileId: 1)->getStatus());

	}//end testNoSessionIsUnauthorized()
}//end class
