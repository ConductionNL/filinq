<?php

/**
 * Unit tests for the guardian consent rules on the signing path
 *
 * Drives the real SigningService, actor resolver, artifact producer and
 * guardian consent guard over an in-memory object store, so the minor's act,
 * the guardian's act and the completion run the way they do in production.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Event\SigningConcludedEventFactory;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Tests for the guardian consent rules as SigningService applies them.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningServiceGuardianConsentTest extends TestCase {

	/**
	 * Objects by id: requests and signer records alike.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $store = [];

	/**
	 * Every object handed to saveObject(), in order.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $saves = [];

	/**
	 * The uid of the Nextcloud user in session.
	 *
	 * @var string
	 */
	private string $sessionUid = 'sanne';

	/**
	 * The context the signing provider was asked to sign with, or null.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $providerContext = null;

	/**
	 * Audit metadata per action, in the order it was logged.
	 *
	 * @var list<array{action: string, metadata: array<string, mixed>}>
	 */
	private array $audit = [];

	/**
	 * @var SigningProviderInterface|MockObject
	 */
	private SigningProviderInterface|MockObject $provider;

	/**
	 * @var SigningService
	 */
	private SigningService $service;

	/**
	 * Build the service over an in-memory store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['find', 'saveObject'])
			->getMock();
		$objectService->method('find')->willReturnCallback(
			function (string $id = ''): ?array {
				return ($this->store[$id] ?? null);
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (array $object = []): array {
				if (isset($object['id']) === false) {
					$object['id'] = 'obj-' . (count($this->store) + 1);
				}

				$this->store[(string)$object['id']] = $object;
				$this->saves[] = $object;

				return $object;
			}
		);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objectService);
		$settings->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settings->method('resolveSignerRecordBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);
		$settings->method('getFeatureToggles')->willReturn(
			[
				'signing_request_expiry_days' => 30,
				'signing_default_level' => 'SES',
				'signing_provider' => 'native',
			]
		);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				$map = [
					'signerRecord_register' => 'filinq',
					'signerRecord_schema' => 'signerRecord',
				];
				return ($map[$key] ?? $default);
			}
		);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturnCallback(
			function (): IUser {
				$user = $this->createMock(IUser::class);
				$user->method('getUID')->willReturn($this->sessionUid);
				$user->method('getDisplayName')->willReturn(ucfirst($this->sessionUid));
				return $user;
			}
		);

		$request = $this->createMock(IRequest::class);
		$request->method('getRemoteAddress')->willReturn('192.0.2.10');

		$this->provider = $this->createMock(SigningProviderInterface::class);
		$this->provider->method('supportsLevel')->willReturn(true);
		$this->provider->method('produceSignedArtifact')->willReturnCallback(
			function (string $documentContent, array $context): string {
				$this->providerContext = $context;
				return 'SIGNED-BYTES';
			}
		);
		$providerFactory = $this->createMock(SigningProviderFactory::class);
		$providerFactory->method('getProvider')->willReturn($this->provider);

		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn('original-bytes');
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($folder);

		$auditService = $this->createMock(SigningAuditService::class);
		$auditService->method('logEvent')->willReturnCallback(
			function (
				string $signingRequestId,
				string $action,
				string $actorUserId = '',
				string $actorDisplayName = '',
				string $ipAddress = '',
				string $signatureLevel = '',
				string $provider = '',
				array $metadata = [],
			): array {
				$this->audit[] = ['action' => $action, 'metadata' => $metadata];
				return [];
			}
		);

		$this->service = new SigningService(
			settingsService: $settings,
			auditService: $auditService,
			artifactProducer: new SignedArtifactProducer(
				providerFactory: $providerFactory,
				userSession: $userSession,
				request: $request,
				rootFolder: $rootFolder,
				finalDocuments: $this->createMock(FinalDocumentService::class)
			),
			validator: new SigningRequestValidator(providerFactory: $providerFactory),
			actorResolver: new SigningActorResolver(
				settingsService: $settings,
				config: $config,
				userSession: $userSession,
				request: $request
			),
			emitter: new SigningConclusionEmitter(
				eventDispatcher: $this->createMock(IEventDispatcher::class),
				logger: $this->createMock(LoggerInterface::class),
				eventFactory: new SigningConcludedEventFactory()
			),
			consentGuard: new GuardianConsentGuard(settingsService: $settings)
		);

	}//end setUp()

	/**
	 * A birth date the given number of years before today.
	 *
	 * @param int $years How old the signer is today.
	 *
	 * @return string
	 */
	private function bornYearsAgo(int $years): string {
		return (new DateTimeImmutable('today'))->modify('-' . $years . ' years')->modify('-10 days')->format('Y-m-d');

	}//end bornYearsAgo()

	/**
	 * Store a signing request with the given signer records.
	 *
	 * @param list<array<string, mixed>> $signers Signer records, each with an `id`.
	 * @param string $status The request status.
	 *
	 * @return void
	 */
	private function seed(array $signers, string $status = 'PENDING'): void {
		$signerIds = [];
		foreach ($signers as $signer) {
			$signer['signingRequestId'] = 'req-1';
			$this->store[(string)$signer['id']] = $signer;
			$signerIds[] = (string)$signer['id'];
		}

		$this->store['req-1'] = [
			'id' => 'req-1',
			'documentFileId' => '42',
			'documentName' => 'opp-sanne.pdf',
			'initiatorUserId' => 'coordinator',
			'status' => $status,
			'signatureLevel' => 'SES',
			'provider' => 'native',
			'guardianConsentAge' => 16,
			'signerIds' => $signerIds,
		];

	}//end seed()

	/**
	 * The learner's signer record.
	 *
	 * @param int $age How old the learner is today.
	 * @param array<string, mixed> $extra Fields to add or override.
	 *
	 * @return array<string, mixed>
	 */
	private function learner(int $age, array $extra = []): array {
		return array_merge(
			[
				'id' => 'learner-1',
				'userId' => 'sanne',
				'displayName' => 'Sanne de Vries',
				'status' => 'PENDING',
				'role' => 'signer',
				'birthDate' => $this->bornYearsAgo(years: $age),
			],
			$extra
		);

	}//end learner()

	/**
	 * The parent's guardian signer record.
	 *
	 * @param array<string, mixed> $extra Fields to add or override.
	 *
	 * @return array<string, mixed>
	 */
	private function parent(array $extra = []): array {
		return array_merge(
			[
				'id' => 'guardian-1',
				'userId' => 'mark',
				'displayName' => 'Mark de Vries',
				'status' => 'PENDING',
				'role' => 'guardian',
				'guardianForSignerId' => 'learner-1',
				'guardianAct' => 'co-sign',
				'guardianRef' => 'learniq/guardian/0001',
			],
			$extra
		);

	}//end parent()

	/**
	 * A 14-year-old cannot sign an OPP that names no guardian, and nothing changes.
	 *
	 * @return void
	 */
	public function testAMinorCannotSignARequestThatNamesNoGuardian(): void {
		$this->seed(signers: [$this->learner(age: 14)]);
		$before = $this->store;

		try {
			$this->service->sign(requestId: 'req-1', signerId: 'learner-1');
			$this->fail('A minor without a guardian must not be able to sign.');
		} catch (Throwable $e) {
			$this->assertSame(403, $e->getCode(), 'The refusal must carry a 403: ' . $e->getMessage());
		}

		$this->assertSame([], $this->saves, 'A refused act must not write anything.');
		$this->assertSame($before, $this->store);
		$this->assertSame([], $this->audit, 'A refused act must not be audited as SIGNED.');

	}//end testAMinorCannotSignARequestThatNamesNoGuardian()

	/**
	 * The parent co-signs, and the completed request and artifact record both signers.
	 *
	 * @return void
	 */
	public function testTheParentCoSignsAndTheRecordNamesBothSigners(): void {
		$this->seed(signers: [$this->learner(age: 14), $this->parent()]);

		$this->sessionUid = 'sanne';
		$this->service->sign(requestId: 'req-1', signerId: 'learner-1');
		$this->assertSame('IN_PROGRESS', $this->store['req-1']['status']);
		$this->assertNull($this->providerContext, 'Nothing may be signed before the guardian acted.');

		$this->sessionUid = 'mark';
		$this->service->sign(requestId: 'req-1', signerId: 'guardian-1');

		$request = $this->store['req-1'];
		$this->assertSame('COMPLETED', $request['status']);
		$this->assertCount(1, $request['consentBasis']);
		$entry = $request['consentBasis'][0];
		$this->assertSame('learner-1', $entry['signerId']);
		$this->assertSame('guardian-co-signature', $entry['basis']);
		$this->assertSame(16, $entry['guardianConsentAge']);
		$this->assertSame('guardian-1', $entry['guardians'][0]['signerId']);
		$this->assertSame('nextcloud-session', $entry['guardians'][0]['identity']['provider']);
		$this->assertStringNotContainsString(
			$this->store['learner-1']['birthDate'],
			(string)json_encode($request['consentBasis']),
			'The consent basis must not carry the birth date.'
		);

		$this->assertSame(
			$request['consentBasis'],
			$this->providerContext['consentBasis'] ?? null,
			'The artifact must be signed with the same consent basis.'
		);
		$this->assertSame(['learner-1', 'guardian-1'], $this->providerContext['signers']);

		$this->assertSame('nextcloud-session', $this->store['learner-1']['actingIdentity']['provider']);
		$this->assertSame('nextcloud-session', $this->store['guardian-1']['actingIdentity']['provider']);

		$guardianAudit = $this->audit[1]['metadata']['guardianConsent'] ?? null;
		$this->assertSame('SIGNED', $this->audit[1]['action']);
		$this->assertSame('learner-1', $guardianAudit['guardianForSignerId'] ?? null);
		$this->assertTrue($this->audit[0]['metadata']['guardianConsent']['required'] ?? false);

	}//end testTheParentCoSignsAndTheRecordNamesBothSigners()

	/**
	 * No artifact is produced for a minor who signed without a guardian who acted.
	 *
	 * @return void
	 */
	public function testNoArtifactWithoutAGuardianWhoActed(): void {
		$this->seed(
			signers: [
				$this->learner(age: 14, extra: ['status' => 'SIGNED', 'signedAt' => (new DateTimeImmutable())->format(DATE_ATOM)]),
				['id' => 'mentor-1', 'userId' => 'coordinator', 'displayName' => 'Coordinator', 'status' => 'PENDING'],
			],
			status: 'IN_PROGRESS'
		);

		$this->sessionUid = 'coordinator';
		try {
			$this->service->sign(requestId: 'req-1', signerId: 'mentor-1');
			$this->fail('Completion must be refused while no guardian acted.');
		} catch (Throwable $e) {
			$this->assertStringContainsString('guardian', $e->getMessage());
		}

		$this->assertNull($this->providerContext, 'No artifact may be produced.');
		$this->assertNotSame('COMPLETED', $this->store['req-1']['status']);
		$this->assertArrayNotHasKey('signedDocumentRef', $this->store['req-1']);

	}//end testNoArtifactWithoutAGuardianWhoActed()

	/**
	 * A request between adults completes exactly as before.
	 *
	 * @return void
	 */
	public function testARequestBetweenAdultsCarriesNoConsentBasis(): void {
		$this->seed(signers: [['id' => 'alice-1', 'userId' => 'alice', 'displayName' => 'Alice', 'status' => 'PENDING']]);

		$this->sessionUid = 'alice';
		$this->service->sign(requestId: 'req-1', signerId: 'alice-1');

		$this->assertSame('COMPLETED', $this->store['req-1']['status']);
		$this->assertArrayNotHasKey('consentBasis', $this->store['req-1']);
		$this->assertArrayNotHasKey('consentBasis', $this->providerContext ?? []);
		$this->assertArrayNotHasKey('actingIdentity', $this->store['alice-1']);

	}//end testARequestBetweenAdultsCarriesNoConsentBasis()

	/**
	 * A delegated OPP request stores the guardian link, the hidden birth date and the applied age.
	 *
	 * @return void
	 */
	public function testCreationStoresTheGuardianLinkAndTheAppliedAge(): void {
		$birthDate = $this->bornYearsAgo(years: 14);

		$created = $this->service->createRequest(
			data: [
				'documentFileId' => '42',
				'documentName' => 'opp-sanne.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'parallel',
				'sourceApp' => 'learniq',
				'subjectSchema' => 'LearningPlan',
				'signers' => [
					['userId' => 'mark', 'displayName' => 'Mark de Vries', 'role' => 'guardian', 'guardianFor' => 'sanne', 'guardianRef' => 'learniq/guardian/0001'],
					['userId' => 'sanne', 'displayName' => 'Sanne de Vries', 'birthDate' => $birthDate],
				],
			]
		);

		$this->assertSame(16, $created['guardianConsentAge']);
		$this->assertCount(2, $created['signerIds']);
		[$guardianId, $learnerId] = $created['signerIds'];

		$guardian = $this->store[$guardianId];
		$this->assertSame('mark', $guardian['userId'], 'signerIds must keep the order of the entries.');
		$this->assertSame('guardian', $guardian['role']);
		$this->assertSame($learnerId, $guardian['guardianForSignerId']);
		$this->assertSame('co-sign', $guardian['guardianAct']);
		$this->assertSame('learniq/guardian/0001', $guardian['guardianRef']);
		$this->assertArrayNotHasKey('guardianFor', $guardian, 'The raw entry key is resolved, not stored.');

		$learner = $this->store[$learnerId];
		$this->assertSame('sanne', $learner['userId']);
		$this->assertSame($birthDate, $learner['birthDate']);

	}//end testCreationStoresTheGuardianLinkAndTheAppliedAge()

	/**
	 * A request that names a minor without a guardian is refused before anything is written.
	 *
	 * @return void
	 */
	public function testCreationRefusesAMinorWithoutAGuardianBeforeWritingAnything(): void {
		try {
			$this->service->createRequest(
				data: [
					'documentFileId' => '42',
					'documentName' => 'opp-sanne.pdf',
					'signatureLevel' => 'SES',
					'signingMode' => 'parallel',
					'signers' => [
						['userId' => 'sanne', 'displayName' => 'Sanne de Vries', 'birthDate' => $this->bornYearsAgo(years: 14)],
					],
				]
			);
			$this->fail('A minor without a guardian must be refused at creation.');
		} catch (Throwable $e) {
			$this->assertSame(400, $e->getCode(), 'The refusal must carry a 400: ' . $e->getMessage());
		}

		$this->assertSame([], $this->saves);

	}//end testCreationRefusesAMinorWithoutAGuardianBeforeWritingAnything()
}//end class
