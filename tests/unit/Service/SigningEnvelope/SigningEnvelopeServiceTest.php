<?php

/**
 * SigningEnvelopeService tests
 *
 * The real SigningService, repository and roll-up over an in-memory
 * OpenRegister: envelopes create ordinary member requests, each signer is
 * notified once, "sign all" goes through the ordinary sign() of every member,
 * and the status rolls up from the members.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SigningEnvelope;

require_once __DIR__ . '/../SignerAuth/AssuranceGateHarness.php';

use OCA\Filinq\Event\SigningConcludedEventFactory;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRepository;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeRollUp;
use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * In-memory OpenRegister: objects by id, per schema.
 */
class EnvelopeObjectStore extends ObjectService {

	/**
	 * Stored objects: id => [schema, data].
	 *
	 * @var array<string, array{0: string, 1: array}>
	 */
	public array $rows = [];

	/**
	 * Every save, in order: [schema, payload].
	 *
	 * @var list<array{0: string, 1: array}>
	 */
	public array $saves = [];

	/**
	 * Find an object.
	 *
	 * @param string $id            Id
	 * @param string $register      Register
	 * @param string $schema        Schema
	 * @param bool   $_rbac         RBAC
	 * @param bool   $_multitenancy Multitenancy
	 * @param bool   $_render       Render
	 * @param bool   $_audit        Audit
	 *
	 * @return array|null
	 */
	public function find(
		string $id = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
		bool $_render = true,
		bool $_audit = true,
	) {
		return ($this->rows[$id][1] ?? null);

	}//end find()

	/**
	 * Save an object.
	 *
	 * @param array       $object        Data
	 * @param string      $register      Register
	 * @param string      $schema        Schema
	 * @param string|null $uuid          Uuid
	 * @param bool        $_rbac         RBAC
	 * @param bool        $_multitenancy Multitenancy
	 *
	 * @return array
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$this->saves[] = [$schema, $object];
		$id = (string) ($uuid ?? ($object['id'] ?? ''));
		if ($id === '') {
			$id = $schema . '-' . (count($this->rows) + 1);
		}

		$object['id'] = $id;
		$this->rows[$id] = [$schema, $object];

		return $object;

	}//end saveObject()

	/**
	 * Search by slug.
	 *
	 * @param string $registerSlug  Register
	 * @param string $schemaSlug    Schema
	 * @param array  $filters       Field filters
	 * @param bool   $_rbac         RBAC
	 * @param bool   $_multitenancy Multitenancy
	 *
	 * @return array
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$out = [];
		foreach ($this->rows as [$schema, $data]) {
			if ($schema === $schemaSlug && array_intersect_assoc($filters, $data) === $filters) {
				$out[] = $data;
			}
		}

		return $out;

	}//end searchObjectsBySlug()

	/**
	 * The stored objects of one schema.
	 *
	 * @param string $schema The schema
	 *
	 * @return list<array>
	 */
	public function of(string $schema): array {
		$out = [];
		foreach ($this->rows as [$rowSchema, $data]) {
			if ($rowSchema === $schema) {
				$out[] = $data;
			}
		}

		return $out;

	}//end of()
}//end class

