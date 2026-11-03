<?php

/**
 * Golden traces of who a consent update acts for, and what the record writer writes.
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

use DateTime;
use OCA\Filinq\Kernel\ConsentUpdate;
use OCA\Filinq\Service\ConsentNotesHelper;
use OCA\Filinq\Service\ConsentPolicyReferentValidator;
use OCA\Filinq\Service\ConsentRecordWriter;
use OCA\Filinq\Service\ConsentScopeValidator;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\ConsentUpdateHandler;
use OCA\Filinq\Service\ObjectionDeadlineChecker;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\Support\ConsentRecordRules;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The calls the group manager, OpenRegister and the update handler see when a
 * consent is created, re-submitted, validated and updated, compared with JSON
 * recorded from the implementation whose update took five parameters and
 * whose record writer took eight.
 *
 * Only ingrid is in the standing-consent group. The forged cases put another
 * user's id into the update payload: the group check and the recorded actor
 * still follow the session user, never the payload.
 *
 * @covers \OCA\Filinq\Service\ConsentService
 * @covers \OCA\Filinq\Service\ConsentRecordWriter
 * @uses   \OCA\Filinq\Service\ConsentScopeValidator
 * @uses   \OCA\Filinq\Service\ConsentPolicyReferentValidator
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 * @uses   \OCA\Filinq\Service\RegisterObjectRef
 * @uses   \OCA\Filinq\Service\ConsentStatusChange
 * @uses   \OCA\Filinq\Service\Support\ConsentRecordRules
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 * @uses   \OCA\Filinq\Kernel\ConsentUpdate
 *
 * @spec openspec/specs/consent-management/spec.md
 */
class ConsentActingUserTraceTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/consent-acting-user.json';

	/**
	 * Update cases: the session user (or null), the stored scope and the update payload.
	 *
	 * @return array<string, array{0: ?string, 1: string, 2: array<string, mixed>}>
	 */
	public static function updates(): array {
		$standing = ['consentStatus' => 'consent_withdrawn', 'evidence' => 'brief'];
		return [
			'standing consent by a group member'              => ['ingrid', 'entity', $standing],
			'standing consent by a forged user'               => ['mallory', 'entity', $standing + ['actor' => 'ingrid', 'updatedBy' => 'ingrid']],
			'standing consent without a session user'         => [null, 'entity', $standing],
			'document consent records the session user'       => ['mallory', 'document', ['consentStatus' => 'consent_given', 'actor' => 'ingrid']],
			'document consent cannot change scope'            => ['ingrid', 'document', ['scope' => 'entity']],
		];

	}//end updates()

	/**
	 * The update trace matches the recorded one.
	 *
	 * @param string|null          $uid   The session user.
	 * @param string               $scope The stored consent's scope.
	 * @param array<string, mixed> $data  The update payload.
	 *
	 * @dataProvider updates
	 *
	 * @return void
	 */
	public function testUpdateTrace(?string $uid, string $scope, array $data): void {
		$stored = ['scope' => $scope, 'consentStatus' => 'pending', 'documentId' => 'doc-1', 'entityType' => 'PERSON', 'entityText' => 'Jan'];
		if ($scope === 'entity') {
			$stored = ['scope' => 'entity', 'consentStatus' => 'consent_given', 'entityType' => 'PERSON', 'entityText' => 'Jan', 'matchRules' => [['type' => 'exact', 'value' => 'Jan']], 'consentMethod' => 'paper'];
		}

		$user = null;
		if ($uid !== null) {
			$user = $this->recorder(IUser::class, ['getUID' => $uid]);
		}

		$service = $this->service(stored: $stored, existing: []);
		$this->traceCall(fn () => $service->validateAndUpdateConsent(update: new ConsentUpdate(consentId: 'c-1', register: 'filinq', schema: 'publicationConsent', data: $data), user: $user));

		$this->assertTrace(file: self::GOLDEN, name: 'update: '.$this->dataName());

	}//end testUpdateTrace()

	/**
	 * Writer cases: the existing consent rows OpenRegister finds and the request extras.
	 *
	 * @return array<string, array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}>
	 */
	public static function requests(): array {
		$extra = ['entityKey' => 'ent-7', 'publicationBases' => ['5.1.2e', '5.1.1c'], 'contactEmail' => 'jan@example.org', 'owner' => 'mallory'];
		return [
			'a new consent is saved under the session user' => [[], $extra],
			'a re-submission updates the existing consent'  => [[['scope' => 'document', 'documentId' => 'doc-1', 'notes' => 'eerder', 'consentStatus' => 'consent_given']], $extra],
			'a standing consent row is never matched'       => [[['scope' => 'entity', 'entityText' => 'Jan']], ['contactAddress' => 'Dorpsstraat 1']],
		];

	}//end requests()

	/**
	 * The create trace matches the recorded one.
	 *
	 * @param array<int, array<string, mixed>> $existing The rows the idempotency lookup finds.
	 * @param array<string, mixed>             $extra    The request extras.
	 *
	 * @dataProvider requests
	 *
	 * @return void
	 */
	public function testCreateTrace(array $existing, array $extra): void {
		$service = $this->service(stored: [], existing: $existing);
		$this->traceCall(fn () => $service->createConsentRequest(documentId: 'doc-1', entityType: 'PERSON', entityText: 'Jan', register: 'filinq', schema: 'publicationConsent', extra: $extra));

		$this->assertTrace(file: self::GOLDEN, name: 'create: '.$this->dataName());

	}//end testCreateTrace()

	/**
	 * Validating publication consent data goes to the referent validator; without OpenRegister nothing is created.
	 *
	 * @return void
	 */
	public function testValidateAndUnavailableTrace(): void {
		$service = $this->service(stored: [], existing: []);
		$this->traceCall(fn () => $service->validatePublicationConsentData(data: ['scope' => 'document', 'documentId' => 'doc-1', 'policyMatch' => 'p-1']));
		$this->traceCall(fn () => $service->validatePublicationConsentData(data: ['scope' => 'entity']));

		$offline = $this->service(stored: [], existing: [], installed: []);
		$this->traceCall(fn () => $offline->createConsentRequest(documentId: 'doc-1', entityType: 'PERSON', entityText: 'Jan', register: 'filinq', schema: 'publicationConsent'));

		$this->assertTrace(file: self::GOLDEN, name: 'validate and unavailable');

	}//end testValidateAndUnavailableTrace()

	/**
	 * The consent service over recording doubles.
	 *
	 * @param array<string, mixed>             $stored    The consent record find() returns.
	 * @param array<int, array<string, mixed>> $existing  The rows searchObjects() returns.
	 * @param array<int, string>               $installed The installed apps.
	 *
	 * @return ConsentService The service.
	 */
	private function service(array $stored, array $existing, array $installed = ['openregister']): ConsentService {
		$objects   = $this->recorder(
			ObjectService::class,
			[
				'find'          => fn (string $id): ?object => ($id === 'c-1' ? $this->saved(data: $stored) : null),
				'searchObjects' => $existing,
				'saveObject'    => fn (array $object): object => $this->saved(data: $object + ['id' => 'saved']),
			]
		);
		$container = $this->recorder(ContainerInterface::class, ['get' => $objects]);
		$apps      = $this->recorder(IAppManager::class, ['getInstalledApps' => $installed]);
		$logger    = $this->recorder(LoggerInterface::class, ['info' => null, 'warning' => null, 'error' => null]);
		$scope     = new ConsentScopeValidator(
			groupManager: $this->recorder(
				IGroupManager::class,
				[
					'isAdmin'   => false,
					'isInGroup' => static fn (string $userId, string $group): bool => ($userId === 'ingrid'),
				]
			)
		);
		$deadlines = $this->recorder(ObjectionDeadlineChecker::class, ['calculateDeadline' => new DateTime('2026-12-01T00:00:00+00:00')]);
		$writer    = new ConsentRecordWriter(
			logger: $logger,
			openRegister: new OpenRegisterAccess(apps: $apps, container: $container),
			rules: new ConsentRecordRules(
				deadlineChecker: $deadlines,
				notesHelper: new ConsentNotesHelper(),
				scopeValidator: $scope,
				referentValidator: new ConsentPolicyReferentValidator(logger: $logger, container: $container, appManager: $apps)
			)
		);

		return new ConsentService(
			logger: $logger,
			deadlineChecker: $deadlines,
			updateHandler: $this->recorder(ConsentUpdateHandler::class, ['updateConsentStatus' => ['updated' => true]]),
			policyMatcher: $this->recorder(PolicyMatchService::class, ['match' => null]),
			scopeValidator: $scope,
			recordWriter: $writer
		);

	}//end service()

	/**
	 * A saved-object stand-in whose getObject() returns $data.
	 *
	 * @param array<string, mixed> $data The payload.
	 *
	 * @return object The stand-in.
	 */
	private function saved(array $data): object {
		return new class($data) {

			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $data The payload.
			 */
			public function __construct(public readonly array $data) {
			}

			/**
			 * The payload.
			 *
			 * @return array<string, mixed> The payload.
			 */
			public function getObject(): array {
				return $this->data;
			}
		};

	}//end saved()
}//end class
