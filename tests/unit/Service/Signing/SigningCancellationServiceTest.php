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
use OCA\Filinq\Exception\SigningCancellationRefusedException;
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
	 * The requests the service moved to CANCELLED.
	 *
	 * @var array<int, string>
	 */
	private array $transitioned = [];

	/**
	 * Whether the request's own transition fails.
	 *
	 * @var bool
	 */
	private bool $transitionFails = false;


	/**
	 * Build the service over doubles.
	 *
	 * @param array|null $request What getRequest() returns, or null.
	 * @param object $provider The provider double.
	 *
	 * @return SigningCancellationService The service.
	 */
	private function service(?array $request, object $provider, ?object $named = null): SigningCancellationService {
		$requests = $this->createMock(SigningService::class);
		$requests->method('getRequest')->willReturn($request);
		$requests->method('isValidTransition')->willReturnCallback(
			static fn (string $currentStatus, string $newStatus): bool => in_array($currentStatus, ['DRAFT', 'PENDING', 'IN_PROGRESS'], true) && $newStatus === 'CANCELLED'
		);
		$requests->method('cancelRequest')->willReturnCallback(
			function (string $requestId): array {
				if ($this->transitionFails === true) {
					throw new \RuntimeException('register down');
				}

				$this->transitioned[] = $requestId;
				return ['id' => $requestId, 'status' => 'CANCELLED'];
			}
		);

		$factory = $this->createMock(SigningProviderFactory::class);
		$factory->method('getActiveProvider')->willReturn($provider);
		if ($named !== null) {
			$factory->method('getProvider')->with('libresign')->willReturn($named);
		}

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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testTheCreatorMayCancel(): void {
		$provider = $this->provider();
		$provider->expects($this->once())->method('cancelSigning')->with('ext-1');

		$result = $this->service($this->request(), $provider)->cancel(uid: 'alice', requestId: 'req-1');

		$this->assertSame('cancelled', $result['status']);
		$this->assertFalse($result['alreadyCancelled']);
		$this->assertSame(['req-1'], $this->transitioned, 'the request itself is moved to CANCELLED');
	}//end testTheCreatorMayCancel()

	/**
	 * REQ: no configuration option can permit an administrative override.
	 *
	 * The rule reads no setting at all: none of the service's collaborators is a
	 * configuration source, and nothing under lib/ names a cancellation override,
	 * so there is no switch an administrator could turn to withdraw another
	 * person's request. Adding one fails this test on purpose: an escape hatch
	 * for an absent creator is a separate change with its own authorisation case.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testNoConfigurationOptionCanPermitAnAdministrativeOverride(): void {
		$constructor = (new \ReflectionClass(SigningCancellationService::class))->getConstructor();
		$this->assertNotNull($constructor);

		$configSources = ['OCP\\IAppConfig', 'OCP\\IConfig', 'OCA\\Filinq\\Service\\SettingsService'];
		foreach ($constructor->getParameters() as $parameter) {
			$type = (string)$parameter->getType();
			$this->assertNotContains(ltrim($type, '?'), $configSources, 'the creator rule must not read a setting: ' . $parameter->getName());
		}

		$named = [];
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 4) . '/lib', \FilesystemIterator::SKIP_DOTS));
		foreach ($files as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}

			$code = (string)file_get_contents($file->getPathname());
			if (preg_match('/[\'"][a-z_.-]*(cancel[a-z_.-]*(override|admin)|(override|admin)[a-z_.-]*cancel)[a-z_.-]*[\'"]/i', $code, $match) === 1) {
				$named[] = $file->getFilename() . ': ' . $match[0];
			}
		}

		$this->assertSame([], $named, 'a setting or key that would let an administrator cancel exists');

		// And the rule holds for an administrator whatever the instance says.
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');
		$this->expectException(SigningCancellationRefusedException::class);
		$this->service($this->request(), $provider)->cancel(uid: 'admin', requestId: 'req-1');
	}//end testNoConfigurationOptionCanPermitAnAdministrativeOverride()

	/**
	 * REQ: an app administrator is refused.
	 *
	 * Administering an application is not being a party to an agreement between a
	 * requester and its signatories.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
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
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAlreadyCancelledIsIdempotent(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');

		$result = $this->service($this->request(status: 'CANCELLED'), $provider)
			->cancel(uid: 'alice', requestId: 'req-1');

		$this->assertTrue($result['alreadyCancelled']);
		$this->assertSame([], $this->transitioned);
	}//end testAlreadyCancelledIsIdempotent()

	/**
	 * REQ: a completed request is a refusal, before the provider is contacted.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testACompletedRequestIsRefusedWithoutContactingTheProvider(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');

		try {
			$this->service($this->request(status: 'COMPLETED'), $provider)->cancel(uid: 'alice', requestId: 'req-1');
			$this->fail('A completed request was withdrawn.');
		} catch (SigningCancellationRefusedException $refusal) {
			$this->assertSame(409, $refusal->getCode());
			$this->assertSame('This signing request is completed and can no longer be withdrawn.', $refusal->getMessage());
		}

		$this->assertSame([], $this->transitioned);
	}//end testACompletedRequestIsRefusedWithoutContactingTheProvider()

	/**
	 * A transition that fails after the provider withdrew says so.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAFailedTransitionSaysTheProviderWithdrewIt(): void {
		$this->transitionFails = true;

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('The provider withdrew the signing request, but its status could not be recorded: register down');

		$this->service($this->request(), $this->provider())->cancel(uid: 'alice', requestId: 'req-1');
	}//end testAFailedTransitionSaysTheProviderWithdrewIt()
	/**
	 * A LibreSign request is withdrawn at LibreSign, whatever the active provider is now.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-3.1
	 */
	public function testARequestIsWithdrawnAtTheProviderItNames(): void {
		$active = $this->provider();
		$active->expects($this->never())->method('cancelSigning');
		$libreSign = $this->provider();
		$libreSign->expects($this->once())->method('cancelSigning')->with('ext-1');

		$this->service($this->request() + ['provider' => 'libresign'], $active, $libreSign)->cancel(uid: 'alice', requestId: 'req-1');

	}//end testARequestIsWithdrawnAtTheProviderItNames()

	/**
	 * A native request, as SigningService stores it, has no provider session:
	 * its own transition withdraws it and no provider is asked.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testANativeRequestWithoutAProviderSessionIsWithdrawnByItsTransition(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');
		$request = ['provider' => 'native', 'externalId' => null] + $this->request(status: 'PENDING');

		$result = $this->service($request, $provider)->cancel(uid: 'alice', requestId: 'req-1');

		$this->assertSame('cancelled', $result['status']);
		$this->assertSame(['req-1'], $this->transitioned);
	}//end testANativeRequestWithoutAProviderSessionIsWithdrawnByItsTransition()

	/**
	 * A request at an external provider without that provider's reference is
	 * not marked cancelled: nothing could be withdrawn there.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testAnExternalRequestWithoutAReferenceStaysLive(): void {
		$provider = $this->provider();
		$provider->expects($this->never())->method('cancelSigning');
		$request = ['provider' => 'libresign', 'externalId' => null] + $this->request(status: 'PENDING');

		try {
			$this->service($request, $provider, $provider)->cancel(uid: 'alice', requestId: 'req-1');
			$this->fail('a request without a provider reference was reported withdrawn');
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString('no provider reference', $e->getMessage());
		}

		$this->assertSame([], $this->transitioned);
	}//end testAnExternalRequestWithoutAReferenceStaysLive()
}//end class
