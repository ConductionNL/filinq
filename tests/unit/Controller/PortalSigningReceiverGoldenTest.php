<?php

/**
 * Golden tests for the portal signing receiver's guard and answers
 *
 * Pins the allowed and refused outcomes of the shared guard behind sign /
 * decline / viewDocument, the payload handed to the signing service, and
 * every answer of viewDocument, so the controller can be split into named
 * steps without changing what it allows or refuses.
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
use OCA\Filinq\Kernel\Contracts\PortalSigningDocumentResolverInterface;
use OCA\Filinq\Portal\PortalAssertionVerifier;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Allowed and refused outcomes of the portal signing receiver.
 *
 * @covers \OCA\Filinq\Controller\PortalSigningReceiverController
 * @uses   \OCA\Filinq\Portal\PortalAssertionVerifier
 * @uses   \OCA\Filinq\Service\OpenRegisterResolver
 */
class PortalSigningReceiverGoldenTest extends TestCase {

	private const SECRET = 'filinq-receiver-test-secret-01';

	private const SIGNER = ['id' => 'signer-1', 'email' => 'alice@example.com', 'signingRequestId' => 'request-uuid-1', 'status' => 'PENDING'];

	private const FORBIDDEN = ['error' => 'forbidden'];

	/**
	 * The signing service double.
	 *
	 * @var SigningService&MockObject
	 */
	private SigningService&MockObject $signing;

	/**
	 * The settings double.
	 *
	 * @var SettingsService&MockObject
	 */
	private SettingsService&MockObject $settings;

	/**
	 * The object store double.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The throttler double.
	 *
	 * @var IThrottler&MockObject
	 */
	private IThrottler&MockObject $throttler;

