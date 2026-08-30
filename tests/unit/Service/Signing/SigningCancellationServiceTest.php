<?php

/**
 * Unit tests for SigningCancellationService.
 *
 * openspec/changes/signing-cancellation.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Exception\SigningCancellationNotSupportedException;
use OCA\Filinq\Service\Signing\SigningCancellationService;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\Filinq\Service\SigningService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Creator-only cancellation, decided 2026-08-16.
 */
class SigningCancellationServiceTest extends TestCase {

	/**
	 * Build the service over doubles.
	 *
	 * @param array|null $request What getRequest() returns, or null.
	 * @param object $provider The provider double.
	 *
	 * @return SigningCancellationService The service.
	 */
	private function service(?array $request, object $provider): SigningCancellationService {
		$requests = $this->createMock(SigningService::class);
		$requests->method('getRequest')->willReturn($request);

		$factory = $this->createMock(SigningProviderFactory::class);
		$factory->method('getActiveProvider')->willReturn($provider);

		return new SigningCancellationService(
			providers: $factory,
			requests: $requests,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end service()

	/**
	 * A provider double that records whether it was called.
	 *
	 * @param bool $throwUnsupported Whether it refuses as unsupported.
	 *
	 * @return SigningProviderInterface The double.
	 */
	private function provider(bool $throwUnsupported = false): SigningProviderInterface {
		$provider = $this->createMock(SigningProviderInterface::class);
		if ($throwUnsupported === true) {
			$provider->method('cancelSigning')
				->willThrowException(new SigningCancellationNotSupportedException('ValidSign'));
		}

		return $provider;
	}//end provider()

	/**
	 * A signing request fixture.
	 *
	 * @param string $creator The initiating user id.
	 * @param string $status The request status.
	 *
	 * @return array The request.
	 */
	private function request(string $creator = 'alice', string $status = 'pending'): array {
		return [
			'id' => 'req-1',
			'initiatorUserId' => $creator,
			'externalId' => 'ext-1',
			'status' => $status,
			'signerIds' => ['bob'],
		];
	}//end request()

	/**
	 * REQ: the creator may cancel.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testTheCreatorMayCancel(): void {
		$provider = $this->provider();
		$provider->expects($this->once())->method('cancelSigning')->with('ext-1');

		$result = $this->service($this->request(), $provider)->cancel(uid: 'alice', requestId: 'req-1');

		$this->assertSame('cancelled', $result['status']);
	}//end testTheCreatorMayCancel()

	/**
	 * REQ: an app administrator is refused.
	 *
	 * Administering an application is not being a party to an agreement between a
	 * requester and its signatories.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAnAdministratorIsRefused(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Only the person who created.*alice.*Administrators cannot/s');

		$this->service($this->request(), $provider)->cancel(uid: 'root', requestId: 'req-1');
	}//end testAnAdministratorIsRefused()

	/**
	 * REQ: a SIGNER is refused.
	 *
	 * `getRequest()` admits signers, so without an explicit creator check a
	 * signatory could withdraw the very request they were asked to sign.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testASignerIsRefused(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');

		$this->expectException(RuntimeException::class);

		$this->service($this->request(), $provider)->cancel(uid: 'bob', requestId: 'req-1');
	}//end testASignerIsRefused()

	/**
	 * REQ: the provider is NOT contacted when the actor is refused.
	 *
	 * Otherwise an unauthorised call could produce a partial cancellation at the
	 * provider while the app reports a refusal.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testTheProviderIsNotContactedOnRefusal(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');

		try {
			$this->service($this->request(), $provider)->cancel(uid: 'mallory', requestId: 'req-1');
			$this->fail('must refuse');
		} catch (RuntimeException $e) {
			$this->addToAssertionCount(1);
		}
	}//end testTheProviderIsNotContactedOnRefusal()

	/**
	 * REQ: an unknown request and an unauthorised one are indistinguishable.
	 *
	 * Distinguishing them lets an unauthorised caller enumerate valid request ids
	 * from the error text.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testUnknownAndUnauthorisedAreIndistinguishable(): void {
		$unknown = null;
		$notMine = $this->request(creator: 'alice');

		$messages = [];
		foreach ([$unknown, $notMine] as $fixture) {
			try {
				$this->service($fixture, $this->provider())->cancel(uid: 'mallory', requestId: 'req-1');
				$this->fail('must refuse');
			} catch (RuntimeException $e) {
				$messages[] = preg_replace('/: [a-z]+\.$/', '.', $e->getMessage());
			}
		}

		$this->assertStringContainsString('Only the person who created', $messages[0]);
		$this->assertStringContainsString('Only the person who created', $messages[1]);
	}//end testUnknownAndUnauthorisedAreIndistinguishable()

	/**
	 * REQ: a provider that cannot cancel surfaces as unsupported, not as success.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAnUnsupportedProviderSurfacesAsUnsupported(): void {
		$this->expectException(SigningCancellationNotSupportedException::class);
		$this->expectExceptionMessageMatches('/still live/');

		$this->service($this->request(), $this->provider(throwUnsupported: true))
			->cancel(uid: 'alice', requestId: 'req-1');
	}//end testAnUnsupportedProviderSurfacesAsUnsupported()

	/**
	 * REQ: a provider failure says the request is still live.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAProviderFailureSaysTheRequestIsStillLive(): void {
		$provider = $this->createMock(SigningProviderInterface::class);
		$provider->method('cancelSigning')->willThrowException(new RuntimeException('gateway timeout'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/could not be withdrawn.*still live.*can still sign/s');

		$this->service($this->request(), $provider)->cancel(uid: 'alice', requestId: 'req-1');
	}//end testAProviderFailureSaysTheRequestIsStillLive()

	/**
	 * REQ: an already-cancelled request is idempotent, and says so.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAlreadyCancelledIsIdempotent(): void {
		$result = $this->service($this->request(status: 'cancelled'), $this->provider())
			->cancel(uid: 'alice', requestId: 'req-1');

		$this->assertTrue($result['alreadyCancelled']);
	}//end testAlreadyCancelledIsIdempotent()
}//end class
