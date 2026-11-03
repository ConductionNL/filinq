<?php

/**
 * Golden traces of who the signing service acts for, and which guard sees it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\SignerAuth\IdentityEvidence;
use OCA\Filinq\Service\SignerAuth\SigningAssuranceGate;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\SigningMandateService;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The calls every collaborator of SigningService sees when a request is
 * created, read, signed, declined and cancelled, compared with JSON recorded
 * from the implementation that took nine constructor parameters.
 *
 * Signer s-1 belongs to alice and s-2 to mallory; only alice holds the
 * mandate for a dossiq besluit, and the request may be read by its initiator
 * and by alice. Each case names the session user, so the trace shows the user
 * the signer lookup, the mandate, the identity gate and the audit log ran
 * for, and the forged cases show a session user acting for someone else's
 * signer, or outside the mandate, is refused before anything is written.
 *
 * @covers \OCA\Filinq\Service\SigningService
 * @uses   \OCA\Filinq\Service\SigningMandateService
 * @uses   \OCA\Filinq\Service\SignerAuth\IdentityEvidence
 * @uses   \OCA\Filinq\Service\Signing\PortalSignatureAssurance
 * @uses   \OCA\Filinq\Service\Signing\SignerEntries
 * @uses   \OCA\Filinq\Service\Signing\SigningAct
 * @uses   \OCA\Filinq\Kernel\SigningAuditActor
 * @uses   \OCA\Filinq\Kernel\SigningAuditEvent
 * @uses   \OCA\Filinq\Kernel\SigningAuditSubject
 *
 * @spec openspec/specs/document-signing/spec.md
 */
class SigningServiceActingUserTraceTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/signing-service-acting-user.json';

	/**
	 * The cases: session user, the call, and its arguments.
	 *
	 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
	 */
	public static function cases(): array {
		$portal = ['userId' => 'portal:s-1', 'displayName' => 'Ann Portal', 'assurance' => 'substantial'];
		return [
			'create as the initiator'              => ['griffier', 'create', ['documentFileId' => 11, 'signers' => [['userId' => 'alice', 'displayName' => 'Alice']]]],
			'read as the initiator'                => ['griffier', 'read', []],
			'read as a forged caller'              => ['mallory', 'read', []],
			'sign as the signer holding the mandate' => ['alice', 'sign', ['signerId' => 's-1']],
			'sign outside the mandate'             => ['mallory', 'sign', ['signerId' => 's-2']],
			'sign for a forged signer'             => ['mallory', 'sign', ['signerId' => 's-1']],
			'sign through the portal'              => ['', 'sign', ['signerId' => 's-1', 'verifiedActor' => $portal]],
			'decline as the signer'                => ['alice', 'decline', ['signerId' => 's-1']],
			'decline for a forged signer'          => ['mallory', 'decline', ['signerId' => 's-1']],
			'cancel as the initiator'              => ['griffier', 'cancel', []],
			'emit an expired conclusion'           => ['', 'expire', []],
		];

	}//end cases()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string               $uid  The session user.
	 * @param string               $call Which entry point runs.
	 * @param array<string, mixed> $args The arguments.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testTrace(string $uid, string $call, array $args): void {
		$service = $this->service(uid: $uid);
		$this->traceCall(
			match ($call) {
				'create'  => fn () => $service->createRequest(data: $args),
				'read'    => fn () => $service->getRequest(requestId: 'req-1', callerUserId: $uid),
				'sign'    => fn () => $service->sign(requestId: 'req-1', signerId: $args['signerId'], verifiedActor: ($args['verifiedActor'] ?? null)),
				'decline' => fn () => $service->decline(requestId: 'req-1', signerId: $args['signerId'], reason: 'onjuist'),
				'cancel'  => fn () => $service->cancelRequest(requestId: 'req-1'),
				'expire'  => fn () => $service->emitExpiredConclusion(request: ['id' => 'req-1', 'status' => 'EXPIRED']),
			}
		);

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testTrace()

	/**
	 * The service over recording doubles, acting for $uid.
	 *
	 * @param string $uid The session user.
	 *
	 * @return SigningService The service.
	 */
	private function service(string $uid): SigningService {
		$settings = $this->recorder(
			SettingsService::class,
			[
				'getObjectService'              => $this->objectService(),
				'getFeatureToggles'             => ['signing_request_expiry_days' => 14, 'signing_default_level' => 'AdES', 'signing_provider' => 'native'],
				'resolveSigningRequestBinding'  => ['register' => 'filinq', 'schema' => 'signingRequest'],
				'resolveSignerRecordBinding'    => ['register' => 'filinq', 'schema' => 'signerRecord'],
			]
		);
		$owners   = ['s-1' => 'alice', 's-2' => 'mallory'];
		$actor    = $this->recorder(
			SigningActorResolver::class,
			[
				'resolveActingIdentity' => static fn (?array $verifiedActor = null): array => [($verifiedActor['userId'] ?? $uid), ucfirst((string) ($verifiedActor['displayName'] ?? $uid))],
				'loadAuthorisedSigner'  => static function (string $requestId, string $signerId, ?array $verifiedActor, string $actorUserId, string $action) use ($owners): array {
					if ($verifiedActor === null && ($owners[$signerId] ?? '') !== $actorUserId) {
						throw new RuntimeException('Not authorised to '.$action.' for signer '.$signerId);
					}

					return ['signingRequestId' => $requestId, 'userId' => ($owners[$signerId] ?? ''), 'status' => 'PENDING'];
				},
				'mayRead'               => static fn (array $request, string $requestId, string $userId): bool => in_array($userId, ['', 'griffier', 'alice'], true),
				'getClientIp'           => '192.0.2.10',
				'actorAuditMetadata'    => static fn (?array $verifiedActor): array => ($verifiedActor === null ? [] : ['portal' => true]),
			]
		);
		$file     = $this->recorder(File::class, ['getId' => 11, 'getName' => 'besluit.pdf']);

		return new SigningService(
			settingsService: $settings,
			auditService: $this->recorder(SigningAuditService::class, ['logEvent' => []]),
			artifactProducer: $this->recorder(SignedArtifactProducer::class, ['initiatorDocument' => $file, 'delegate' => static fn (array $request): array => $request, 'produce' => 'file:11@v2']),
			validator: $this->recorder(
				SigningRequestValidator::class,
				['validateRequestData' => null, 'validateProviderLevelPair' => null, 'validateSigners' => null, 'withPlacements' => static fn (array $request): array => $request]
			),
			actorResolver: $actor,
			emitter: $this->recorder(SigningConclusionEmitter::class, ['emitIfDelegated' => null]),
			consentGuard: $this->recorder(
				GuardianConsentGuard::class,
				[
					'appliedAge'        => 16,
					'prepareSigners'    => ['links' => [], 'fields' => []],
					'guardSigningAct'   => ['record' => ['consentBasis' => 'adult'], 'audit' => ['consent' => 'adult']],
					'withConsentBasis'  => static fn (array $request): array => $request + ['consentBasis' => 'adult'],
				]
			),
			assuranceGate: $this->recorder(
				SigningAssuranceGate::class,
				[
					'applyToRequest'        => static fn (array $request): array => $request + ['assuranceFloor' => 'low'],
					'floorFor'              => 'low',
					'evidenceForAct'        => new IdentityEvidence('nextcloud', 'session', 'low', 'pseudo-'.$uid, new DateTimeImmutable('2026-01-01T00:00:00+00:00'), 'hash'),
					'consume'               => null,
					'withResolvedAssurance' => static fn (array $request): array => $request + ['assurance' => 'low'],
				]
			),
			mandateService: new SigningMandateService(
				config: $this->recorder(IAppConfig::class, ['getValueString' => json_encode(['dossiq/besluit' => ['groups' => ['portefeuillehouders'], 'rule' => 'Only the portefeuillehouder signs a besluit']])]),
				groupManager: $this->recorder(IGroupManager::class, ['isInGroup' => static fn (string $userId, string $group): bool => ($userId === 'alice' && $group === 'portefeuillehouders')])
			)
		);

	}//end service()

	/**
	 * An object service holding request req-1 with signers s-1 and s-2.
	 *
	 * @return ObjectService The double.
	 */
	private function objectService(): ObjectService {
		$request = [
			'id'              => 'req-1',
			'status'          => 'PENDING',
			'signatureLevel'  => 'AdES',
			'provider'        => 'native',
			'initiatorUserId' => 'griffier',
			'signerIds'       => ['s-1'],
			'sourceApp'       => 'dossiq',
			'subjectSchema'   => 'besluit',
		];
		$signers = ['s-1' => ['id' => 's-1', 'userId' => 'alice', 'status' => 'SIGNED'], 's-2' => ['id' => 's-2', 'userId' => 'mallory', 'status' => 'PENDING']];

		return $this->recorder(
			ObjectService::class,
			[
				'find'       => static fn (string $id): ?array => ($id === 'req-1' ? $request : ($signers[$id] ?? null)),
				'saveObject' => static fn (array $object): array => $object + ['id' => 'saved-'.count($object)],
			]
		);

	}//end objectService()
}//end class
