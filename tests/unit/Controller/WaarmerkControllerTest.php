<?php

/**
 * Unit tests for the waarmerk controllers.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\WaarmerkController;
use OCA\Filinq\Controller\WaarmerkVerifyController;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\ProcessingCertificateService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\Waarmerk\SealedArtifact;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCA\Filinq\Service\Waarmerk\WaarmerkVerifier;
use OCA\Filinq\Service\WaarmerkService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Authenticated seal/revoke endpoints and the public verification surface.
 *
 * @covers \OCA\Filinq\Controller\WaarmerkController
 * @covers \OCA\Filinq\Controller\WaarmerkVerifyController
 * @uses   \OCA\Filinq\Service\Waarmerk\SealedArtifact
 * @uses   \OCA\Filinq\Service\Waarmerk\SealedFileWriter
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class WaarmerkControllerTest extends TestCase {

	/**
	 * The request parameters.
	 *
	 * @var array<string, mixed>
	 */
	private array $params = [];

	/**
	 * The waarmerk service double.
	 *
	 * @var WaarmerkService&MockObject
	 */
	private WaarmerkService&MockObject $waarmerk;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->waarmerk = $this->createMock(WaarmerkService::class);
	}//end setUp()

	/**
	 * The request double.
	 *
	 * @return IRequest
	 */
	private function request(): IRequest {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(fn (string $key, mixed $default = null): mixed => $this->params[$key] ?? $default);
		return $request;
	}//end request()

	/**
	 * The authenticated controller.
	 *
	 * @param string|null $uid The caller, or null for none.
	 * @param bool $admin Whether the caller is an admin.
	 * @param File|null $node What the file resolver answers.
	 * @param SealedFileWriter|null $writer The writer double.
	 *
	 * @return WaarmerkController
	 */
	private function controller(?string $uid = 'mallory', bool $admin = false, ?File $node = null, ?SealedFileWriter $writer = null): WaarmerkController {
		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn($admin);
		$files = $this->createMock(ReadableFileResolver::class);
		$files->method('node')->willReturn($node);

		return new WaarmerkController(
			appName: 'filinq',
			request: $this->request(),
			waarmerk: $this->waarmerk,
			certificates: $this->createMock(ProcessingCertificateService::class),
			files: $files,
			writer: $writer ?? $this->createMock(SealedFileWriter::class),
			userSession: $session,
			groupManager: $groups,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end controller()

	/**
	 * A non-admin non-sealer gets 403 from the in-method guard, with the reason passed through.
	 *
	 * @return void
	 */
	public function testRevokeByAStrangerIs403(): void {
		$this->params = ['reason' => 'document withdrawn after objection'];
		$this->waarmerk->expects($this->once())->method('revoke')
			->with('w-1', 'mallory', false, 'document withdrawn after objection')
			->willThrowException(new WaarmerkRefusedException(message: 'Only an admin or the sealer can revoke this waarmerk', status: 403));

		$response = $this->controller()->revoke(id: 'w-1');
		$this->assertSame(403, $response->getStatus());
		$this->assertSame('Only an admin or the sealer can revoke this waarmerk', $response->getData()['error']);
	}//end testRevokeByAStrangerIs403()

	/**
	 * No session: 401 and the service is never asked.
	 *
	 * @return void
	 */
	public function testUnauthenticatedIs401(): void {
		$this->waarmerk->expects($this->never())->method($this->anything());
		$controller = $this->controller(uid: null);
		foreach ([$controller->index(), $controller->show(id: 'w'), $controller->seal(), $controller->revoke(id: 'w'), $controller->certify(linkId: 'l')] as $response) {
			$this->assertSame(401, $response->getStatus());
		}
	}//end testUnauthenticatedIs401()

	/**
	 * Sealing a file the caller cannot read is a 404, nothing prepared.
	 *
	 * @return void
	 */
	public function testSealingAnUnreadableFileIs404(): void {
		$this->params = ['fileId' => 5];
		$this->waarmerk->expects($this->never())->method('prepare');
		$this->assertSame(404, $this->controller(node: null)->seal()->getStatus());
	}//end testSealingAnUnreadableFileIs404()

	/**
	 * Sealing a readable PDF prepares, writes next to it and answers 201; a 503 is passed through.
	 *
	 * @return void
	 */
	public function testSeal(): void {
		$this->params = ['fileId' => 5];
		$folder = $this->createMock(Folder::class);
		$node = $this->createMock(File::class);
		$node->method('getParent')->willReturn($folder);
		$node->method('getName')->willReturn('besluit.pdf');
		$node->method('getContent')->willReturn('%PDF-1.4');
		$node->method('getId')->willReturn(5);
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: []);
		$this->waarmerk->expects($this->exactly(2))->method('prepare')->with('%PDF-1.4', 'besluit-gewaarmerkt.pdf', 'alice')
			->willReturnOnConsecutiveCalls($artifact, $this->throwException(new WaarmerkRefusedException(message: 'Key resolution failed', status: 503)));
		$writer = $this->createMock(SealedFileWriter::class);
		$writer->expects($this->once())->method('write')->with($folder, 'besluit-gewaarmerkt.pdf', $artifact)->willReturn(['fileId' => 6, 'fileName' => 'besluit-gewaarmerkt.pdf', 'waarmerk' => ['uuid' => 'w-1']]);

		$controller = $this->controller(uid: 'alice', node: $node, writer: $writer);
		$created = $controller->seal();
		$this->assertSame(201, $created->getStatus());
		$this->assertSame(6, $created->getData()['fileId']);
		$this->assertSame(5, $created->getData()['sourceFileId']);

		$refused = $controller->seal();
		$this->assertSame(503, $refused->getStatus());
		$this->assertSame('Key resolution failed', $refused->getData()['error']);
	}//end testSeal()

	/**
	 * Show answers 404 for another user's seal; index passes the admin flag and file filter.
	 *
	 * @return void
	 */
	public function testShowAndIndex(): void {
		$this->waarmerk->method('show')->willReturn(null);
		$this->assertSame(404, $this->controller()->show(id: 'w-1')->getStatus());

		$this->params = ['documentFileId' => '7'];
		$this->waarmerk->expects($this->once())->method('list')->with('root', true, 7)->willReturn([['uuid' => 'w-1']]);
		$this->assertSame([['uuid' => 'w-1']], $this->controller(uid: 'root', admin: true)->index()->getData()['results']);
	}//end testShowAndIndex()

	/**
	 * The public verifier controller.
	 *
	 * @param WaarmerkVerifier $verifier The verifier double.
	 * @param IInitialState|null $state The initial state double.
	 *
	 * @return WaarmerkVerifyController
	 */
	private function verifyController(WaarmerkVerifier $verifier, ?IInitialState $state = null): WaarmerkVerifyController {
		return new WaarmerkVerifyController(appName: 'filinq', request: $this->request(), verifier: $verifier, initialState: $state ?? $this->createMock(IInitialState::class));
	}//end verifyController()

	/**
	 * Every outcome is a 200 of the verifier's answer; every non-valid one is throttled, a valid one not.
	 *
	 * @return void
	 */
	public function testVerifyThrottlesEveryNonValidAnswer(): void {
		foreach (['valid' => false, 'hash-mismatch' => true, 'revoked' => true, 'unknown' => true] as $status => $throttled) {
			$this->params = ['code' => 'c', 'sha256' => 'h'];
			$verifier = $this->createMock(WaarmerkVerifier::class);
			$verifier->expects($this->once())->method('verify')->with('c', 'h')->willReturn(['status' => $status]);

			$response = $this->verifyController(verifier: $verifier)->verify();
			$this->assertSame(200, $response->getStatus(), $status);
			$this->assertSame(['status' => $status], $response->getData(), $status);
			$this->assertSame($throttled, $response->isThrottled(), $status);
		}
	}//end testVerifyThrottlesEveryNonValidAnswer()

	/**
	 * The page is a guest template; its facts go through the initial state; an unknown code is throttled.
	 *
	 * @return void
	 */
	public function testVerifyPage(): void {
		$verifier = $this->createMock(WaarmerkVerifier::class);
		$verifier->method('page')->willReturnCallback(fn (string $code): array => ['status' => ($code === 'known') ? 'active' : 'unknown']);
		$provided = [];
		$state = $this->createMock(IInitialState::class);
		$state->method('provideInitialState')->willReturnCallback(function (string $key, mixed $data) use (&$provided): void {
			$provided[$key] = $data;
		});

		$known = $this->verifyController(verifier: $verifier, state: $state)->verifyPage(code: 'known');
		$this->assertSame('waarmerk-verify', $known->getTemplateName());
		$this->assertSame('guest', $known->getRenderAs());
		$this->assertFalse($known->isThrottled());
		$this->assertSame(['status' => 'active', 'code' => 'known'], $provided['waarmerk']);

		$this->assertTrue($this->verifyController(verifier: $verifier, state: $state)->verifyPage(code: 'nope')->isThrottled());
	}//end testVerifyPage()

	/**
	 * The public surfaces are public, CSRF-free, rate limited and brute-force protected; the rest is not public.
	 *
	 * @return void
	 */
	public function testRouteAttributes(): void {
		foreach (['verify', 'verifyPage'] as $method) {
			$names = array_map(fn ($a): string => $a->getName(), (new ReflectionMethod(WaarmerkVerifyController::class, $method))->getAttributes());
			foreach (['PublicPage', 'NoCSRFRequired', 'AnonRateLimit', 'BruteForceProtection'] as $attribute) {
				$this->assertContains('OCP\\AppFramework\\Http\\Attribute\\' . $attribute, $names, $method);
			}
		}

		foreach (['index', 'show', 'seal', 'revoke', 'certify'] as $method) {
			$names = array_map(fn ($a): string => $a->getName(), (new ReflectionMethod(WaarmerkController::class, $method))->getAttributes());
			$this->assertSame(['OCP\\AppFramework\\Http\\Attribute\\NoAdminRequired'], $names, $method);
		}
	}//end testRouteAttributes()
}//end class
