<?php

/**
 * Characterization tests for the portal signer lookup (anti-IDOR boundary)
 *
 * Drives signDocument() end to end and pins exactly what the signerRecord
 * lookup asks OpenRegister for and which returned row it accepts: only a
 * row matching BOTH the verified assertion email and the requested id.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\PortalSigningReceiverController;
use OCA\Filinq\Portal\PortalAssertionVerifier;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\PortalSigningDocumentResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The signer lookup behind every portal signing act.
 *
 * @covers \OCA\Filinq\Controller\PortalSigningReceiverController
 * @uses   \OCA\Filinq\Portal\PortalAssertionVerifier
 * @uses   \OCA\Filinq\Service\OpenRegisterResolver
 * @uses   \OCA\Filinq\Service\PortalSigningDocumentResolver
 */
class PortalSigningReceiverSignerLookupTraceTest extends TestCase {

	private const SECRET = 'filinq-receiver-test-secret-01';

	private const SIGNER = ['id' => 'signer-1', 'email' => 'alice@example.com', 'signingRequestId' => 'request-uuid-1', 'status' => 'PENDING'];

	private SigningService&MockObject $signing;

	private SettingsService&MockObject $settings;

	private ObjectService&MockObject $objects;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningService::class);
		$this->settings = $this->createMock(SettingsService::class);
		$this->objects = $this->createMock(ObjectService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->settings->method('getObjectService')->willReturn($this->objects);
		$this->settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);

	}//end setUp()

	/**
	 * The lookup is scoped by register, schema, the ASSERTED email and the requested id,
	 * whatever the request body claims, and runs as a system lookup.
	 *
	 * @return void
	 */
	public function testLookupIsScopedByTheAssertedEmailNotTheRequest(): void {
		$this->objects->expects($this->once())->method('findAll')
			->with(
				['filters' => ['register' => 'filinq', 'schema' => 'signerRecord', 'email' => 'alice@example.com', 'signingRequestId' => 'request-uuid-1']],
				false,
				false
			)
			->willReturn([self::SIGNER]);
		$this->signing->expects($this->once())->method('sign')
			->with('request-uuid-1', 'signer-1', $this->callback(static fn (array $actor): bool => $actor['email'] === 'alice@example.com'), $this->anything())
			->willReturn(['status' => 'signed']);

		$params = ['signingRequestId' => 'request-uuid-1', 'requestId' => 'request-uuid-2', 'email' => 'mallory@example.com', 'signerEmail' => 'mallory@example.com', 'userId' => 'admin', 'requestedBy' => 'admin'];
		$response = $this->controller(params: $params)->signDocument();

		$this->assertSame(200, $response->getStatus());

	}//end testLookupIsScopedByTheAssertedEmailNotTheRequest()

	/**
	 * The email match ignores case.
	 *
	 * @return void
	 */
	public function testEmailMatchIgnoresCase(): void {
		$this->objects->method('findAll')->willReturn([['email' => 'Alice@Example.COM'] + self::SIGNER]);
		$this->signing->expects($this->once())->method('sign')->willReturn(['status' => 'signed']);

		$this->assertSame(200, $this->controller(params: ['signingRequestId' => 'request-uuid-1'])->signDocument()->getStatus());

	}//end testEmailMatchIgnoresCase()

	/**
	 * A returned row for another request or another signer is never accepted.
	 *
	 * @return void
	 */
	public function testARowForAnotherRequestOrSignerIsRefused(): void {
		foreach ([['signingRequestId' => 'request-uuid-2'], ['email' => 'mallory@example.com']] as $drift) {
			$this->setUp();
			$this->objects->method('findAll')->willReturn([$drift + self::SIGNER]);
			$this->signing->expects($this->never())->method('sign');

			$response = $this->controller(params: ['signingRequestId' => 'request-uuid-1'])->signDocument();

			$this->assertSame(403, $response->getStatus(), (string)json_encode($drift));
		}

	}//end testARowForAnotherRequestOrSignerIsRefused()

	/**
	 * An unreadable row is skipped and a later matching row is still found.
	 *
	 * @return void
	 */
	public function testAnUnreadableRowIsSkipped(): void {
		$this->objects->method('findAll')->willReturn(['junk', self::SIGNER]);
		$this->signing->expects($this->once())->method('sign')->willReturn(['status' => 'signed']);

		$this->assertSame(200, $this->controller(params: ['signingRequestId' => 'request-uuid-1'])->signDocument()->getStatus());

	}//end testAnUnreadableRowIsSkipped()

	/**
	 * No object store, or a failing lookup, refuses the act.
	 *
	 * @return void
	 */
	public function testNoStoreOrAFailingLookupRefuses(): void {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willThrowException(new RuntimeException('OpenRegister missing'));
		$settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);
		$this->settings = $settings;
		$this->signing->expects($this->never())->method('sign');
		$this->logger->expects($this->never())->method('debug');
		$this->assertSame(403, $this->controller(params: ['signingRequestId' => 'request-uuid-1'])->signDocument()->getStatus());

		$this->setUp();
		$this->objects->method('findAll')->willThrowException(new RuntimeException('db down'));
		$this->logger->expects($this->once())->method('debug')->with('Filinq: portal signer lookup failed', ['reason' => 'db down']);
		$this->signing->expects($this->never())->method('sign');
		$this->assertSame(403, $this->controller(params: ['signingRequestId' => 'request-uuid-1'])->signDocument()->getStatus());

	}//end testNoStoreOrAFailingLookupRefuses()

	/**
	 * No matching row refuses the act.
	 *
	 * @return void
	 */
	public function testNoRowRefuses(): void {
		$this->objects->method('findAll')->willReturn([]);
		$this->signing->expects($this->never())->method('sign');

		$this->assertSame(403, $this->controller(params: ['signingRequestId' => 'request-uuid-1'])->signDocument()->getStatus());

	}//end testNoRowRefuses()

	/**
	 * The controller over a request carrying a valid assertion for alice and $params.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 *
	 * @return PortalSigningReceiverController The controller.
	 */
	private function controller(array $params): PortalSigningReceiverController {
		$assertion = $this->assertion();
		$request = $this->createMock(IRequest::class);
		$request->method('getHeader')->willReturnCallback(static fn (string $name): string => ($name === PortalAssertionVerifier::HEADER ? $assertion : ''));
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);
		$request->method('getParams')->willReturn($params);

		return new PortalSigningReceiverController(
			appName: 'filinq',
			request: $request,
			verifier: new PortalAssertionVerifier(secretOverride: self::SECRET),
			signingService: $this->signing,
			settingsService: $this->settings,
			registerResolver: new OpenRegisterResolver(settingsService: $this->settings),
			logger: $this->logger,
			documentResolver: new PortalSigningDocumentResolver(rootFolder: $this->createMock(IRootFolder::class)),
			throttler: $this->createMock(IThrottler::class)
		);

	}//end controller()

	/**
	 * A valid signer assertion for alice@example.com.
	 *
	 * @return string The compact JWT.
	 */
	private function assertion(): string {
		$now = time();
		$claims = [
			'sub' => '00000000-0000-0000-0000-000000000000', 'audience' => 'signer', 'signerEmail' => 'alice@example.com',
			'trust' => 'substantial', 'jti' => 'jti-0000000000000000000000000000', 'use' => 'assertion',
			'iat' => $now, 'exp' => ($now + 60), 'iss' => 'portaliq',
		];
		$b64 = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
		$hPart = $b64((string)json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
		$cPart = $b64((string)json_encode($claims));

		return $hPart . '.' . $cPart . '.' . $b64(hash_hmac('sha256', $hPart . '.' . $cPart, self::SECRET, true));
	}//end assertion()
}//end class
