<?php

/**
 * Unit tests for SignerIdentityController
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SignerIdentityController;
use OCA\Filinq\Service\SignerAuth\SignerStepUpService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * The step-up endpoints: start answers a challenge or a generic 404; the
 * callback always lands the signer back on the request page.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SignerIdentityControllerTest extends TestCase {

	/**
	 * The step-up service double.
	 *
	 * @var SignerStepUpService&MockObject
	 */
	private SignerStepUpService&MockObject $stepUp;

	/**
	 * The controller with the given request params.
	 *
	 * @param array<string, string> $params The params.
	 *
	 * @return SignerIdentityController
	 */
	private function controller(array $params): SignerIdentityController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(fn (string $key, mixed $default = null): mixed => $params[$key] ?? $default);
		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('linkToRoute')->willReturn('/index.php/apps/filinq/');

		return new SignerIdentityController(
			request: $request,
			stepUp: $this->stepUp,
			urlGenerator: $urls,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end controller()

	/**
	 * A fresh step-up double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->stepUp = $this->createMock(SignerStepUpService::class);

	}//end setUp()

	/**
	 * Start answers the challenge for the signer's own record.
	 *
	 * @return void
	 */
	public function testStartAnswersTheChallenge(): void {
		$challenge = ['provider' => 'oidc-broker', 'type' => 'redirect', 'url' => 'https://broker.example.nl/authorize?x', 'requiredAssurance' => 'substantial'];
		$this->stepUp->expects($this->once())->method('start')->with('req-1', 'signer-1')->willReturn($challenge);

		$response = $this->controller(['signerId' => 'signer-1'])->start('req-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame($challenge, $response->getData());

	}//end testStartAnswersTheChallenge()

	/**
	 * Someone else's record, or no record at all, gets the same generic 404.
	 *
	 * @return void
	 */
	public function testStartIsNoExistenceOracle(): void {
		$this->stepUp->method('start')->willThrowException(new RuntimeException('Not authorized to authenticate as this signer'));

		$response = $this->controller(['signerId' => 'signer-1'])->start('req-1');

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Step-up not available'], $response->getData());

	}//end testStartIsNoExistenceOracle()

	/**
	 * A finished step-up returns the signer to the request, ready to sign.
	 *
	 * @return void
	 */
	public function testTheCallbackReturnsTheSignerToTheRequest(): void {
		$this->stepUp->expects($this->once())->method('authorizeCallback')->with('the-code', 'the-state')
			->willReturn(['requestId' => 'req-1', 'signerId' => 'signer-1', 'assurance' => 'substantial']);

		$response = $this->controller(['code' => 'the-code', 'state' => 'the-state'])->callback();

		$this->assertSame('/index.php/apps/filinq/signing/req-1?stepUp=done&signerId=signer-1', $response->getRedirectURL());

	}//end testTheCallbackReturnsTheSignerToTheRequest()

	/**
	 * A failed step-up says so, and names nothing.
	 *
	 * @return void
	 */
	public function testAFailedCallbackSaysSo(): void {
		$this->stepUp->method('authorizeCallback')->willThrowException(new RuntimeException('ID token failed the nonce check'));

		$response = $this->controller(['code' => 'x', 'state' => 'y'])->callback();

		$this->assertSame('/index.php/apps/filinq/signing-folder?stepUp=failed', $response->getRedirectURL());

	}//end testAFailedCallbackSaysSo()

	/**
	 * Both endpoints are for signed-in users; only the broker's redirect skips the CSRF token.
	 *
	 * @return void
	 */
	public function testTheAuthPostureIsDeclared(): void {
		$start = new ReflectionMethod(SignerIdentityController::class, 'start');
		$callback = new ReflectionMethod(SignerIdentityController::class, 'callback');

		$this->assertCount(1, $start->getAttributes(NoAdminRequired::class));
		$this->assertCount(0, $start->getAttributes(NoCSRFRequired::class));
		$this->assertCount(1, $callback->getAttributes(NoAdminRequired::class));
		$this->assertCount(1, $callback->getAttributes(NoCSRFRequired::class));

	}//end testTheAuthPostureIsDeclared()
}//end class
