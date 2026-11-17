<?php

/**
 * Delegated signing authority: the store, the signing act and the folder
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

require_once __DIR__ . '/../SignerAuth/AssuranceGateHarness.php';

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\Signing\SigningDelegationService;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\SigningFolderService;
use OCA\Filinq\Service\SigningMandateService;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\Support\SigningGuards;
use OCA\Filinq\Service\Support\SigningOutputs;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A wethouder delegates besluiten to a colleague from 1 to 14 July.
 *
 * @covers \OCA\Filinq\Service\Signing\SigningDelegationService
 * @covers \OCA\Filinq\Service\SigningFolderService
 * @covers \OCA\Filinq\Service\SigningService
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningDelegationTest extends TestCase {
	use \OCA\Filinq\Tests\Unit\Service\SignerAuth\AssuranceGateHarness;

	/**
	 * The refusal that comes after every delegation and mandate check.
	 */
	private const REACHED_THE_SIGNER_PATH = 'Signer has already responded to this request';

	/**
	 * The stored app config values.
	 *
	 * @var array<string, string>
	 */
	private array $stored = [];

	/**
	 * Today, Y-m-d.
	 *
	 * @var string
	 */
	private string $today = '2026-07-05';

	/**
	 * The delegation store over in-memory config and a settable today.
	 *
	 * @return SigningDelegationService
	 */
	private function delegations(): SigningDelegationService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => $this->stored[$key] ?? $default
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->stored[$key] = $value;
				return true;
			}
		);
		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getTime')->willReturnCallback(
			fn (): int => (new DateTimeImmutable($this->today . ' 12:00:00', new DateTimeZone(date_default_timezone_get())))->getTimestamp()
		);

		return new SigningDelegationService(config: $config, clock: $clock);

	}//end delegations()

	/**
	 * The holiday delegation, stored.
	 *
	 * @param SigningDelegationService $delegations The store
	 *
	 * @return array<string, mixed>
	 */
	private function holiday(SigningDelegationService $delegations): array {
		return $delegations->declare(delegator: 'wethouder', delegate: 'collega', from: '2026-07-01', until: '2026-07-14', types: ['dossiq/besluit']);

	}//end holiday()

	/**
	 * Inside the period and for its type the delegate may act; outside, the refusal names why.
	 *
	 * @return void
	 */
	public function testTheDelegationCoversItsPeriodAndTypesOnly(): void {
		$delegations = $this->delegations();
		$this->holiday($delegations);

		$this->assertNull($delegations->refusal(delegator: 'wethouder', delegate: 'collega', typeReference: 'dossiq/besluit'));
		$this->assertSame(['wethouder'], $delegations->activeDelegators(delegate: 'collega'));
		$this->assertSame('The delegation from wethouder does not cover dossiq/brief', $delegations->refusal(delegator: 'wethouder', delegate: 'collega', typeReference: 'dossiq/brief'));
		$this->assertSame('No delegation from wethouder to iemand', $delegations->refusal(delegator: 'wethouder', delegate: 'iemand', typeReference: null));

		$this->today = '2026-07-15';
		$this->assertSame('The delegation from wethouder ended on 2026-07-14', $delegations->refusal(delegator: 'wethouder', delegate: 'collega', typeReference: 'dossiq/besluit'));
		$this->assertSame([], $delegations->activeDelegators(delegate: 'collega'));
		$this->assertSame(['wethouder'], $delegations->delegatorsOf(delegate: 'collega'));

		$this->today = '2026-06-30';
		$this->assertSame('The delegation from wethouder starts on 2026-07-01', $delegations->refusal(delegator: 'wethouder', delegate: 'collega', typeReference: null));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Signing refused: The delegation from wethouder starts on 2026-07-01');
		$delegations->assertMayActFor(delegator: 'wethouder', delegate: 'collega', typeReference: null);

	}//end testTheDelegationCoversItsPeriodAndTypesOnly()

	/**
	 * A delegation needs both parties, real dates in order and a type.
	 *
	 * @return void
	 */
	public function testADeclarationIsValidated(): void {
		$delegations = $this->delegations();
		$cases = [
			['wethouder', 'wethouder', '2026-07-01', '2026-07-14', ['dossiq/besluit'], 'A signer cannot delegate to themselves'],
			['wethouder', '', '2026-07-01', '2026-07-14', ['dossiq/besluit'], 'A delegation names the signer and the colleague'],
			['wethouder', 'collega', '2026-02-30', '2026-07-14', ['dossiq/besluit'], 'A delegation needs a start and an end date (YYYY-MM-DD)'],
			['wethouder', 'collega', '2026-07-14', '2026-07-01', ['dossiq/besluit'], 'A delegation cannot end before it starts'],
			['wethouder', 'collega', '2026-07-01', '2026-07-14', [], 'A delegation names at least one document type'],
			['wethouder', 'collega', '2026-07-01', '2026-07-14', ['besluit'], 'A delegated document type is an <app>/<schema> reference'],
		];
		foreach ($cases as [$from, $to, $start, $end, $types, $message]) {
			try {
				$delegations->declare(delegator: $from, delegate: $to, from: $start, until: $end, types: $types);
				$this->fail('Accepted: ' . $message);
			} catch (InvalidArgumentException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

		$this->assertSame([], $this->stored);

	}//end testADeclarationIsValidated()

	/**
	 * Only the signer who gave a delegation withdraws it.
	 *
	 * @return void
	 */
	public function testOnlyTheGiverWithdraws(): void {
		$delegations = $this->delegations();
		$delegation = $this->holiday($delegations);

		$this->assertSame([$delegation], $delegations->forUser(userId: 'collega')['received']);
		$this->assertFalse($delegations->withdraw(id: $delegation['id'], delegator: 'collega'));
		$this->assertTrue($delegations->withdraw(id: $delegation['id'], delegator: 'wethouder'));
		$this->assertSame(['given' => [], 'received' => []], $delegations->forUser(userId: 'wethouder'));

	}//end testOnlyTheGiverWithdraws()

	/**
	 * The signing service over a signer record of the wethouder.
	 *
	 * @param SigningDelegationService $delegations The store
	 * @param list<string>             $actedAs     Collects the user the signer record is authorised for
	 *
	 * @return SigningService
	 */
	private function signingService(SigningDelegationService $delegations, array &$actedAs): SigningService {
		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['find', 'saveObject'])
			->getMock();
		$objectService->method('find')->willReturn(
			['id' => 'req-001', 'status' => 'PENDING', 'signatureLevel' => 'AdES', 'provider' => 'native', 'signerIds' => ['signer-001'], 'sourceApp' => 'dossiq', 'subjectSchema' => 'besluit']
		);
		$objectService->expects($this->never())->method('saveObject');

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objectService);
		$settings->method('resolveSigningRequestBinding')->willReturn(['register' => 'filinq', 'schema' => 'signingRequest']);
		$settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);

		$actor = $this->getMockBuilder(SigningActorResolver::class)
			->disableOriginalConstructor()
			->onlyMethods(['resolveActingIdentity', 'loadAuthorisedSigner', 'getClientIp', 'actorAuditMetadata'])
			->getMock();
		$actor->method('resolveActingIdentity')->willReturn(['collega', 'Collega']);
		$actor->method('loadAuthorisedSigner')->willReturnCallback(
			static function (string $requestId, string $signerId, ?array $verifiedActor, string $actorUserId) use (&$actedAs): array {
				$actedAs[] = $actorUserId;
				return ['id' => $signerId, 'signingRequestId' => $requestId, 'userId' => 'wethouder', 'status' => 'SIGNED'];
			}
		);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('');

		return new SigningService(
			settingsService: $settings,
			actorResolver: $actor,
			guards: new SigningGuards(
				validator: $this->createMock(SigningRequestValidator::class),
				consentGuard: new GuardianConsentGuard(settingsService: $settings),
				assuranceGate: $this->assuranceGate(userSession: $this->createMock(\OCP\IUserSession::class)),
				mandateService: new SigningMandateService(config: $config, groupManager: $this->createMock(IGroupManager::class)),
				delegations: $delegations
			),
			outputs: new SigningOutputs(
				auditService: $this->createMock(SigningAuditService::class),
				artifactProducer: $this->createMock(SignedArtifactProducer::class),
				emitter: $this->createMock(SigningConclusionEmitter::class)
			)
		);

	}//end signingService()

	/**
	 * During the delegation the colleague signs as the wethouder's signer record.
	 *
	 * @return void
	 */
	public function testTheDelegateSignsTheSignersRecordDuringTheDelegation(): void {
		$delegations = $this->delegations();
		$this->holiday($delegations);
		$actedAs = [];

		try {
			$this->signingService($delegations, $actedAs)->signOnBehalfOf(requestId: 'req-001', signerId: 'signer-001', delegator: 'wethouder');
			$this->fail('The sentinel signer record was not reached');
		} catch (RuntimeException $e) {
			$this->assertSame(self::REACHED_THE_SIGNER_PATH, $e->getMessage());
		}

		$this->assertSame(['wethouder'], $actedAs);

	}//end testTheDelegateSignsTheSignersRecordDuringTheDelegation()

	/**
	 * After the delegation ended the signature is refused before any record is read.
	 *
	 * @return void
	 */
	public function testAnEndedDelegationIsRefusedBeforeTheRecordIsRead(): void {
		$delegations = $this->delegations();
		$this->holiday($delegations);
		$this->today = '2026-07-15';
		$actedAs = [];

		try {
			$this->signingService($delegations, $actedAs)->signOnBehalfOf(requestId: 'req-001', signerId: 'signer-001', delegator: 'wethouder');
			$this->fail('An ended delegation signed');
		} catch (RuntimeException $e) {
			$this->assertSame('Signing refused: The delegation from wethouder ended on 2026-07-14', $e->getMessage());
		}

		$this->assertSame([], $actedAs);

	}//end testAnEndedDelegationIsRefusedBeforeTheRecordIsRead()

	/**
	 * The folder over one pending besluit of the wethouder.
	 *
	 * @param SigningDelegationService $delegations The store
	 * @param SigningService           $signing     The signing double
	 *
	 * @return SigningFolderService
	 */
	private function folder(SigningDelegationService $delegations, SigningService $signing): SigningFolderService {
		$besluit = ['id' => 'req-001', 'documentName' => 'Besluit 1', 'status' => 'PENDING', 'signerIds' => ['signer-w'], 'sourceApp' => 'dossiq', 'subjectSchema' => 'besluit'];
		$signing->method('listRequests')->willReturnCallback(static fn (string $callerUserId = ''): array => ($callerUserId === 'wethouder') ? [$besluit] : []);
		$signing->method('getRequest')->willReturnCallback(static fn (string $requestId, string $callerUserId = ''): ?array => ($callerUserId === 'wethouder') ? $besluit : null);

		$actor = $this->getMockBuilder(SigningActorResolver::class)->disableOriginalConstructor()->onlyMethods(['findSignerForUser', 'recipientRole'])->getMock();
		$actor->method('findSignerForUser')->willReturnCallback(static fn (array $signerIds, string $userId): ?string => ($userId === 'wethouder') ? 'signer-w' : null);
		// The wethouder is a signer, not an accept-only recipient (signing-accept-only-recipient).
		$actor->method('recipientRole')->willReturn('sign');

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('');
		$mandates = new SigningMandateService(config: $config, groupManager: $this->createMock(IGroupManager::class));

		return new SigningFolderService(signingService: $signing, actorResolver: $actor, mandateService: $mandates, delegations: $delegations);

	}//end folder()

	/**
	 * On 5 July the colleague sees and signs the besluit, which records both names.
	 *
	 * @return void
	 */
	public function testTheDelegateSeesAndSignsTheBesluitDuringTheHoliday(): void {
		$delegations = $this->delegations();
		$this->holiday($delegations);
		$signing = $this->getMockBuilder(SigningService::class)->disableOriginalConstructor()->onlyMethods(['listRequests', 'getRequest', 'signOnBehalfOf'])->getMock();
		$signing->expects($this->once())->method('signOnBehalfOf')->with('req-001', 'signer-w', 'wethouder')
			->willReturn(['signedAt' => '2026-07-05T12:00:00+00:00', 'signedBy' => 'collega', 'signedOnBehalfOf' => 'wethouder']);
		$folder = $this->folder($delegations, $signing);

		$page = $folder->folder(userId: 'collega');
		$this->assertSame(1, $page['total']);
		$this->assertSame('wethouder', $page['entries'][0]['onBehalfOf']);
		$this->assertSame('signer-w', $page['entries'][0]['signerId']);

		$result = $folder->signSelection(requestIds: ['req-001'], userId: 'collega');
		$this->assertSame(1, $result['signed']);
		$this->assertSame('wethouder', $result['results'][0]['onBehalfOf']);

	}//end testTheDelegateSeesAndSignsTheBesluitDuringTheHoliday()

	/**
	 * On 15 July the besluit is gone from the folder and the signature is refused, naming the ended delegation.
	 *
	 * @return void
	 */
	public function testTheEndedDelegationRefusesTheDelegate(): void {
		$delegations = $this->delegations();
		$this->holiday($delegations);
		$this->today = '2026-07-15';
		$signing = $this->getMockBuilder(SigningService::class)->disableOriginalConstructor()->onlyMethods(['listRequests', 'getRequest', 'signOnBehalfOf'])->getMock();
		$signing->expects($this->never())->method('signOnBehalfOf');
		$folder = $this->folder($delegations, $signing);

		$this->assertSame(0, $folder->folder(userId: 'collega')['total']);

		$result = $folder->signSelection(requestIds: ['req-001'], userId: 'collega');
		$this->assertSame(0, $result['signed']);
		$this->assertSame('Signing refused: The delegation from wethouder ended on 2026-07-14', $result['results'][0]['reason']);

	}//end testTheEndedDelegationRefusesTheDelegate()
}//end class
