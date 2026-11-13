<?php

/**
 * Unit tests for PublicVerificationController.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-4.1
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-4.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\PublicVerificationController;
use OCA\Filinq\Service\SignatureVerificationLinkService;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * The public verify endpoint: no existence oracle, throttled on unknown tokens, fail-closed.
 *
 * @covers \OCA\Filinq\Controller\PublicVerificationController
 * @uses   \OCA\Filinq\Service\SigningVerificationService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PublicVerificationControllerTest extends TestCase {

	/**
	 * Finds records by token.
	 *
	 * @var SignatureVerificationLinkService&MockObject
	 */
	private SignatureVerificationLinkService&MockObject $links;

	/**
	 * The brute-force throttler.
	 *
	 * @var IThrottler&MockObject
	 */
	private IThrottler&MockObject $throttler;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The controller under test, with a real verifier.
	 *
	 * @var PublicVerificationController
	 */
	private PublicVerificationController $controller;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$request->method('getRemoteAddress')->willReturn('203.0.113.9');
		$this->links = $this->createMock(SignatureVerificationLinkService::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->controller = new PublicVerificationController(
			appName: 'filinq',
			request: $request,
			links: $this->links,
			verifier: new SigningVerificationService(
				rootFolder: $this->createMock(\OCP\Files\IRootFolder::class),
				config: $this->createMock(\OCP\IAppConfig::class)
			),
			throttler: $this->throttler,
			logger: $this->logger,
		);

	}//end setUp()

	/**
	 * A known token answers 200 with the verdict and is not counted as a failed attempt.
	 *
	 * @return void
	 */
	public function testAKnownTokenAnswersItsVerdict(): void {
		$token = str_repeat('ab', 16);
		$this->links->expects($this->once())->method('findByToken')->with($token)->willReturn([
			'fileName' => 'besluit.pdf',
			'fileRef' => '11:signed:aa',
			'contentHash' => str_repeat('c', 64),
			'signatures' => [['status' => 'verified', 'level' => 'SES', 'method' => 'native', 'signerAsserted' => 'Alice', 'integrityVerified' => true, 'identityBound' => true]],
			'revoked' => false,
		]);
		$this->throttler->expects($this->never())->method('registerAttempt');

		$response = $this->controller->verify(token: $token);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('verified', $response->getData()['status']);
		$this->assertSame('besluit.pdf', $response->getData()['fileName']);
		$this->assertArrayNotHasKey('fileRef', $response->getData());

	}//end testAKnownTokenAnswersItsVerdict()

	/**
	 * An unknown token answers 200 with the unknown verdict in the same shape, and is counted.
	 *
	 * @return void
	 */
	public function testAnUnknownTokenIsNotAnOracle(): void {
		$this->links->method('findByToken')->willReturn(null);
		$this->throttler->expects($this->once())->method('registerAttempt')->with(
			PublicVerificationController::THROTTLE_ACTION,
			'203.0.113.9',
			$this->callback(static fn (array $metadata): bool => strlen((string)$metadata['token']) === 16)
		);

		$response = $this->controller->verify(token: str_repeat('ef', 16));

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(SigningVerificationService::unknownVerdict(), $response->getData());

	}//end testAnUnknownTokenIsNotAnOracle()

	/**
	 * A revoked record answers exactly like a token that names nothing.
	 *
	 * @return void
	 */
	public function testARevokedRecordAnswersLikeAnUnknownToken(): void {
		$this->links->method('findByToken')->willReturn(['signatures' => [], 'revoked' => true]);
		$this->throttler->expects($this->once())->method('registerAttempt');

		$this->assertSame(SigningVerificationService::unknownVerdict(), $this->controller->verify(token: str_repeat('ab', 16))->getData());

	}//end testARevokedRecordAnswersLikeAnUnknownToken()

	/**
	 * An unreadable store fails closed to unknown and is logged, with no detail in the answer.
	 *
	 * @return void
	 */
	public function testAnUnreadableStoreFailsClosed(): void {
		$this->links->method('findByToken')->willThrowException(new \RuntimeException('index gone'));
		$this->logger->expects($this->atLeastOnce())->method('warning');

		$response = $this->controller->verify(token: str_repeat('ab', 16));

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(SigningVerificationService::unknownVerdict(), $response->getData());
		$this->assertStringNotContainsString('index gone', (string)json_encode($response->getData()));

	}//end testAnUnreadableStoreFailsClosed()

	/**
	 * A failing throttler does not change the answer.
	 *
	 * @return void
	 */
	public function testAFailingThrottlerDoesNotChangeTheAnswer(): void {
		$this->links->method('findByToken')->willReturn(null);
		$this->throttler->method('registerAttempt')->willThrowException(new \RuntimeException('cache down'));
		$this->logger->expects($this->once())->method('warning');

		$this->assertSame('unknown', $this->controller->verify(token: 'nonsense')->getData()['status']);

	}//end testAFailingThrottlerDoesNotChangeTheAnswer()

	/**
	 * The endpoint is public, CSRF-free, rate-limited and enforces the brute-force action it counts.
	 *
	 * @return void
	 */
	public function testTheEndpointIsPublicAndThrottled(): void {
		$method = new ReflectionMethod(PublicVerificationController::class, 'verify');
		$names = array_map(static fn ($attribute): string => $attribute->getName(), $method->getAttributes());

		$this->assertContains(PublicPage::class, $names);
		$this->assertContains(NoCSRFRequired::class, $names);
		$this->assertContains(AnonRateLimit::class, $names);

		$brute = $method->getAttributes(BruteForceProtection::class);
		$this->assertCount(1, $brute);
		$this->assertSame(PublicVerificationController::THROTTLE_ACTION, $brute[0]->getArguments()['action']);

	}//end testTheEndpointIsPublicAndThrottled()

	/**
	 * The public route is registered, and the app carries no group restriction that would hide it from anonymous visitors.
	 *
	 * @return void
	 */
	public function testThePublicRouteIsReachableWithoutAnAccount(): void {
		$root = dirname(__DIR__, 3);
		$routes = (require $root . '/appinfo/routes.php')['routes'];
		$this->assertContains(
			['name' => 'publicVerification#verify', 'url' => 'api/verify/{token}', 'verb' => 'GET'],
			$routes
		);

		$info = simplexml_load_file($root . '/appinfo/info.xml');
		$this->assertNotFalse($info);
		$this->assertCount(0, $info->xpath('//groups'), 'a group restriction makes public pages unreachable');
		$this->assertCount(0, $info->xpath('//default_enable/groups'));

	}//end testThePublicRouteIsReachableWithoutAnAccount()
}//end class