	/**
	 * The document resolver double.
	 *
	 * @var PortalSigningDocumentResolverInterface&MockObject
	 */
	private PortalSigningDocumentResolverInterface&MockObject $documents;

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
		$this->throttler = $this->createMock(IThrottler::class);
		$this->documents = $this->createMock(PortalSigningDocumentResolverInterface::class);
		$this->settings->method('getObjectService')->willReturn($this->objects);
		$this->settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);

	}//end setUp()

	/**
	 * Claims that fail the audience, trust or signer email check are refused before any lookup,
	 * and each refusal is counted once.
	 *
	 * @return void
	 */
	public function testClaimsThatDoNotAuthoriseASignerAreRefusedAndCounted(): void {
		$refused = [
			['audience' => 'citizen'],
			['audience' => null],
			['trust' => 'low'],
			['trust' => 'extreme'],
			['trust' => null],
			['signerEmail' => ''],
			['signerEmail' => 42],
			['signerEmail' => null],
		];
		foreach ($refused as $override) {
			$this->setUp();
			$this->objects->expects($this->never())->method('findAll');
			$this->throttler->expects($this->once())->method('registerAttempt');

			$response = $this->controller(claims: $override, params: ['signingRequestId' => 'request-uuid-1'])->signDocument();

			$this->assertSame([403, self::FORBIDDEN], [$response->getStatus(), $response->getData()], (string)json_encode($override));
		}

	}//end testClaimsThatDoNotAuthoriseASignerAreRefusedAndCounted()

	/**
	 * A malformed or missing target is refused before the lookup; a bad assertion answers 401.
	 *
	 * @return void
	 */
	public function testABadTargetOrAssertionIsRefused(): void {
		foreach ([[], ['signingRequestId' => ''], ['signingRequestId' => 'a/b'], ['signingRequestId' => ['x']]] as $params) {
			$this->setUp();
			$this->objects->expects($this->never())->method('findAll');
			$this->throttler->expects($this->once())->method('registerAttempt');

			$this->assertSame(403, $this->controller(claims: [], params: $params)->viewDocument()->getStatus());
		}

		$this->setUp();
		$this->throttler->expects($this->once())->method('registerAttempt');
		$response = $this->controller(claims: ['exp' => (time() - 10)], params: ['signingRequestId' => 'request-uuid-1'])->declineDocument();
		$this->assertSame([401, ['error' => 'unauthorized']], [$response->getStatus(), $response->getData()]);

	}//end testABadTargetOrAssertionIsRefused()

	/**
	 * A high trust signer is allowed and acts with the verified actor built from the claims only.
	 *
	 * @return void
	 */
	public function testAnAllowedSignerActsAsTheVerifiedActor(): void {
		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->throttler->expects($this->never())->method('registerAttempt');
		$actor = [
			'email' => 'alice@example.com',
			'subjectRef' => 'subject-1',
			'identityRef' => 'subject-1',
			'trust' => 'high',
			'jti' => '',
		];
		$this->signing->expects($this->once())->method('sign')
			->with('request-uuid-1', 'signer-1', $actor, ['consent' => true, 'signature' => 'drawn'])
			->willReturn([]);

		$claims = ['trust' => 'high', 'sub' => 'subject-1', 'jti' => null];
		$params = ['signingRequestId' => 'request-uuid-1', 'consent' => '1', 'signature' => 'drawn'];
		$response = $this->controller(claims: $claims, params: $params)->signDocument();

		$this->assertSame([200, ['status' => 'signed', 'assurance' => 'SES']], [$response->getStatus(), $response->getData()]);

	}//end testAnAllowedSignerActsAsTheVerifiedActor()

	/**
	 * An empty or missing drawn signature is left out; the signer id falls back to the uuid.
	 *
	 * @return void
	 */
	public function testTheSignaturePayloadAndSignerId(): void {
		$row = ['uuid' => 'signer-uuid', 'email' => 'alice@example.com', 'signingRequestId' => 'request-uuid-1'];
		$this->objects->method('findAll')->willReturn([$row]);
		$this->signing->expects($this->once())->method('sign')
			->with('request-uuid-1', 'signer-uuid', $this->anything(), ['consent' => false])
			->willReturn(['status' => 'PENDING', 'signatureAssurance' => 'AES']);

		$response = $this->controller(claims: [], params: ['signingRequestId' => 'request-uuid-1', 'signature' => ''])->signDocument();

		$this->assertSame(['status' => 'PENDING', 'assurance' => 'AES'], $response->getData());

	}//end testTheSignaturePayloadAndSignerId()

	/**
	 * A failing sign answers 502 and logs; a refusal answers 403 and is not counted.
	 *
	 * @return void
	 */
	public function testSignFailuresAndRefusals(): void {
		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->signing->method('sign')->willThrowException(new RuntimeException('down', 500));
		$this->logger->expects($this->once())->method('error')->with('Filinq: portal signing receiver signDocument failed: down');
		$response = $this->controller(claims: [], params: ['signingRequestId' => 'request-uuid-1'])->signDocument();
		$this->assertSame([502, ['error' => 'downstream_failure']], [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->signing->method('sign')->willThrowException(new RuntimeException('guardian', 403));
		$this->throttler->expects($this->never())->method('registerAttempt');
		$this->logger->expects($this->once())->method('info')->with('Filinq: portal signing refused: guardian');
		$response = $this->controller(claims: [], params: ['signingRequestId' => 'request-uuid-1'])->signDocument();
		$this->assertSame([403, ['error' => 'signing_refused']], [$response->getStatus(), $response->getData()]);

	}//end testSignFailuresAndRefusals()

	/**
	 * Rows that serialise to the invited signer are accepted; unreadable objects are skipped.
	 *
	 * @return void
	 */
	public function testSerialisableRowsAreReadAndOthersSkipped(): void {
		$unreadable = new class {
			/**
			 * Not an array.
			 *
			 * @return string
			 */
			public function jsonSerialize(): string {
				return 'x';
			}
		};
		$entity = new class implements \JsonSerializable {
			/**
			 * The invited signer.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['id' => 'entity-signer', 'email' => 'ALICE@example.com', 'signingRequestId' => 'request-uuid-1'];
			}
		};
		$this->objects->method('findAll')->willReturn([new \stdClass(), $unreadable, 7, $entity]);
		$this->signing->expects($this->once())->method('sign')->with('request-uuid-1', 'entity-signer')->willReturn([]);

		$this->assertSame(200, $this->controller(claims: [], params: ['signingRequestId' => 'request-uuid-1'])->signDocument()->getStatus());

	}//end testSerialisableRowsAreReadAndOthersSkipped()

	/**
	 * No object store refuses the act without logging a lookup failure.
	 *
	 * @return void
	 */
	public function testNoObjectStoreRefuses(): void {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn(null);
		$this->settings = $settings;
		$this->logger->expects($this->never())->method('debug');
		$this->throttler->expects($this->once())->method('registerAttempt');

		$this->assertSame(403, $this->controller(claims: [], params: ['signingRequestId' => 'request-uuid-1'])->declineDocument()->getStatus());

	}//end testNoObjectStoreRefuses()

	/**
	 * Every answer of viewDocument after the guard passed.
	 *
	 * @return void
	 */
	public function testViewDocumentAnswers(): void {
		$params = ['signingRequestId' => 'request-uuid-1'];

		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->signing->method('getRequest')->willThrowException(new RuntimeException('gone'));
		$this->logger->expects($this->once())->method('error')->with('Filinq: portal signing receiver viewDocument failed: gone');
		$this->assertSame(502, $this->controller(claims: [], params: $params)->viewDocument()->getStatus());

		$this->setUp();
		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->signing->method('getRequest')->with('request-uuid-1')->willReturn(['documentFileId' => 1]);
		$this->documents->method('resolve')->willReturn(null);
		$response = $this->controller(claims: [], params: $params)->viewDocument();
		$this->assertSame([404, ['error' => 'document_unavailable']], [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->signing->method('getRequest')->willReturn(['documentFileId' => 1]);
		$broken = $this->createMock(File::class);
		$broken->method('getContent')->willThrowException(new RuntimeException('unreadable'));
		$this->documents->method('resolve')->willReturn($broken);
		$this->logger->expects($this->once())->method('error')->with('Filinq: portal signing receiver viewDocument failed: unreadable');
		$this->assertSame(502, $this->controller(claims: [], params: $params)->viewDocument()->getStatus());

		$this->setUp();
		$this->objects->method('findAll')->willReturn([self::SIGNER]);
		$this->signing->method('getRequest')->willReturn(['documentFileId' => 1]);
		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn('body');
		$file->method('getName')->willReturn('file.pdf');
		$file->method('getMimeType')->willReturn('application/pdf');
		$this->documents->method('resolve')->willReturn($file);
		$response = $this->controller(claims: [], params: $params)->viewDocument();
		$expected = ['documentName' => 'file.pdf', 'mimeType' => 'application/pdf', 'contentBase64' => base64_encode('body')];
		$this->assertSame([200, $expected], [$response->getStatus(), $response->getData()]);

	}//end testViewDocumentAnswers()

	/**
	 * The controller over a request carrying a signer assertion with $claims overridden, and $params.
	 *
	 * @param array<string, mixed> $claims Claim overrides; null removes a claim.
	 * @param array<string, mixed> $params The request parameters.
	 *
	 * @return PortalSigningReceiverController The controller.
	 */
	private function controller(array $claims, array $params): PortalSigningReceiverController {
		$assertion = $this->assertion(overrides: $claims);
		$request = $this->createMock(IRequest::class);
		$headers = [PortalAssertionVerifier::HEADER => $assertion];
		$request->method('getHeader')->willReturnCallback(static fn (string $name): string => ($headers[$name] ?? ''));
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => ($params[$key] ?? $default)
		);

		return new PortalSigningReceiverController(
			appName: 'filinq',
			request: $request,
			verifier: new PortalAssertionVerifier(secretOverride: self::SECRET),
			signingService: $this->signing,
			settingsService: $this->settings,
			registerResolver: new OpenRegisterResolver(settingsService: $this->settings),
			logger: $this->logger,
			documentResolver: $this->documents,
			throttler: $this->throttler
		);

	}//end controller()

	/**
	 * A signer assertion for alice@example.com with the given claim overrides.
	 *
	 * @param array<string, mixed> $overrides Claim overrides; null removes a claim.
	 *
	 * @return string The compact JWT.
	 */
	private function assertion(array $overrides): string {
		$now = time();
		$claims = [
			'sub' => '00000000-0000-0000-0000-000000000000', 'audience' => 'signer', 'signerEmail' => 'alice@example.com',
			'trust' => 'substantial', 'jti' => 'jti-0000000000000000000000000000', 'use' => 'assertion',
			'iat' => $now, 'exp' => ($now + 60), 'iss' => 'portaliq',
		];
		$claims = array_filter(array_merge($claims, $overrides), static fn (mixed $value): bool => $value !== null);
		$b64 = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
		$hPart = $b64((string)json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
		$cPart = $b64((string)json_encode($claims));

		return $hPart . '.' . $cPart . '.' . $b64(hash_hmac('sha256', $hPart . '.' . $cPart, self::SECRET, true));
	}//end assertion()
}//end class
