<?php

/**
 * Unit tests for SigningService
 *
 * Covers the signing request lifecycle: create, get, list, sign, decline,
 * cancel, bulk sign, and status transition validation per REQ-SIGN-01..05.
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
 * @spec openspec/changes/document-signing/tasks.md#2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/SignerAuth/AssuranceGateHarness.php';

use OCA\Filinq\Event\SigningConcludedEventFactory;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use DateTimeImmutable;
use OCA\Filinq\Exception\StepUpRequiredException;
use OCA\Filinq\Service\SignerAuth\IdentityEvidence;

/**
 * Tests for SigningService signing request lifecycle
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningServiceTest extends TestCase {
	use \OCA\Filinq\Tests\Unit\Service\SignerAuth\AssuranceGateHarness;


	/**
	 * @var SigningService
	 */
	private SigningService $service;

	/**
	 * @var SettingsService|MockObject
	 */
	private SettingsService|MockObject $settingsService;

	/**
	 * @var ObjectService|MockObject
	 */
	private ObjectService|MockObject $objectService;

	/**
	 * @var SigningAuditService|MockObject
	 */
	private SigningAuditService|MockObject $auditService;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $config;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $userSession;

	/**
	 * @var IUser|MockObject
	 */
	private IUser|MockObject $user;

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $request;

	/**
	 * @var SigningProviderFactory|MockObject
	 */
	private SigningProviderFactory|MockObject $providerFactory;

	/**
	 * @var \OCP\Files\IRootFolder|MockObject
	 */
	private \OCP\Files\IRootFolder|MockObject $rootFolder;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->objectService = $this->getMockBuilder(className: ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['saveObject', 'find', 'findAll', 'searchObjects', 'buildSearchQuery', 'searchObjectsPaginated'])
			->getMock();

		$this->settingsService = $this->createMock(SettingsService::class);
		$this->settingsService->method('getObjectService')->willReturn($this->objectService);

		// SigningService now resolves its register/schema bindings through
		// SettingsService and FAILS CLOSED when either half is unset, instead
		// of reading them inline with an empty-string default and writing to
		// register '' / schema ''. An unstubbed mock returns null, so these
		// stubs are what keep the suite exercising the configured path — and
		// their absence is what the fail-closed guard is there to catch.
		$this->settingsService->method('resolveSigningRequestBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$this->settingsService->method('resolveSignerRecordBinding')
			->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);
		$this->settingsService->method('getFeatureToggles')->willReturn(
			[
				'signing_request_expiry_days' => 30,
				'signing_default_level' => 'SES',
				'signing_provider' => 'native',
			]
		);

		$this->auditService = $this->createMock(SigningAuditService::class);

		$this->config = $this->createMock(IAppConfig::class);
		$this->config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				$map = [
					'signingRequest_register' => 'signing',
					'signingRequest_schema' => 'signingRequest',
					'signerRecord_register' => 'signing',
					'signerRecord_schema' => 'signerRecord',
					'signing_request_expiry_days' => '30',
					'signing_default_level' => 'SES',
					'signing_provider' => 'native',
				];
				return $map[$key] ?? $default;
			}
		);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');
		$this->user->method('getDisplayName')->willReturn('Alice');

		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn($this->user);

		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getRemoteAddress')->willReturn('127.0.0.1');

		$this->providerFactory = $this->createMock(SigningProviderFactory::class);
		$logger = $this->createMock(LoggerInterface::class);
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->rootFolder = $this->createMock(\OCP\Files\IRootFolder::class);

		// Real (not mocked) collaborators, so these tests still exercise the
		// identity-resolution, signer-authorisation and conclusion-emission
		// code paths end to end after they moved out of SigningService.
		$this->service = new SigningService(
			settingsService: $this->settingsService,
			auditService: $this->auditService,
			artifactProducer: new SignedArtifactProducer(
				providerFactory: $this->providerFactory,
				userSession: $this->userSession,
				request: $this->request,
				rootFolder: $this->rootFolder,
				finalDocuments: $this->createMock(FinalDocumentService::class)
			),
			validator: new SigningRequestValidator(providerFactory: $this->providerFactory),
			actorResolver: new SigningActorResolver(
				settingsService: $this->settingsService,
				config: $this->config,
				userSession: $this->userSession,
				request: $this->request
			),
			emitter: new SigningConclusionEmitter(
				eventDispatcher: $eventDispatcher,
				logger: $logger,
				eventFactory: new SigningConcludedEventFactory()
			),
			consentGuard: new GuardianConsentGuard(settingsService: $this->settingsService),
			assuranceGate: $this->assuranceGate(userSession: $this->userSession)
		);

	}//end setUp()

	/**
	 * createRequest() with valid data creates and persists a signing request.
	 *
	 * @return void
	 */
	public function testCreateRequestHappyPath(): void {
		$savedRequest = [
			'id' => 'req-001',
			'documentFileId' => 'file-001',
			'documentName' => 'besluit.pdf',
			'initiatorUserId' => 'alice',
			'signatureLevel' => 'SES',
			'signingMode' => 'sequential',
			'status' => 'PENDING',
			'provider' => 'native',
			'signerIds' => [],
		];

		$this->providerFactory->method('getProvider')->willReturn($this->makeSupportingProvider());

		// First saveObject call creates the request; subsequent calls update signerIds.
		$this->objectService->expects($this->atLeastOnce())
			->method('saveObject')
			->willReturn($savedRequest);

		$this->auditService->expects($this->once())
			->method('logEvent');

		$result = $this->service->createRequest(
			data: [
				'documentFileId' => 'file-001',
				'documentName' => 'besluit.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'sequential',
				'signers' => [['displayName' => 'Bea', 'email' => 'bea@example.org']],
			]
		);

		$this->assertSame('PENDING', $result['status']);
		$this->assertSame('alice', $result['initiatorUserId']);

	}//end testCreateRequestHappyPath()

	/**
	 * createRequest() persists consumer provenance fields onto the request.
	 *
	 * Cross-app delegated-signing contract (filinq-signing-events): a request
	 * raised via DocumentSigningRequestedEvent carries provenance that must be
	 * stored so the terminal SigningConcludedEvent can correlate back.
	 *
	 * @return void
	 */
	public function testCreateRequestPersistsProvenance(): void {
		$captured = [];

		$this->providerFactory->method('getProvider')->willReturn($this->makeSupportingProvider());

		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$captured): array {
				// Capture the first save (the signing-request create), which
				// carries the provenance; ignore signer-record saves.
				if (isset($object['initiatorUserId']) === true && $captured === []) {
					$captured = $object;
				}

				$object['id'] = 'req-prov-1';
				return $object;
			}
		);

		$this->service->createRequest(
			data: [
				'documentFileId' => 'file-9',
				'documentName' => 'contract.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'sequential',
				'signers' => [['displayName' => 'Bea', 'email' => 'bea@example.org']],
				'sourceApp' => 'shillinq',
				'subjectRegister' => 'finance',
				'subjectSchema' => 'invoice',
				'subjectId' => 'inv-42',
				'externalReference' => 'ext-42',
				'correlationId' => 'corr-42',
			]
		);

		$this->assertSame('shillinq', $captured['sourceApp'] ?? null);
		$this->assertSame('inv-42', $captured['subjectId'] ?? null);
		$this->assertSame('ext-42', $captured['externalReference'] ?? null);
		$this->assertSame('corr-42', $captured['correlationId'] ?? null);

	}//end testCreateRequestPersistsProvenance()

	/**
	 * createRequest() leaves an internal request free of provenance.
	 *
	 * @return void
	 */
	public function testCreateRequestInternalHasNoProvenance(): void {
		$captured = [];

		$this->providerFactory->method('getProvider')->willReturn($this->makeSupportingProvider());

		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$captured): array {
				if (isset($object['initiatorUserId']) === true && $captured === []) {
					$captured = $object;
				}

				$object['id'] = 'req-int-1';
				return $object;
			}
		);

		$this->service->createRequest(
			data: [
				'documentFileId' => 'file-int',
				'documentName' => 'internal.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'sequential',
				'signers' => [['displayName' => 'Bea', 'email' => 'bea@example.org']],
			]
		);

		$this->assertArrayNotHasKey('sourceApp', $captured);

	}//end testCreateRequestInternalHasNoProvenance()

	/**
	 * Issue #1209: a request that names nobody to sign is refused with a 400
	 * before anything is stored, instead of being saved as a PENDING request
	 * nobody can ever sign.
	 *
	 * @return void
	 */
	public function testCreateRequestRejectsARequestWithoutSigners(): void {
		$this->providerFactory->method('getProvider')->willReturn($this->makeSupportingProvider());
		$this->objectService->expects($this->never())->method('saveObject');

		foreach ([[], [['displayName' => 'Nobody reachable']], [['userId' => '', 'email' => '  ']]] as $signers) {
			try {
				$this->service->createRequest(
					data: [
						'documentFileId' => 'file-001',
						'documentName' => 'besluit.pdf',
						'signatureLevel' => 'SES',
						'signingMode' => 'sequential',
						'signers' => $signers,
					]
				);
				$this->fail('createRequest() must refuse a request without a reachable signer: '.json_encode($signers));
			} catch (RuntimeException $e) {
				$this->assertSame(400, $e->getCode());
				$this->assertStringContainsString('signer', $e->getMessage());
			}
		}

	}//end testCreateRequestRejectsARequestWithoutSigners()

	/**
	 * Issue #1209: the signers sent with the form are stored as signer records
	 * linked to the new request.
	 *
	 * @return void
	 */
	public function testCreateRequestStoresTheSignersItIsGiven(): void {
		$saved = [];
		$this->providerFactory->method('getProvider')->willReturn($this->makeSupportingProvider());
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$saved): array {
				$saved[] = $object;
				$object['id'] = (isset($object['initiatorUserId']) === true) ? 'req-1209' : 'signer-'.count($saved);
				return $object;
			}
		);

		$result = $this->service->createRequest(
			data: [
				'documentFileId' => 'file-001',
				'documentName' => 'besluit.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'sequential',
				'signers' => [
					['displayName' => 'Bea', 'email' => 'bea@example.org'],
					['displayName' => 'Carl', 'userId' => 'carl'],
				],
			]
		);

		$signerRecords = array_values(array_filter($saved, static fn (array $o) => isset($o['signingRequestId']) === true));
		$this->assertCount(2, $signerRecords);
		$this->assertSame('req-1209', $signerRecords[0]['signingRequestId']);
		$this->assertSame('bea@example.org', $signerRecords[0]['email']);
		$this->assertSame('carl', $signerRecords[1]['userId']);
		$this->assertCount(2, $result['signerIds']);

	}//end testCreateRequestStoresTheSignersItIsGiven()

	/**
	 * createRequest() rejects missing documentFileId.
	 *
	 * @return void
	 */
	public function testCreateRequestRejectsEmptyDocumentFileId(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Document file ID is required');

		$this->service->createRequest(
			data: [
				'documentFileId' => '',
				'documentName' => 'test.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'sequential',
			]
		);

	}//end testCreateRequestRejectsEmptyDocumentFileId()

	/**
	 * createRequest() rejects an invalid signature level.
	 *
	 * @return void
	 */
	public function testCreateRequestRejectsInvalidSignatureLevel(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid signature level');

		$this->service->createRequest(
			data: [
				'documentFileId' => 'file-001',
				'documentName' => 'test.pdf',
				'signatureLevel' => 'INVALID',
				'signingMode' => 'sequential',
			]
		);

	}//end testCreateRequestRejectsInvalidSignatureLevel()

	/**
	 * createRequest() rejects an invalid signing mode.
	 *
	 * @return void
	 */
	public function testCreateRequestRejectsInvalidSigningMode(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid signing mode');

		$this->service->createRequest(
			data: [
				'documentFileId' => 'file-001',
				'documentName' => 'test.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'unknown-mode',
			]
		);

	}//end testCreateRequestRejectsInvalidSigningMode()

	/**
	 * getRequest() returns the object when found.
	 *
	 * @return void
	 */
	public function testGetRequestReturnsObjectWhenFound(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
		];
		$this->objectService->method('find')->willReturn($requestData);

		$result = $this->service->getRequest(requestId: 'req-001');

		$this->assertSame('PENDING', $result['status']);

	}//end testGetRequestReturnsObjectWhenFound()

	/**
	 * getRequest() throws when the request is not found.
	 *
	 * @return void
	 */
	public function testGetRequestThrowsWhenNotFound(): void {
		$this->objectService->method('find')->willReturn(null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Signing request not found');

		$this->service->getRequest(requestId: 'does-not-exist');

	}//end testGetRequestThrowsWhenNotFound()

	/**
	 * listRequests() returns an array of request arrays when the paginated
	 * search surface yields plain associative arrays.
	 *
	 * Locks the register/schema-context refactor: listRequests() resolves
	 * results via buildSearchQuery()+searchObjectsPaginated() (not findAll),
	 * which return a ['results' => [...]] envelope.
	 *
	 * @return void
	 */
	public function testListRequestsReturnsArray(): void {
		$this->objectService->method('buildSearchQuery')->willReturn(['_limit' => 1000]);
		$this->objectService->method('searchObjectsPaginated')->willReturn(
			[
				'results' => [
					['id' => 'req-001', 'status' => 'PENDING'],
					['id' => 'req-002', 'status' => 'COMPLETED'],
				],
			]
		);

		$result = $this->service->listRequests();

		$this->assertCount(2, $result);
		$this->assertSame('req-001', $result[0]['id']);
		$this->assertSame('COMPLETED', $result[1]['status']);

	}//end testListRequestsReturnsArray()

	/**
	 * listRequests() serialises OpenRegister ObjectEntity results via
	 * jsonSerialize() rather than casting them to array.
	 *
	 * Regression lock for the signing Entity-vs-array bug: when
	 * searchObjectsPaginated() returns ObjectEntity instances (the real
	 * OpenRegister behaviour), listRequests() must call ->jsonSerialize()
	 * to obtain the flat record. A naive (array) cast would expose the
	 * entity's protected properties instead of the object data, dropping the
	 * 'id'/'status' fields the callers depend on.
	 *
	 * @return void
	 */
	public function testListRequestsSerialisesObjectEntitiesViaJsonSerialize(): void {
		$entityOne = $this->makeSigningRequestEntity(['id' => 'req-001', 'status' => 'PENDING']);
		$entityTwo = $this->makeSigningRequestEntity(['id' => 'req-002', 'status' => 'COMPLETED']);

		$this->objectService->method('buildSearchQuery')->willReturn(['_limit' => 1000]);
		$this->objectService->method('searchObjectsPaginated')->willReturn(
			['results' => [$entityOne, $entityTwo]]
		);

		$result = $this->service->listRequests();

		$this->assertCount(2, $result);
		// The flat record fields are present, proving jsonSerialize() was used.
		$this->assertSame('req-001', $result[0]['id']);
		$this->assertSame('PENDING', $result[0]['status']);
		$this->assertSame('req-002', $result[1]['id']);
		$this->assertSame('COMPLETED', $result[1]['status']);

	}//end testListRequestsSerialisesObjectEntitiesViaJsonSerialize()

	/**
	 * listRequests() applies the WF2 non-admin visibility filter so a caller
	 * only sees requests they initiated or are a signer on.
	 *
	 * @return void
	 */
	public function testListRequestsFiltersForNonAdminCaller(): void {
		$this->objectService->method('buildSearchQuery')->willReturn(['_limit' => 1000]);
		$this->objectService->method('searchObjectsPaginated')->willReturn(
			[
				'results' => [
					['id' => 'req-001', 'status' => 'PENDING', 'initiatorUserId' => 'alice'],
					['id' => 'req-002', 'status' => 'PENDING', 'initiatorUserId' => 'bob', 'signerIds' => ['alice']],
					['id' => 'req-003', 'status' => 'PENDING', 'initiatorUserId' => 'bob', 'signerIds' => ['carol']],
				],
			]
		);

		$result = $this->service->listRequests(callerUserId: 'alice');

		// alice initiated req-001 and signs req-002; req-003 is hidden.
		$this->assertCount(2, $result);
		$ids = array_column($result, 'id');
		$this->assertContains('req-001', $ids);
		$this->assertContains('req-002', $ids);
		$this->assertNotContains('req-003', $ids);

	}//end testListRequestsFiltersForNonAdminCaller()

	/**
	 * listRequests() returns every request unfiltered for an UNSCOPED caller.
	 *
	 * callerUserId='' is the single explicit scoping bypass — the spelling an
	 * admin caller uses (SigningController::listRequests()).
	 *
	 * @return void
	 */
	public function testListRequestsReturnsAllForAdminCaller(): void {
		$this->objectService->method('buildSearchQuery')->willReturn(['_limit' => 1000]);
		$this->objectService->method('searchObjectsPaginated')->willReturn(
			[
				'results' => [
					['id' => 'req-001', 'status' => 'PENDING', 'initiatorUserId' => 'alice'],
					['id' => 'req-003', 'status' => 'PENDING', 'initiatorUserId' => 'bob', 'signerIds' => ['carol']],
				],
			]
		);

		$result = $this->service->listRequests(callerUserId: '');

		$this->assertCount(2, $result);

	}//end testListRequestsReturnsAllForAdminCaller()

	/**
	 * getRequest() denies a SCOPED caller who is neither initiator nor signer.
	 *
	 * NEGATIVE CONTROL for the WF2 / Wilco #6 caller-scoping contract. Before
	 * this test the single-record scoping in getRequest() was pinned by NOTHING
	 * — deleting the guard entirely left the whole suite green. Access denied
	 * must collapse to null, the SAME shape as not-found, so an unrelated user
	 * cannot probe request-ID existence.
	 *
	 * @return void
	 */
	public function testGetRequestReturnsNullForScopedCallerWhoIsNeitherInitiatorNorSigner(): void {
		$this->objectService->method('find')->willReturn(
			$this->makeSigningRequestEntity(
				[
					'id' => 'req-001',
					'status' => 'PENDING',
					'initiatorUserId' => 'bob',
					'signerIds' => ['carol'],
				]
			)
		);

		$result = $this->service->getRequest(requestId: 'req-001', callerUserId: 'mallory');

		$this->assertNull($result);

	}//end testGetRequestReturnsNullForScopedCallerWhoIsNeitherInitiatorNorSigner()

	/**
	 * getRequest() allows a SCOPED caller who is the initiator.
	 *
	 * Positive control for the test above: proves the scoped path can return a
	 * record at all, so the null assertion there is evidence about the GUARD
	 * and not about a broken fixture.
	 *
	 * @return void
	 */
	public function testGetRequestAllowsScopedInitiator(): void {
		$this->objectService->method('find')->willReturn(
			$this->makeSigningRequestEntity(
				[
					'id' => 'req-001',
					'status' => 'PENDING',
					'initiatorUserId' => 'bob',
					'signerIds' => ['carol'],
				]
			)
		);

		$result = $this->service->getRequest(requestId: 'req-001', callerUserId: 'bob');

		$this->assertIsArray($result);
		$this->assertSame('req-001', $result['id']);

	}//end testGetRequestAllowsScopedInitiator()

	/**
	 * getRequest() allows a SCOPED caller who is a listed signer.
	 *
	 * @return void
	 */
	public function testGetRequestAllowsScopedSigner(): void {
		$this->objectService->method('find')->willReturn(
			$this->makeSigningRequestEntity(
				[
					'id' => 'req-001',
					'status' => 'PENDING',
					'initiatorUserId' => 'bob',
					'signerIds' => ['carol'],
				]
			)
		);

		$result = $this->service->getRequest(requestId: 'req-001', callerUserId: 'carol');

		$this->assertIsArray($result);
		$this->assertSame('req-001', $result['id']);

	}//end testGetRequestAllowsScopedSigner()

	/**
	 * getRequest() returns the record UNSCOPED for callerUserId=''.
	 *
	 * That is the single explicit bypass an admin caller uses — the caller
	 * would be denied by the scoped test above.
	 *
	 * @return void
	 */
	public function testGetRequestReturnsRecordForUnscopedCaller(): void {
		$this->objectService->method('find')->willReturn(
			$this->makeSigningRequestEntity(
				[
					'id' => 'req-001',
					'status' => 'PENDING',
					'initiatorUserId' => 'bob',
					'signerIds' => ['carol'],
				]
			)
		);

		$result = $this->service->getRequest(requestId: 'req-001', callerUserId: '');

		$this->assertIsArray($result);
		$this->assertSame('req-001', $result['id']);

	}//end testGetRequestReturnsRecordForUnscopedCaller()

	/**
	 * Build an ObjectEntity-like double whose jsonSerialize() returns the
	 * given flat record, mirroring OpenRegister's real return shape.
	 *
	 * @param array<string, mixed> $data The record to expose via jsonSerialize().
	 *
	 * @return \OCA\OpenRegister\Db\ObjectEntity
	 */
	private function makeSigningRequestEntity(array $data): \OCA\OpenRegister\Db\ObjectEntity {
		return new class($data) extends \OCA\OpenRegister\Db\ObjectEntity {
			/**
			 * @var array<string, mixed>
			 */
			private array $record;

			/**
			 * @param array<string, mixed> $record The flat record.
			 */
			public function __construct(array $record) {
				$this->record = $record;
			}

			/**
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->record;
			}
		};

	}//end makeSigningRequestEntity()

	/**
	 * sign() marks the signer SIGNED and logs the audit event.
	 *
	 * @return void
	 */
	public function testSignHappyPath(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
			'signatureLevel' => 'SES',
			'provider' => 'native',
			'initiatorUserId' => 'bob',
			'signerIds' => ['signer-001'],
		];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-001',
			'userId' => 'alice',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls(
			$requestData,
			$signerData,
			$requestData,
			$signerData
		);
		$this->objectService->method('saveObject')->willReturnArgument(0);

		$this->auditService->expects($this->once())
			->method('logEvent')
			->with($this->equalTo('req-001'), $this->equalTo('SIGNED'));

		$result = $this->service->sign(requestId: 'req-001', signerId: 'signer-001');

		$this->assertSame('SIGNED', $result['status']);
		$this->assertArrayHasKey('signedAt', $result);

	}//end testSignHappyPath()

	/**
	 * A portal signature records its assurance level, capped by the session
	 * trust and never QES (portal-signing-surface REQ-DDPSS-005).
	 *
	 * @return void
	 */
	public function testPortalSignatureRecordsAssuranceNoHigherThanTheSession(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
			'signatureLevel' => 'AES',
			'provider' => 'native',
			'initiatorUserId' => 'bob',
			'signerIds' => ['signer-001'],
		];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-001',
			'email' => 'mark@home.example',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls(
			$requestData,
			$signerData,
			$requestData,
			$signerData
		);

		$saved = [];
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$saved): array {
				$saved[] = $object;

				return $object;
			}
		);

		$result = $this->service->sign(
			requestId: 'req-001',
			signerId: 'signer-001',
			verifiedActor: [
				'email' => 'mark@home.example',
				'subjectRef' => 'sub-1',
				'identityRef' => 'sub-1',
				'trust' => 'low',
				'jti' => 'jti-1',
			]
		);

		$this->assertSame('SES', $result['signatureAssurance'] ?? null, 'A low session caps an AES request at SES.');
		$this->assertSame('SES', $saved[0]['signatureAssurance'] ?? null, 'The capped level is what is stored.');

	}//end testPortalSignatureRecordsAssuranceNoHigherThanTheSession()

	/**
	 * An in-app signature carries no portal assurance field.
	 *
	 * @return void
	 */
	public function testInAppSignatureRecordsNoPortalAssurance(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
			'signatureLevel' => 'SES',
			'provider' => 'native',
			'initiatorUserId' => 'bob',
			'signerIds' => ['signer-001'],
		];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-001',
			'userId' => 'alice',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls(
			$requestData,
			$signerData,
			$requestData,
			$signerData
		);
		$this->objectService->method('saveObject')->willReturnArgument(0);

		$result = $this->service->sign(requestId: 'req-001', signerId: 'signer-001');

		$this->assertArrayNotHasKey('signatureAssurance', $result);

	}//end testInAppSignatureRecordsNoPortalAssurance()

	/**
	 * The completing signature produces + stores a signed artifact and sets
	 * signedDocumentRef to that artifact (native-ses-signature-embedding).
	 *
	 * @return void
	 */
	public function testCompletingSignatureStoresArtifactAndSetsRef(): void {
		$request = [
			'id' => 'req-001',
			'status' => 'IN_PROGRESS',
			'signatureLevel' => 'SES',
			'provider' => 'native',
			'initiatorUserId' => 'alice',
			'documentFileId' => '42',
			'signerIds' => ['signer-001'],
		];
		$signerPending = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$signerSigned = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'SIGNED'];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls(
			$request,
			$signerPending,
			$signerSigned,
			$request
		);

		$captured = [];
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$captured): array {
				if (($object['status'] ?? '') === 'COMPLETED') {
					$captured = $object;
				}

				return $object;
			}
		);

		// Provider produces signed bytes.
		$provider = $this->createMock(\OCA\Filinq\Service\Signing\SigningProviderInterface::class);
		$provider->method('produceSignedArtifact')->willReturn('SIGNED-BYTES');
		$this->providerFactory->method('getProvider')->willReturn($provider);

		// File resolves through the initiator folder; putContent creates the version.
		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('getContent')->willReturn('original-bytes');
		$stored = null;
		$file->method('putContent')->willReturnCallback(function ($bytes) use (&$stored): void {
			$stored = $bytes;
		});

		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->willReturn($folder);

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

		$this->assertSame('SIGNED-BYTES', $stored, 'The signed bytes must be stored as a new file version.');
		$this->assertSame('COMPLETED', $captured['status'] ?? null);
		$expectedRef = '42:signed:' . substr(hash('sha256', 'SIGNED-BYTES'), 0, 16);
		$this->assertSame($expectedRef, $captured['signedDocumentRef'] ?? null);
		$this->assertStringStartsWith('42:signed:', $captured['signedDocumentRef'] ?? '');
		$this->assertNotSame('42', $captured['signedDocumentRef'] ?? null, 'Ref must not be the unsigned original file id.');

	}//end testCompletingSignatureStoresArtifactAndSetsRef()

	/**
	 * Honest-completion gate: when the provider cannot produce an artifact the
	 * completing signature fails and the request is NOT marked COMPLETED.
	 *
	 * @return void
	 */
	public function testHonestCompletionGateWhenProviderCannotProduce(): void {
		$request = [
			'id' => 'req-001',
			'status' => 'IN_PROGRESS',
			'signatureLevel' => 'SES',
			'provider' => 'native',
			'initiatorUserId' => 'alice',
			'documentFileId' => '42',
			'signerIds' => ['signer-001'],
		];
		$signerPending = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$signerSigned = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'SIGNED'];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls(
			$request,
			$signerPending,
			$signerSigned,
			$request
		);

		$sawCompleted = false;
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$sawCompleted): array {
				if (($object['status'] ?? '') === 'COMPLETED') {
					$sawCompleted = true;
				}

				return $object;
			}
		);

		$provider = $this->createMock(\OCA\Filinq\Service\Signing\SigningProviderInterface::class);
		$provider->method('produceSignedArtifact')->willThrowException(
			new RuntimeException('signing_verification_secret is unset')
		);
		$this->providerFactory->method('getProvider')->willReturn($provider);

		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('getContent')->willReturn('original-bytes');
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->willReturn($folder);

		$threw = false;
		try {
			$this->service->sign(requestId: 'req-001', signerId: 'signer-001');
		} catch (RuntimeException $e) {
			$threw = true;
			$this->assertStringContainsString('secret', $e->getMessage());
		}

		$this->assertTrue($threw, 'The completing signature must fail loudly.');
		$this->assertFalse($sawCompleted, 'The request must NOT be marked COMPLETED without an artifact.');

	}//end testHonestCompletionGateWhenProviderCannotProduce()

	/**
	 * sign() throws when signer record belongs to a different request.
	 *
	 * @return void
	 */
	public function testSignThrowsOnSignerRequestMismatch(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
		];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-other',
			'userId' => 'alice',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls($requestData, $signerData);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('does not belong to this signing request');

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

	}//end testSignThrowsOnSignerRequestMismatch()

	/**
	 * sign() throws when the authenticated user is not the signer.
	 *
	 * @return void
	 */
	public function testSignThrowsWhenUserIsNotSigner(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
		];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-001',
			'userId' => 'charlie',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls($requestData, $signerData);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Not authorized to sign as this signer');

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

	}//end testSignThrowsWhenUserIsNotSigner()

	/**
	 * cancelRequest() transitions the request status to CANCELLED.
	 *
	 * @return void
	 */
	public function testCancelRequestTransitionsToCancel(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturn($requestData);
		$this->objectService->method('saveObject')->willReturnArgument(0);

		$result = $this->service->cancelRequest(requestId: 'req-001');

		$this->assertSame('CANCELLED', $result['status']);

	}//end testCancelRequestTransitionsToCancel()

	/**
	 * cancelRequest() throws when the request is in a terminal state.
	 *
	 * @return void
	 */
	public function testCancelRequestThrowsForTerminalStatus(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'COMPLETED',
		];

		$this->objectService->method('find')->willReturn($requestData);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot cancel request in status');

		$this->service->cancelRequest(requestId: 'req-001');

	}//end testCancelRequestThrowsForTerminalStatus()

	/**
	 * isValidTransition() returns true for allowed transitions.
	 *
	 * @return void
	 */
	public function testIsValidTransitionAllowedPaths(): void {
		$this->assertTrue($this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'IN_PROGRESS'));
		$this->assertTrue($this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'COMPLETED'));
		$this->assertTrue($this->service->isValidTransition(currentStatus: 'PENDING', newStatus: 'CANCELLED'));
		$this->assertTrue($this->service->isValidTransition(currentStatus: 'IN_PROGRESS', newStatus: 'EXPIRED'));

	}//end testIsValidTransitionAllowedPaths()

	/**
	 * isValidTransition() returns false for disallowed transitions.
	 *
	 * @return void
	 */
	public function testIsValidTransitionBlockedPaths(): void {
		$this->assertFalse($this->service->isValidTransition(currentStatus: 'COMPLETED', newStatus: 'PENDING'));
		$this->assertFalse($this->service->isValidTransition(currentStatus: 'DECLINED', newStatus: 'IN_PROGRESS'));
		$this->assertFalse($this->service->isValidTransition(currentStatus: 'EXPIRED', newStatus: 'SIGNED'));
		$this->assertFalse($this->service->isValidTransition(currentStatus: 'CANCELLED', newStatus: 'IN_PROGRESS'));

	}//end testIsValidTransitionBlockedPaths()

	/**
	 * bulkSign() returns success/error results per request ID.
	 *
	 * @return void
	 */
	public function testBulkSignReturnsResultsPerRequest(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'PENDING',
			'signatureLevel' => 'SES',
			'provider' => 'native',
			'signerIds' => [],
		];

		$this->objectService->method('find')->willReturn($requestData);

		$results = $this->service->bulkSign(requestIds: ['req-001']);

		$this->assertArrayHasKey('req-001', $results);
		// No signer record found for current user → error result.
		$this->assertFalse($results['req-001']['success']);

	}//end testBulkSignReturnsResultsPerRequest()

	/**
	 * Build a permissive SigningProviderInterface double (supportsLevel: true).
	 *
	 * @return \OCA\Filinq\Service\Signing\SigningProviderInterface&MockObject
	 */
	private function makeSupportingProvider(): \OCA\Filinq\Service\Signing\SigningProviderInterface {
		$provider = $this->createMock(\OCA\Filinq\Service\Signing\SigningProviderInterface::class);
		$provider->method('supportsLevel')->willReturn(true);

		return $provider;
	}//end makeSupportingProvider()

	/**
	 * Provider/level honesty (signing-trust-rebuild REQ-DDSTR-002 point 1):
	 * createRequest() rejects a provider/level pair the provider does not
	 * support with HTTP 400, before anything is persisted.
	 *
	 * @return void
	 */
	public function testCreateRequestRejectsUnsupportedProviderLevelPair(): void {
		$refusingProvider = $this->createMock(\OCA\Filinq\Service\Signing\SigningProviderInterface::class);
		$refusingProvider->method('supportsLevel')->willReturn(false);
		$this->providerFactory->method('getProvider')->willReturn($refusingProvider);

		$this->objectService->expects($this->never())->method('saveObject');

		try {
			$this->service->createRequest(
				data: [
					'documentFileId' => 'file-001',
					'documentName' => 'contract.pdf',
					'signatureLevel' => 'QES',
					'signingMode' => 'sequential',
					'provider' => 'native',
				]
			);
			$this->fail('createRequest() must reject an unsupported provider/level pair.');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertStringContainsString('does not support signature level', $e->getMessage());
		}

	}//end testCreateRequestRejectsUnsupportedProviderLevelPair()

	/**
	 * createRequest() rejects an unknown provider name with HTTP 400, before
	 * anything is persisted (signing-trust-rebuild REQ-DDSTR-002 point 1).
	 *
	 * @return void
	 */
	public function testCreateRequestRejectsUnknownProvider(): void {
		$this->providerFactory->method('getProvider')->willThrowException(
			new RuntimeException('Signing provider not available: bogus')
		);

		$this->objectService->expects($this->never())->method('saveObject');

		try {
			$this->service->createRequest(
				data: [
					'documentFileId' => 'file-001',
					'documentName' => 'contract.pdf',
					'signatureLevel' => 'SES',
					'signingMode' => 'sequential',
					'provider' => 'bogus',
				]
			);
			$this->fail('createRequest() must reject an unknown provider.');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertStringContainsString('Unknown signing provider', $e->getMessage());
		}

	}//end testCreateRequestRejectsUnknownProvider()

	/**
	 * decline() rejects a COMPLETED request via the status machine — the
	 * stored status is unchanged and the signer record is NOT mutated
	 * (signing-trust-rebuild REQ-DDSTR-003, closing the #282 residual where
	 * decline() skipped isValidTransition() entirely).
	 *
	 * @return void
	 */
	public function testDeclineRejectedOnCompletedRequest(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'COMPLETED',
		];

		$this->objectService->method('find')->willReturn($requestData);
		// The gate must reject BEFORE any signer/request mutation.
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot decline request in status');

		$this->service->decline(requestId: 'req-001', signerId: 'signer-001', reason: 'changed my mind');

	}//end testDeclineRejectedOnCompletedRequest()

	/**
	 * decline() from a signable (IN_PROGRESS) state still works: the signer
	 * record becomes DECLINED with the reason and the request becomes
	 * DECLINED (signing-trust-rebuild REQ-DDSTR-003).
	 *
	 * @return void
	 */
	public function testDeclineHappyPathFromInProgress(): void {
		$requestData = [
			'id' => 'req-001',
			'status' => 'IN_PROGRESS',
			'signatureLevel' => 'SES',
			'provider' => 'native',
		];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-001',
			'userId' => 'alice',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls($requestData, $signerData);
		$this->objectService->method('saveObject')->willReturnArgument(0);

		$this->auditService->expects($this->once())
			->method('logEvent')
			->with($this->equalTo('req-001'), $this->equalTo('DECLINED'));

		$result = $this->service->decline(requestId: 'req-001', signerId: 'signer-001', reason: 'changed my mind');

		$this->assertSame('DECLINED', $result['status']);
		$this->assertSame('changed my mind', $result['declineReason']);

	}//end testDeclineHappyPathFromInProgress()

	/**
	 * decline() throws when signer record belongs to a different request
	 * (C4 check preserved by the rewritten decline()).
	 *
	 * @return void
	 */
	public function testDeclineThrowsOnSignerRequestMismatch(): void {
		$requestData = ['id' => 'req-001', 'status' => 'IN_PROGRESS'];
		$signerData = [
			'id' => 'signer-001',
			'signingRequestId' => 'req-other',
			'userId' => 'alice',
			'status' => 'PENDING',
		];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls($requestData, $signerData);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('does not belong to this signing request');

		$this->service->decline(requestId: 'req-001', signerId: 'signer-001', reason: 'no');

	}//end testDeclineThrowsOnSignerRequestMismatch()

	/**
	 * Provider/level honesty on the completion path (signing-trust-rebuild
	 * REQ-DDSTR-002 point 2): an unknown/unregistered provider name on a
	 * completing request fails loudly — NO fallback to getActiveProvider().
	 *
	 * @return void
	 */
	public function testCompletionFailsLoudlyOnUnknownProviderNoFallback(): void {
		$request = [
			'id' => 'req-001',
			'status' => 'IN_PROGRESS',
			'signatureLevel' => 'SES',
			'provider' => 'bogus',
			'initiatorUserId' => 'alice',
			'documentFileId' => '42',
			'signerIds' => ['signer-001'],
		];
		$signerPending = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$signerSigned = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'SIGNED'];

		$this->objectService->method('find')->willReturnOnConsecutiveCalls(
			$request,
			$signerPending,
			$signerSigned,
			$request
		);

		$sawCompleted = false;
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$sawCompleted): array {
				if (($object['status'] ?? '') === 'COMPLETED') {
					$sawCompleted = true;
				}

				return $object;
			}
		);

		$this->providerFactory->expects($this->once())
			->method('getProvider')
			->with($this->equalTo('bogus'))
			->willThrowException(new RuntimeException('Signing provider not available: bogus'));
		$this->providerFactory->expects($this->never())->method('getActiveProvider');

		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('getContent')->willReturn('original-bytes');
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->willReturn($folder);

		$threw = false;
		try {
			$this->service->sign(requestId: 'req-001', signerId: 'signer-001');
		} catch (RuntimeException $e) {
			$threw = true;
		}

		$this->assertTrue($threw, 'Completion must fail loudly for an unknown provider.');
		$this->assertFalse($sawCompleted, 'The request must NOT complete via a silently substituted provider.');

	}//end testCompletionFailsLoudlyOnUnknownProviderNoFallback()
	/**
	 * Keep step-up evidence for req-001 / signer-001, as the broker callback does.
	 *
	 * @param string $assurance The assurance.
	 * @param int $ageSeconds How long ago the signer authenticated.
	 *
	 * @return void
	 */
	private function keepStepUpEvidence(string $assurance, int $ageSeconds = 30): void {
		$this->evidenceStore()->put(
			requestId: 'req-001',
			signerId: 'signer-001',
			evidence: new IdentityEvidence(
				provider: 'oidc-broker',
				means: 'digid',
				assurance: $assurance,
				subjectPseudonym: 'ps-abcdef',
				authenticatedAt: (new DateTimeImmutable())->modify('-' . $ageSeconds . ' seconds'),
				evidenceHash: str_repeat('d', 64)
			)
		);

	}//end keepStepUpEvidence()

	/**
	 * A substantial request refuses a session-only signer: 403 with a step-up hint, nothing written.
	 *
	 * @return void
	 */
	public function testASubstantialRequestRefusesASessionOnlySignerWithoutMutation(): void {
		$request = ['id' => 'req-001', 'status' => 'PENDING', 'signatureLevel' => 'SES', 'requiredAssurance' => 'substantial', 'signerIds' => ['signer-001']];
		$signer = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$this->objectService->method('find')->willReturnOnConsecutiveCalls($request, $signer);
		$this->objectService->expects($this->never())->method('saveObject');
		$this->auditService->expects($this->never())->method('logEvent');

		try {
			$this->service->sign(requestId: 'req-001', signerId: 'signer-001');
			$this->fail('A session-only signer must not sign a substantial request');
		} catch (StepUpRequiredException $e) {
			$this->assertSame(403, $e->getCode());
			$this->assertSame('substantial', $e->stepUp()['requiredAssurance']);
		}

	}//end testASubstantialRequestRefusesASessionOnlySignerWithoutMutation()

	/**
	 * Stale evidence does not carry over (REQ-DDSIR-003 "Stale evidence does not carry over").
	 *
	 * @return void
	 */
	public function testStaleEvidenceDoesNotCarryOver(): void {
		$this->keepStepUpEvidence(assurance: 'substantial', ageSeconds: 20 * 60);
		$request = ['id' => 'req-001', 'status' => 'PENDING', 'signatureLevel' => 'SES', 'requiredAssurance' => 'substantial', 'signerIds' => ['signer-001']];
		$signer = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$this->objectService->method('find')->willReturnOnConsecutiveCalls($request, $signer);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(StepUpRequiredException::class);

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

	}//end testStaleEvidenceDoesNotCarryOver()

	/**
	 * After step-up the act is accepted and the evidence lands on the record and the audit entry.
	 *
	 * @return void
	 */
	public function testStepUpEvidenceLandsOnTheRecordAndTheAuditEntry(): void {
		$this->keepStepUpEvidence(assurance: 'substantial');
		$request = ['id' => 'req-001', 'status' => 'PENDING', 'signatureLevel' => 'SES', 'requiredAssurance' => 'substantial', 'signerIds' => ['signer-001', 'signer-002']];
		$signer = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$other = ['id' => 'signer-002', 'signingRequestId' => 'req-001', 'userId' => 'bob', 'status' => 'PENDING'];
		$this->objectService->method('find')->willReturnOnConsecutiveCalls($request, $signer, $signer + ['status' => 'SIGNED'], $other, $request);
		$this->objectService->method('saveObject')->willReturnArgument(0);

		$metadata = null;
		$this->auditService->expects($this->once())->method('logEvent')->willReturnCallback(
			function (...$args) use (&$metadata): array {
				$metadata = $args[7] ?? null;
				return [];
			}
		);

		$result = $this->service->sign(requestId: 'req-001', signerId: 'signer-001');

		$this->assertSame('SIGNED', $result['status']);
		$this->assertSame('oidc-broker', $result['identityEvidence']['provider']);
		$this->assertSame('substantial', $result['identityEvidence']['assurance']);
		$this->assertSame('ps-abcdef', $result['identityEvidence']['subjectPseudonym']);
		$this->assertSame($result['identityEvidence'], $metadata['identityEvidence'] ?? null);
		$this->assertNull($this->evidenceStore()->get(requestId: 'req-001', signerId: 'signer-001'), 'The act spends the evidence');

	}//end testStepUpEvidenceLandsOnTheRecordAndTheAuditEntry()

	/**
	 * decline() runs the same gate.
	 *
	 * @return void
	 */
	public function testDeclineIsGatedToo(): void {
		$request = ['id' => 'req-001', 'status' => 'IN_PROGRESS', 'signatureLevel' => 'SES', 'requiredAssurance' => 'high'];
		$signer = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];
		$this->objectService->method('find')->willReturnOnConsecutiveCalls($request, $signer);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(StepUpRequiredException::class);

		$this->service->decline(requestId: 'req-001', signerId: 'signer-001', reason: 'no');

	}//end testDeclineIsGatedToo()

	/**
	 * Completion writes the resolved assurance and each signer's evidence, and hands both to the artifact.
	 *
	 * @return void
	 */
	public function testCompletionRecordsTheResolvedAssuranceAndHandsTheEvidenceToTheArtifact(): void {
		$this->keepStepUpEvidence(assurance: 'substantial');
		$request = ['id' => 'req-001', 'status' => 'IN_PROGRESS', 'signatureLevel' => 'SES', 'provider' => 'native', 'requiredAssurance' => 'substantial', 'initiatorUserId' => 'alice', 'documentFileId' => '42', 'signerIds' => ['signer-001']];
		$pending = ['id' => 'signer-001', 'signingRequestId' => 'req-001', 'userId' => 'alice', 'status' => 'PENDING'];

		$saved = [];
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$saved): array {
				$saved[] = $object;
				return $object;
			}
		);
		$this->objectService->method('find')->willReturnCallback(
			function (string $id) use ($request, $pending, &$saved): array {
				if ($id === 'req-001') {
					return $request;
				}

				foreach (array_reverse($saved) as $object) {
					if (($object['id'] ?? '') === $id) {
						return $object;
					}
				}

				return $pending;
			}
		);

		$context = null;
		$provider = $this->createMock(\OCA\Filinq\Service\Signing\SigningProviderInterface::class);
		$provider->method('produceSignedArtifact')->willReturnCallback(
			function (string $bytes, array $ctx) use (&$context): string {
				$context = $ctx;
				return 'SIGNED-BYTES';
			}
		);
		$this->providerFactory->method('getProvider')->willReturn($provider);
		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('getContent')->willReturn('original-bytes');
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder->method('getById')->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')->willReturn($folder);

		$this->service->sign(requestId: 'req-001', signerId: 'signer-001');

		$completed = array_values(array_filter($saved, static fn (array $o): bool => ($o['status'] ?? '') === 'COMPLETED'))[0] ?? [];
		$this->assertSame('substantial', $completed['resolvedAssurance'] ?? null);
		$this->assertSame('signer-001', $completed['signerEvidence'][0]['signerId'] ?? null);
		$this->assertSame('oidc-broker', $completed['signerEvidence'][0]['provider'] ?? null);
		$this->assertSame($completed['signerEvidence'], $context['signerEvidence'] ?? null);

	}//end testCompletionRecordsTheResolvedAssuranceAndHandsTheEvidenceToTheArtifact()

	/**
	 * The completion payload carries exactly the recorded assurance (REQ-DDSIR-007).
	 *
	 * @return void
	 */
	public function testTheCompletionPayloadCarriesTheRecordedAssurance(): void {
		$factory = new SigningConcludedEventFactory();

		$recorded = $factory->create(request: ['id' => 'r', 'provider' => 'native', 'signatureLevel' => 'SES', 'resolvedAssurance' => 'substantial'], status: 'signed');
		$this->assertSame('substantial', $recorded->getAssuranceLevel());

		$forged = $factory->create(request: ['id' => 'r', 'provider' => 'native', 'signatureLevel' => 'SES', 'resolvedAssurance' => 'ultra'], status: 'signed');
		$this->assertSame('low', $forged->getAssuranceLevel(), 'A value off the scale is never surfaced');

		$legacy = $factory->create(request: ['id' => 'r', 'provider' => 'native', 'signatureLevel' => 'SES'], status: 'declined');
		$this->assertSame('low', $legacy->getAssuranceLevel());

	}//end testTheCompletionPayloadCarriesTheRecordedAssurance()

	/**
	 * Creation holds a request at its floor and says so.
	 *
	 * @return void
	 */
	public function testCreateRequestHoldsTheAssuranceAtTheFloor(): void {
		$this->providerFactory->method('getProvider')->willReturn($this->makeSupportingProvider());
		$first = null;
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object) use (&$first): array {
				$first ??= $object;
				return ['id' => $object['id'] ?? 'req-001'] + $object;
			}
		);

		$result = $this->service->createRequest(
			data: [
				'documentFileId' => 'file-001',
				'documentName' => 'besluit.pdf',
				'signatureLevel' => 'AdES',
				'provider' => 'validsign',
				'requiredAssurance' => 'low',
				'signers' => [['displayName' => 'Bea', 'email' => 'bea@example.org']],
			]
		);

		$this->assertSame('substantial', $first['requiredAssurance']);
		$this->assertSame('substantial', $result['requiredAssurance']);
		$this->assertSame('substantial', $result['assuranceFloor']);

	}//end testCreateRequestHoldsTheAssuranceAtTheFloor()

}//end class
