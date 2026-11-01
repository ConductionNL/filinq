<?php

/**
 * Characterization tests for DossierController::generateGrondslagenSummary()
 *
 * Pins the access check before the render, the refusals, the rethrow of
 * other failures, and both responses. The dossier checked and rendered is
 * the route's own id: request values naming another user never reach it.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/fix-dossier-grondslagen-route-mismatch/tasks.md#task-R-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\DossierController;
use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the dossier summary endpoint.
 *
 * @covers \OCA\Filinq\Controller\DossierController
 */
class DossierControllerSummaryTraceTest extends TestCase {

	private const FORGED = ['userId' => 'admin', 'requestedBy' => 'admin', 'owner' => 'admin', 'dossierId' => 'other-dossier'];

	private LegalBasesSummaryServiceInterface&MockObject $summary;

	private LoggerInterface&MockObject $logger;

	/**
	 * @var array<int, string> The dossier ids the access check ran for.
	 */
	private array $checked = [];

	/**
	 * Fresh doubles: the access check lets only 'dossier-1' and 'dossier-9' through, refusing others with 403.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->summary = $this->createMock(LegalBasesSummaryServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->summary->method('authorizeAccess')->willReturnCallback(
			function (string $dossierId): void {
				$this->checked[] = $dossierId;
				if (in_array($dossierId, ['dossier-1', 'dossier-9'], true) === false) {
					throw new RuntimeException('forbidden', 403);
				}
			}
		);

	}//end setUp()

	/**
	 * An authorised dossier is rendered and its file described; forged request values change nothing.
	 *
	 * @return void
	 */
	public function testAuthorisedDossierIsRenderedForTheRouteIdOnly(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(77);
		$file->method('getName')->willReturn('grondslagen.pdf');
		$file->method('getPath')->willReturn('/admin/files/grondslagen.pdf');
		$file->method('getSize')->willReturn(1234);
		$this->summary->expects($this->once())->method('renderDossierSummary')->with('dossier-1')->willReturn($file);

		$response = $this->controller()->generateGrondslagenSummary(dossierId: 'dossier-1');

		$this->assertSame(['dossier-1'], $this->checked);
		$this->assertSame(200, $response->getStatus());
		$data = $response->getData();
		$this->assertSame(77, $data['fileId']);
		$this->assertSame('grondslagen.pdf', $data['filename']);
		$this->assertSame('/admin/files/grondslagen.pdf', $data['filePath']);
		$this->assertSame(1234, $data['size']);
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $data['generatedAt']);
		$this->assertCount(5, $data);

	}//end testAuthorisedDossierIsRenderedForTheRouteIdOnly()

	/**
	 * Another dossier is refused with 403 and never rendered, whatever the request claims.
	 *
	 * @return void
	 */
	public function testForeignDossierIsRefusedAndNotRendered(): void {
		$this->summary->expects($this->never())->method('renderDossierSummary');

		$response = $this->controller()->generateGrondslagenSummary(dossierId: 'dossier-2');

		$this->assertSame(['dossier-2'], $this->checked);
		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'This dossier does not exist or you may not read it.'], $response->getData());

	}//end testForeignDossierIsRefusedAndNotRendered()

	/**
	 * An unknown dossier from the mapper is refused the same way.
	 *
	 * @return void
	 */
	public function testUnknownDossierIsRefused(): void {
		$summary = $this->createMock(LegalBasesSummaryServiceInterface::class);
		$summary->method('authorizeAccess')->willThrowException(new DoesNotExistException('nope'));
		$summary->expects($this->never())->method('renderDossierSummary');
		$this->summary = $summary;

		$this->assertSame(403, $this->controller()->generateGrondslagenSummary(dossierId: 'x')->getStatus());

	}//end testUnknownDossierIsRefused()

	/**
	 * Any other access-check failure is rethrown, not turned into a refusal or a render.
	 *
	 * @return void
	 */
	public function testOtherAccessFailureIsRethrown(): void {
		$summary = $this->createMock(LegalBasesSummaryServiceInterface::class);
		$summary->method('authorizeAccess')->willThrowException(new RuntimeException('db down', 500));
		$summary->expects($this->never())->method('renderDossierSummary');
		$this->summary = $summary;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('db down');
		$this->controller()->generateGrondslagenSummary(dossierId: 'dossier-1');

	}//end testOtherAccessFailureIsRethrown()

	/**
	 * A failing render is logged with the dossier and answered 500.
	 *
	 * @return void
	 */
	public function testRenderFailureAnswers500(): void {
		$failure = new Exception('no template', 422);
		$this->summary->expects($this->once())->method('renderDossierSummary')->with('dossier-9')->willThrowException($failure);
		$this->logger->expects($this->once())->method('error')->with(
			'DossierController::generateGrondslagenSummary failed: no template',
			['dossierId' => 'dossier-9', 'exception' => $failure]
		);

		$response = $this->controller()->generateGrondslagenSummary(dossierId: 'dossier-9');

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Failed to generate dossier summary: no template'], $response->getData());

	}//end testRenderFailureAnswers500()

	/**
	 * Without a session user nothing is checked or rendered.
	 *
	 * @return void
	 */
	public function testNotAuthenticated(): void {
		$this->summary->expects($this->never())->method('renderDossierSummary');

		$this->assertSame(401, $this->controller(signedIn: false)->generateGrondslagenSummary(dossierId: 'dossier-1')->getStatus());
		$this->assertSame([], $this->checked);

	}//end testNotAuthenticated()

	/**
	 * The controller over a request that carries forged identity values through getParams() and getParam().
	 *
	 * @param bool $signedIn Whether a session user exists.
	 *
	 * @return DossierController The controller.
	 */
	private function controller(bool $signedIn = true): DossierController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(self::FORGED);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (self::FORGED[$key] ?? $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('alice');
		}

		$session->method('getUser')->willReturn($user);

		return new DossierController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			grondslagenSummary: $this->summary,
			l10n: $l10n,
			userSession: $session,
		);
	}//end controller()
}//end class
