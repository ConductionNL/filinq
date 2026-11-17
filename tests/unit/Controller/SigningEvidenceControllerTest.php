<?php

/**
 * Unit tests for SigningEvidenceController and WaarmerkSettingsController.
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

use OCA\Filinq\Controller\SigningEvidenceController;
use OCA\Filinq\Controller\WaarmerkSettingsController;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\SealedArtifact;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\SigningEvidenceService;
use OCA\Filinq\Service\Waarmerk\WaarmerkCms;
use OCA\Filinq\Service\Waarmerk\WaarmerkSettings;
use OCA\Filinq\Service\WaarmerkService;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Evidence download / copy, and the admin certificate endpoints.
 *
 * @covers \OCA\Filinq\Controller\SigningEvidenceController
 * @covers \OCA\Filinq\Controller\WaarmerkSettingsController
 * @uses   \OCA\Filinq\Kernel\SealedArtifact
 * @uses   \OCA\Filinq\Exception\WaarmerkRefusedException
 */
class SigningEvidenceControllerTest extends TestCase {

	/**
	 * The request parameters.
	 *
	 * @var array<string, mixed>
	 */
	private array $params = [];

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
	 * A session for a user.
	 *
	 * @param string $uid The uid.
	 *
	 * @return IUserSession
	 */
	private function session(string $uid): IUserSession {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		return $session;
	}//end session()

	/**
	 * The evidence controller.
	 *
	 * @param SigningEvidenceService $evidence The service double.
	 * @param bool $admin Whether the caller is an admin.
	 *
	 * @return SigningEvidenceController
	 */
	private function evidenceController(SigningEvidenceService $evidence, bool $admin): SigningEvidenceController {
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn($admin);
		return new SigningEvidenceController(appName: 'filinq', request: $this->request(), evidence: $evidence, userSession: $this->session(uid: 'ann'), groupManager: $groups, logger: $this->createMock(LoggerInterface::class));
	}//end evidenceController()

	/**
	 * Download answers the PDF; a non-admin is scoped to themselves, an admin unscoped.
	 *
	 * @return void
	 */
	public function testDownloadScopesTheCaller(): void {
		$evidence = $this->createMock(SigningEvidenceService::class);
		$evidence->expects($this->exactly(2))->method('download')
			->willReturnCallback(fn (string $requestId, string $callerScope, string $userId): array => ['pdf' => '%PDF ' . $callerScope, 'fileName' => 'b.pdf', 'waarmerk' => []]);

		$mine = $this->evidenceController(evidence: $evidence, admin: false)->download(id: 'req-1');
		$this->assertInstanceOf(DataDownloadResponse::class, $mine);
		$this->assertSame('%PDF ann', $mine->getData());
		$this->assertSame('%PDF ', $this->evidenceController(evidence: $evidence, admin: true)->download(id: 'req-1')->getData());
	}//end testDownloadScopesTheCaller()

	/**
	 * An open request is a 409 that names the open signers; copy answers 201.
	 *
	 * @return void
	 */
	public function testOpenRequestAndCopy(): void {
		$evidence = $this->createMock(SigningEvidenceService::class);
		$evidence->method('download')->willThrowException(new WaarmerkRefusedException(message: 'still open', status: 409, details: ['openSigners' => ['Cor']]));
		$evidence->method('copyToDossier')->willReturn(['fileId' => 12]);
		$controller = $this->evidenceController(evidence: $evidence, admin: false);

		$open = $controller->download(id: 'req-1');
		$this->assertSame(409, $open->getStatus());
		$this->assertSame(['error' => 'still open', 'openSigners' => ['Cor']], $open->getData());
		$copy = $controller->copy(id: 'req-1');
		$this->assertSame(201, $copy->getStatus());
		$this->assertSame(['fileId' => 12], $copy->getData());
	}//end testOpenRequestAndCopy()

	/**
	 * Update passes string fields only; a refusal is answered with its status.
	 *
	 * @return void
	 */
	public function testSettingsUpdate(): void {
		$this->params = ['certificate' => 'PEM', 'custody' => 'local', 'localKey' => ['not', 'a', 'string']];
		$settings = $this->createMock(WaarmerkSettings::class);
		$settings->expects($this->exactly(2))->method('save')->with(['certificate' => 'PEM', 'custody' => 'local'])
			->willReturnOnConsecutiveCalls(['configured' => true], $this->throwException(new WaarmerkRefusedException(message: 'bad', status: 400)));
		$controller = new WaarmerkSettingsController(
			appName: 'filinq',
			request: $this->request(),
			settings: $settings,
			waarmerk: $this->createMock(WaarmerkService::class),
			cms: $this->createMock(WaarmerkCms::class),
			pdfService: $this->createMock(PdfService::class),
			userSession: $this->session(uid: 'root')
		);

		$this->assertSame(['configured' => true], $controller->update()->getData());
		$this->assertSame(400, $controller->update()->getStatus());
	}//end testSettingsUpdate()

	/**
	 * The test seal checks hash and CMS in memory and records nothing; a key failure is its 503.
	 *
	 * @return void
	 */
	public function testTestSeal(): void {
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('generatePdfFromHtml')->willReturn('%PDF test');
		$artifact = new SealedArtifact(pdf: '%PDF sealed', record: ['documentHash' => hash('sha256', '%PDF sealed'), 'cmsSignature' => 'CMS', 'certificateFingerprint' => 'AA:BB']);
		$waarmerk = $this->createMock(WaarmerkService::class);
		$waarmerk->expects($this->never())->method('record');
		$waarmerk->method('prepare')->willReturnOnConsecutiveCalls($artifact, $artifact, $this->throwException(new WaarmerkRefusedException(message: 'Key resolution failed', status: 503)));
		$cms = $this->createMock(WaarmerkCms::class);
		$cms->method('verify')->with(hash('sha256', '%PDF sealed'), 'CMS', 'AA:BB')->willReturnOnConsecutiveCalls('SIGNER', null);
		$controller = new WaarmerkSettingsController(appName: 'filinq', request: $this->request(), settings: $this->createMock(WaarmerkSettings::class), waarmerk: $waarmerk, cms: $cms, pdfService: $pdf, userSession: $this->session(uid: 'root'));

		$this->assertSame(['ok' => true, 'fingerprint' => 'AA:BB'], $controller->testSeal()->getData());
		$this->assertFalse($controller->testSeal()->getData()['ok']);
		$failed = $controller->testSeal();
		$this->assertSame(503, $failed->getStatus());
		$this->assertSame('Key resolution failed', $failed->getData()['error']);
	}//end testTestSeal()
}//end class
