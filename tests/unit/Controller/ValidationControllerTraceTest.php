<?php

/**
 * Characterization tests for ValidationController::validate()
 *
 * Pins the order of the refusals (401, 400, 404), that the file is resolved
 * through the session user's own folder only, the arguments the validation
 * service receives, and the logged 500 on failure. Request values naming
 * another user never reach the folder lookup.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ValidationController;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the on-demand validation endpoint.
 *
 * @covers \OCA\Filinq\Controller\ValidationController
 */
class ValidationControllerTraceTest extends TestCase {

	private const FORGED = ['userId' => 'bob', 'requestedBy' => 'bob', 'owner' => 'bob'];

	private DocumentValidationServiceInterface&MockObject $service;

	private LoggerInterface&MockObject $logger;

	private File&MockObject $file;

	/**
	 * @var array<int, array{0: string, 1: int}> The user folder and file id of every lookup.
	 */
	private array $lookups = [];

	/**
	 * @var array<int, mixed> What alice's folder answers for file 42.
	 */
	private array $aliceNodes = [];

	/**
	 * Fresh doubles: only alice's folder holds file 42; every other user's folder is empty.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(DocumentValidationServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->file = $this->createMock(File::class);
		$this->aliceNodes = [$this->file, $this->createMock(File::class)];

	}//end setUp()

	/**
	 * Alice's own file is validated with the request's document type; forged identity values change nothing.
	 *
	 * @return void
	 */
	public function testOwnFileIsValidated(): void {
		$this->service->expects($this->once())->method('validate')
			->with($this->identicalTo($this->file), $this->identicalTo([]), $this->identicalTo('invoice'))
			->willReturn(['verdict' => 'pass', 'findings' => []]);

		$response = $this->controller(params: ['fileId' => '42', 'documentType' => 'invoice'])->validate();

		$this->assertSame([['alice', 42]], $this->lookups);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['verdict' => 'pass', 'findings' => []], $response->getData());

	}//end testOwnFileIsValidated()

	/**
	 * Without a document type the service gets null; a non-string one is cast to string.
	 *
	 * @return void
	 */
	public function testDocumentTypeIsOptionalAndCastToString(): void {
		$seen = [];
		$this->service->method('validate')->willReturnCallback(
			function (File $file, array $record, ?string $documentType) use (&$seen): array {
				$seen[] = $documentType;
				return [];
			}
		);

		$this->controller(params: ['fileId' => 42])->validate();
		$this->controller(params: ['fileId' => 42, 'documentType' => 7])->validate();
		$this->controller(params: ['fileId' => 42, 'documentType' => ''])->validate();

		$this->assertSame([null, '7', ''], $seen);

	}//end testDocumentTypeIsOptionalAndCastToString()

	/**
	 * A missing or non-positive file id is a 400 before any lookup.
	 *
	 * @return void
	 */
	public function testMissingFileIdIsBadRequest(): void {
		$this->service->expects($this->never())->method('validate');

		foreach ([[], ['fileId' => 0], ['fileId' => -3], ['fileId' => 'abc']] as $params) {
			$response = $this->controller(params: $params)->validate();
			$this->assertSame(400, $response->getStatus());
			$this->assertSame(['error' => 'fileId is required'], $response->getData());
		}

		$this->assertSame([], $this->lookups);

	}//end testMissingFileIdIsBadRequest()

	/**
	 * A file only another user holds is a 404 for alice, and alice's file is a 404 for bob even when the request names alice.
	 *
	 * @return void
	 */
	public function testFileOutsideTheSessionUsersFolderIsNotFound(): void {
		$this->service->expects($this->never())->method('validate');

		$response = $this->controller(params: ['fileId' => 43])->validate();
		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'File not found'], $response->getData());

		$forged = ['fileId' => 42, 'userId' => 'alice', 'requestedBy' => 'alice', 'owner' => 'alice'];
		$this->assertSame(404, $this->controller(params: $forged, uid: 'bob')->validate()->getStatus());

		$this->assertSame([['alice', 43], ['bob', 42]], $this->lookups);

	}//end testFileOutsideTheSessionUsersFolderIsNotFound()

	/**
	 * A node that is not a file is a 404.
	 *
	 * @return void
	 */
	public function testNonFileNodeIsNotFound(): void {
		$this->aliceNodes = [$this->createMock(Folder::class), $this->file];
		$this->service->expects($this->never())->method('validate');

		$response = $this->controller(params: ['fileId' => 42])->validate();

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'File not found'], $response->getData());

	}//end testNonFileNodeIsNotFound()

	/**
	 * A failing validation is logged with the file id and answered 500.
	 *
	 * @return void
	 */
	public function testValidationFailureAnswers500(): void {
		$this->service->method('validate')->willThrowException(new RuntimeException('parser crashed'));
		$this->logger->expects($this->once())->method('error')->with(
			$this->identicalTo('Validation failed'),
			$this->identicalTo(['fileId' => 42, 'exception' => 'parser crashed'])
		);

		$response = $this->controller(params: ['fileId' => '42'])->validate();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Validation failed'], $response->getData());

	}//end testValidationFailureAnswers500()

	/**
	 * Without a session user nothing is looked up or validated.
	 *
	 * @return void
	 */
	public function testNotAuthenticated(): void {
		$this->service->expects($this->never())->method('validate');

		$response = $this->controller(params: ['fileId' => 42], uid: null)->validate();

		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());
		$this->assertSame([], $this->lookups);

	}//end testNotAuthenticated()

	/**
	 * The controller over a request carrying forged identity values through getParams() and getParam().
	 *
	 * @param array<string, mixed> $params The request values on top of the forged ones.
	 * @param string|null $uid The session user, or null for none.
	 *
	 * @return ValidationController The controller.
	 */
	private function controller(array $params, ?string $uid = 'alice'): ValidationController {
		$all = array_merge(self::FORGED, $params);
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($all);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $all) === true ? $all[$key] : $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(
			function (string $userId): Folder {
				$folder = $this->createMock(Folder::class);
				$folder->method('getById')->willReturnCallback(
					function (int $fileId) use ($userId): array {
						$this->lookups[] = [$userId, $fileId];
						if ($userId === 'alice' && $fileId === 42) {
							return $this->aliceNodes;
						}

						return [];
					}
				);
				return $folder;
			}
		);

		return new ValidationController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			service: $this->service,
			rootFolder: $root,
			l10n: $l10n,
			userSession: $session,
		);
	}//end controller()
}//end class
