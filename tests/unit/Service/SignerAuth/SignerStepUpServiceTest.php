<?php

/**
 * Unit tests for SignerStepUpService
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
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

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

require_once __DIR__ . '/AssuranceGateHarness.php';

use OCA\Filinq\Service\SignerAuth\SignerStepUpService;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Start goes through the signing ownership check; finish keeps the evidence
 * for the act the state was bound to, in the session that started it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SignerStepUpServiceTest extends TestCase {
	use AssuranceGateHarness;

	/**
	 * The ownership check double.
	 *
	 * @var SigningActorResolver&MockObject
	 */
	private SigningActorResolver&MockObject $actors;

	/**
	 * The signing service double.
	 *
	 * @var SigningService&MockObject
	 */
	private SigningService&MockObject $signing;

	/**
	 * The session user's uid.
	 *
	 * @var string
	 */
	private string $uid = 'alice';

	/**
	 * A configured broker as the step-up provider.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->configureBroker();
		$this->appConfig['signer_auth_provider'] = 'oidc-broker';
		$this->actors = $this->createMock(SigningActorResolver::class);
		$this->actors->method('resolveActingIdentity')->willReturnCallback(fn (): array => [$this->uid, 'Alice']);
		$this->signing = $this->createMock(SigningService::class);
		$this->signing->method('getRequest')->willReturn(['id' => 'req-1', 'requiredAssurance' => 'low', 'guardianRequiredAssurance' => 'substantial']);

	}//end setUp()

	/**
	 * The service over the harness.
	 *
	 * @return SignerStepUpService
	 */
	private function service(): SignerStepUpService {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturnCallback(fn (): string => $this->uid);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$broker = $this->broker();

		return new SignerStepUpService(
			providers: $this->providerFactory(userSession: $session),
			broker: $broker,
			gate: $this->assuranceGate(userSession: $session),
			store: $this->evidenceStore(),
			actors: $this->actors,
			signing: $this->signing
		);

	}//end service()

	/**
	 * A guardian starts a broker step-up at the guardian's assurance.
	 *
	 * @return void
	 */
	public function testAGuardianStartsAtTheGuardiansAssurance(): void {
		$this->actors->expects($this->once())->method('loadAuthorisedSigner')
			->with('req-1', 'signer-9', null, 'alice', 'authenticate')
			->willReturn(['id' => 'signer-9', 'role' => 'guardian', 'signingRequestId' => 'req-1']);

		$challenge = $this->service()->start(requestId: 'req-1', signerId: 'signer-9');

		$this->assertSame('substantial', $challenge['requiredAssurance']);
		$this->assertSame('redirect', $challenge['type']);
		$this->assertStringContainsString('acr_values=', $challenge['url']);

	}//end testAGuardianStartsAtTheGuardiansAssurance()

	/**
	 * Someone who is not the signer cannot start a step-up for them.
	 *
	 * @return void
	 */
	public function testANonSignerCannotStart(): void {
		$this->actors->method('loadAuthorisedSigner')->willThrowException(new RuntimeException('Not authorized to authenticate as this signer'));

		$this->expectException(RuntimeException::class);

		$this->service()->start(requestId: 'req-1', signerId: 'signer-9');

	}//end testANonSignerCannotStart()

	/**
	 * Finishing keeps the evidence for the bound act, and only there.
	 *
	 * @return void
	 */
	public function testFinishKeepsTheEvidenceForTheBoundAct(): void {
		$this->actors->method('loadAuthorisedSigner')->willReturn(['id' => 'signer-9', 'signingRequestId' => 'req-1']);
		$service = $this->service();
		$challenge = $service->start(requestId: 'req-1', signerId: 'signer-9');
		$this->nextClaims = $this->claimsFor(nonce: $this->queryParam(url: $challenge['url'], name: 'nonce'), acr: 'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard');

		$act = $service->finish(code: 'c', state: $this->queryParam(url: $challenge['url'], name: 'state'));

		$this->assertSame(['requestId' => 'req-1', 'signerId' => 'signer-9', 'assurance' => 'substantial'], $act);
		$this->assertSame('substantial', $this->evidenceStore()->get(requestId: 'req-1', signerId: 'signer-9')?->assurance);
		$this->assertNull($this->evidenceStore()->get(requestId: 'req-1', signerId: 'signer-1'));

	}//end testFinishKeepsTheEvidenceForTheBoundAct()

	/**
	 * A callback landing in another user's session keeps nothing.
	 *
	 * @return void
	 */
	public function testFinishInAnotherUsersSessionKeepsNothing(): void {
		$this->actors->method('loadAuthorisedSigner')->willReturn(['id' => 'signer-9', 'signingRequestId' => 'req-1']);
		$service = $this->service();
		$challenge = $service->start(requestId: 'req-1', signerId: 'signer-9');
		$this->uid = 'mallory';

		try {
			$service->finish(code: 'c', state: $this->queryParam(url: $challenge['url'], name: 'state'));
			$this->fail('Another user must not finish this step-up');
		} catch (RuntimeException) {
			$this->assertNull($this->evidenceStore()->get(requestId: 'req-1', signerId: 'signer-9'));
		}

	}//end testFinishInAnotherUsersSessionKeepsNothing()

	/**
	 * An unknown state is refused.
	 *
	 * @return void
	 */
	public function testAnUnknownStateIsRefused(): void {
		$this->expectException(RuntimeException::class);

		$this->service()->finish(code: 'c', state: 'never-issued');

	}//end testAnUnknownStateIsRefused()
}//end class