/**
 * Envelope behaviour over the real signing path.
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
class SigningEnvelopeServiceTest extends TestCase {
	use \OCA\Filinq\Tests\Unit\Service\SignerAuth\AssuranceGateHarness;

	/**
	 * The in-memory register.
	 *
	 * @var EnvelopeObjectStore
	 */
	private EnvelopeObjectStore $store;

	/**
	 * The session user's uid, switchable per test.
	 *
	 * @var string
	 */
	private string $uid = 'alice';

	/**
	 * Real signing service.
	 *
	 * @var SigningService
	 */
	private SigningService $signing;

	/**
	 * Service under test.
	 *
	 * @var SigningEnvelopeService
	 */
	private SigningEnvelopeService $envelopes;

	/**
	 * Build the real services over the in-memory register.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->store = new EnvelopeObjectStore();

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($this->store);
		$settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);
		$settings->method('getFeatureToggles')->willReturn(
			['signing_request_expiry_days' => 30, 'signing_default_level' => 'SES', 'signing_provider' => 'native']
		);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => [
				'signerRecord_register' => 'filinq',
				'signerRecord_schema' => 'signerRecord',
			][$key] ?? $default
		);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturnCallback(
			function (): IUser {
				$user = $this->createMock(IUser::class);
				$user->method('getUID')->willReturn($this->uid);
				$user->method('getDisplayName')->willReturn(ucfirst($this->uid));
				return $user;
			}
		);

		$request = $this->createMock(IRequest::class);
		$request->method('getRemoteAddress')->willReturn('127.0.0.1');
		$providerFactory = $this->createMock(SigningProviderFactory::class);
		$provider = $this->createMock(\OCA\Filinq\Service\Signing\SigningProviderInterface::class);
		$provider->method('supportsLevel')->willReturn(true);
		$providerFactory->method('getProvider')->willReturn($provider);

		$this->signing = new SigningService(
			settingsService: $settings,
			auditService: $this->createMock(SigningAuditService::class),
			artifactProducer: new SignedArtifactProducer(
				providerFactory: $providerFactory,
				userSession: $userSession,
				request: $request,
				rootFolder: $this->createMock(IRootFolder::class),
				finalDocuments: $this->createMock(FinalDocumentService::class)
			),
			validator: new SigningRequestValidator(providerFactory: $providerFactory),
			actorResolver: new SigningActorResolver(settingsService: $settings, config: $config, userSession: $userSession, request: $request),
			emitter: new SigningConclusionEmitter(
				eventDispatcher: $this->createMock(IEventDispatcher::class),
				logger: $this->createMock(LoggerInterface::class),
				eventFactory: new SigningConcludedEventFactory()
			),
			consentGuard: new GuardianConsentGuard(settingsService: $settings),
			assuranceGate: $this->assuranceGate(userSession: $userSession)
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);
		$this->envelopes = new SigningEnvelopeService(
			signing: $this->signing,
			repository: new SigningEnvelopeRepository(objectResolver: $resolver),
			rollUp: new SigningEnvelopeRollUp()
		);

	}//end setUp()

	/**
	 * An envelope of three documents for Bob and Carol.
	 *
	 * @return array The created envelope
	 */
	private function threeDocuments(): array {
		return $this->envelopes->create(
			input: [
				'title' => 'Arbeidsovereenkomst',
				'documents' => [
					['documentFileId' => '11', 'documentName' => 'contract.pdf'],
					['documentFileId' => '12', 'documentName' => 'geheimhouding.pdf'],
					['documentFileId' => '13', 'documentName' => 'reglement.pdf'],
				],
				'signers' => [
					['userId' => 'bob', 'displayName' => 'Bob', 'email' => 'bob@example.invalid'],
					['userId' => 'carol', 'displayName' => 'Carol', 'email' => 'carol@example.invalid'],
				],
				'signingMode' => 'parallel',
			],
			userId: 'alice'
		);

	}//end threeDocuments()

	/**
	 * Validate a payload against the real schema fragment of the register.
	 *
	 * @param string $schemaName The schema
	 * @param array  $payload    The payload as saved
	 *
	 * @return void
	 */
	private function assertValidAgainst(string $schemaName, array $payload): void {
		$descriptor = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$schema = $descriptor['components']['schemas'][$schemaName];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(
			['type' => 'object', 'properties' => $properties, 'required' => ($schema['required'] ?? []), 'additionalProperties' => false]
		);
		$result = (new Validator())->validate(json_decode((string) json_encode(array_diff_key($payload, ['id' => true]))), $json);
		$message = '';
		if ($result->isValid() === false) {
			$message = $schemaName . ': ' . (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $message);

	}//end assertValidAgainst()

	/**
	 * Three ordinary requests, each carrying the envelope, and one envelope
	 * stored last; every payload fits its real schema fragment.
	 *
	 * @return void
	 */
	public function testAnEnvelopeCreatesOrdinaryMemberRequestsAndIsStoredLast(): void {
		$envelope = $this->threeDocuments();

		$requests = $this->store->of('signingRequest');
		$this->assertCount(3, $requests);
		$this->assertSame(array_column($requests, 'id'), $envelope['requestRefs']);
		foreach ($requests as $request) {
			$this->assertSame($envelope['uuid'], $request['envelopeRef']);
			$this->assertSame('PENDING', $request['status']);
			$this->assertCount(2, $request['signerIds']);
		}

		$this->assertSame('signingEnvelope', end($this->store->saves)[0], 'The envelope is saved after its members');
		$this->assertSame(['bob', 'carol'], $envelope['signerUserIds']);
		$this->assertSame('pending', $envelope['status']);
		$this->assertSame(3, $envelope['documentCount']);
		$this->assertSame(['PENDING', 'PENDING', 'PENDING'], array_column($envelope['members'], 'status'));

		foreach ($this->store->saves as [$schema, $payload]) {
			$this->assertValidAgainst(schemaName: $schema, payload: $payload);
		}

	}//end testAnEnvelopeCreatesOrdinaryMemberRequestsAndIsStoredLast()

	/**
	 * Each signer gets one notification per envelope: the per-document rule
	 * skips a signer record that carries an envelope, and the envelope's own
	 * rule names every signer.
	 *
	 * @return void
	 */
	public function testEachSignerIsNotifiedOncePerEnvelope(): void {
		$this->threeDocuments();
		$descriptor = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$schemas = $descriptor['components']['schemas'];

		$perDocument = $schemas['signerRecord']['x-openregister-notifications']['signingRequested']['trigger'];
		$this->assertSame(['type' => 'created', 'filter' => ['field' => 'envelopeRef', 'operator' => 'equals', 'value' => '']], $perDocument);

		$records = $this->store->of('signerRecord');
		$this->assertCount(6, $records);
		// OpenRegister's created-filter compares the field as a string, a missing field being ''.
		$notifiedPerDocument = array_filter($records, static fn (array $r): bool => (string) ($r['envelopeRef'] ?? '') === '');
		$this->assertSame([], $notifiedPerDocument);

		$perEnvelope = $schemas['signingEnvelope']['x-openregister-notifications']['envelopeRequested'];
		$this->assertSame(['type' => 'created'], $perEnvelope['trigger']);
		$this->assertSame([['kind' => 'relation', 'relation' => 'signerUserIds']], $perEnvelope['recipients']);
		$this->assertCount(1, $this->store->of('signingEnvelope'));

		// A request on its own still notifies per document.
		$this->signing->createRequest(data: ['documentFileId' => '99', 'documentName' => 'los.pdf', 'signers' => [['userId' => 'bob']]]);
		$standalone = end($this->store->saves)[1];
		$standaloneSigner = $this->store->rows[$standalone['signerIds'][0]][1];
		$this->assertSame('', (string) ($standaloneSigner['envelopeRef'] ?? ''));

	}//end testEachSignerIsNotifiedOncePerEnvelope()

	/**
	 * Bob signs all three at once; each member is signed through sign() and
	 * keeps its own signer record; the envelope is in progress until Carol signs.
	 *
	 * @return void
	 */
	public function testSignAllSignsEveryMemberThroughTheOrdinarySign(): void {
		$envelope = $this->threeDocuments();

		$this->uid = 'bob';
		$outcome = $this->envelopes->signAll(id: $envelope['uuid'], userId: 'bob');

		$this->assertNotNull($outcome);
		$this->assertSame(array_fill(0, 3, true), array_values(array_column($outcome['results'], 'success')));
		$signed = array_filter($this->store->of('signerRecord'), static fn (array $r): bool => $r['userId'] === 'bob' && $r['status'] === 'SIGNED');
		$this->assertCount(3, $signed);
		$this->assertSame(['IN_PROGRESS', 'IN_PROGRESS', 'IN_PROGRESS'], array_column($outcome['envelope']['members'], 'status'));
		$this->assertSame('in_progress', $outcome['envelope']['status']);
		$this->assertSame('in_progress', $this->store->rows[$envelope['uuid']][1]['status'], 'The roll-up is stored');

	}//end testSignAllSignsEveryMemberThroughTheOrdinarySign()

	/**
	 * A member whose identity gate refuses a direct sign() is refused in the
	 * ceremony with the same error, the other members are still signed, and a
	 * declined member makes the envelope partly declined.
	 *
	 * @return void
	 */
	public function testSignAllRefusesAMemberWithTheDirectErrorAndGoesOn(): void {
		$envelope = $this->threeDocuments();
		[$first, $second, $third] = $envelope['requestRefs'];
		$this->store->rows[$third][1]['requiredAssurance'] = 'high';

		$this->uid = 'bob';
		$bobThird = $this->store->rows[$third][1]['signerIds'][0];
		$direct = '';
		try {
			$this->signing->sign(requestId: $third, signerId: $bobThird);
		} catch (\Exception $e) {
			$direct = $e->getMessage();
		}

		$this->assertNotSame('', $direct);

		$outcome = $this->envelopes->signAll(id: $envelope['uuid'], userId: 'bob');

		$this->assertTrue($outcome['results'][$first]['success']);
		$this->assertTrue($outcome['results'][$second]['success']);
		$this->assertFalse($outcome['results'][$third]['success']);
		$this->assertSame($direct, $outcome['results'][$third]['error']);
		$this->assertSame(['IN_PROGRESS', 'IN_PROGRESS', 'PENDING'], array_column($outcome['envelope']['members'], 'status'));

		$this->uid = 'carol';
		$this->signing->decline(requestId: $second, signerId: $this->store->rows[$second][1]['signerIds'][1], reason: 'Wrong version');
		$after = $this->envelopes->get(id: $envelope['uuid'], userId: 'bob', isAdmin: false);
		$this->assertSame('partially_declined', $after['status']);
		$this->assertSame('SIGNED', $this->store->rows[$this->store->rows[$first][1]['signerIds'][0]][1]['status'], 'What Bob signed stays signed');

	}//end testSignAllRefusesAMemberWithTheDirectErrorAndGoesOn()

	/**
	 * Only the initiator, a signer the envelope names, or an admin reads it;
	 * only a named signer signs through it; only the initiator or an admin cancels.
	 *
	 * @return void
	 */
	public function testAccessFollowsTheSingleRequestPosture(): void {
		$envelope = $this->threeDocuments();
		$id = $envelope['uuid'];

		$this->assertNotNull($this->envelopes->get(id: $id, userId: 'alice', isAdmin: false));
		$this->assertNotNull($this->envelopes->get(id: $id, userId: 'carol', isAdmin: false));
		$this->assertNotNull($this->envelopes->get(id: $id, userId: 'admin', isAdmin: true));
		$this->assertNull($this->envelopes->get(id: $id, userId: 'mallory', isAdmin: false));
		$this->assertNull($this->envelopes->signAll(id: $id, userId: 'mallory'));
		$this->assertNull($this->envelopes->signAll(id: $id, userId: 'alice'), 'The initiator is not a signer here');
		$this->assertNull($this->envelopes->cancel(id: $id, userId: 'carol', isAdmin: false));
		$this->assertSame([], $this->envelopes->listFor(userId: 'carol', isAdmin: false));
		$this->assertCount(1, $this->envelopes->listFor(userId: 'alice', isAdmin: false));

	}//end testAccessFollowsTheSingleRequestPosture()

	/**
	 * Cancelling cancels the open members and leaves a declined one alone.
	 *
	 * @return void
	 */
	public function testCancelCancelsOpenMembersOnly(): void {
		$envelope = $this->threeDocuments();
		$second = $envelope['requestRefs'][1];
		// A request is declined once it is under way (PENDING cannot move to DECLINED).
		$this->uid = 'bob';
		$this->signing->sign(requestId: $second, signerId: $this->store->rows[$second][1]['signerIds'][0]);
		$this->uid = 'carol';
		$this->signing->decline(requestId: $second, signerId: $this->store->rows[$second][1]['signerIds'][1], reason: 'No');

		$this->uid = 'alice';
		$cancelled = $this->envelopes->cancel(id: $envelope['uuid'], userId: 'alice', isAdmin: false);

		$this->assertSame(2, $cancelled['cancelledRequests']);
		$this->assertSame(['CANCELLED', 'DECLINED', 'CANCELLED'], array_column($cancelled['members'], 'status'));
		$this->assertSame('cancelled', $cancelled['status']);

	}//end testCancelCancelsOpenMembersOnly()

	/**
	 * A document that cannot become a request refuses the envelope, names the
	 * document, cancels the members already made and stores no envelope.
	 *
	 * @return void
	 */
	public function testARefusedDocumentUndoesTheEnvelope(): void {
		try {
			$this->envelopes->create(
				input: [
					'documents' => [
						['documentFileId' => '11', 'documentName' => 'a.pdf'],
						['documentFileId' => '12', 'documentName' => 'b.pdf'],
					],
					'signers' => [['userId' => 'bob']],
					'signatureLevel' => 'NOPE',
				],
				userId: 'alice'
			);
			$this->fail('An invalid level was accepted');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertStringStartsWith('Document 1 (a.pdf): ', $e->getMessage());
		}

		$this->assertSame([], $this->store->of('signingEnvelope'));

		try {
			$this->envelopes->create(input: ['documents' => [['documentFileId' => '11']], 'signers' => [['userId' => 'bob']]], userId: 'alice');
			$this->fail('One document was accepted');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
		}

	}//end testARefusedDocumentUndoesTheEnvelope()
}//end class
