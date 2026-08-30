<?php

/**
 * Unit tests for SigningAuditService — migrate-signing-audit-to-or-audit.
 *
 * Verifies:
 *  1. Constructor accepts AuditTrailMapper + LoggerInterface.
 *  2. logEvent() calls createAuditTrailEntry() with the correct namespaced
 *     action type (docudesk.signing.{ACTION}) for all seven VALID_ACTIONS.
 *  3. logEvent() builds the context array with all required fields.
 *  4. getAuditTrail() queries the OR audit trail and returns entries
 *     filtered by objectUuid in chronological order.
 *  5. Invalid actions throw RuntimeException.
 *  6. VALID_ACTIONS still includes START (finding L2 — retained).
 *  7. rejectUpdate() and rejectDelete() are absent (finding #289 — retained).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-2.1
 * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTime;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningAuditService;
use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Unit tests for the migrated SigningAuditService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningAuditServiceTest extends TestCase {

	/**
	 * AuditTrailMapper mock.
	 *
	 * @var AuditTrailMapper|MockObject
	 */
	private AuditTrailMapper|MockObject $mapperMock;

	/**
	 * LoggerInterface mock.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $loggerMock;

	/**
	 * Service under test.
	 *
	 * @var SigningAuditService
	 */
	private SigningAuditService $service;

	/**
	 * SettingsService mock (resolves the real signing-request ObjectEntity).
	 *
	 * @var SettingsService|MockObject
	 */
	private SettingsService|MockObject $settingsServiceMock;

	/**
	 * IAppConfig mock.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $configMock;

	/**
	 * Set up mocks before each test.
	 *
	 * By default `settingsServiceMock->getObjectService()` returns null
	 * (PHPUnit's default stub for a nullable return type), so
	 * `resolveSigningRequestObject()` falls back to the uuid-only stub — the
	 * pre-existing tests below rely on that fallback shape unless a test
	 * explicitly configures a resolving ObjectService (REQ-DDSTR-006 tests).
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mapperMock = $this->createMock(AuditTrailMapper::class);
		$this->loggerMock = $this->createMock(LoggerInterface::class);

		$this->settingsServiceMock = $this->createMock(SettingsService::class);
		$this->configMock = $this->createMock(IAppConfig::class);
		$this->configMock->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				$map = [
					'signingRequest_register' => 'signing',
					'signingRequest_schema' => 'signingRequest',
				];
				return $map[$key] ?? $default;
			}
		);

		$this->service = new SigningAuditService(
			auditTrailMapper: $this->mapperMock,
			logger:           $this->loggerMock,
			settingsService:  $this->settingsServiceMock,
			config:           $this->configMock
		);

	}//end setUp()

	/**
	 * Helper: build a mock AuditTrail with objectUuid and creation time set.
	 *
	 * @param string $objectUuid The object UUID.
	 * @param string $action The action string.
	 * @param DateTime|null $created Optional creation time.
	 *
	 * @return AuditTrail|MockObject
	 */
	private function makeAuditTrail(
		string $objectUuid,
		string $action,
		?DateTime $created = null,
	): AuditTrail|MockObject {
		$trail = $this->createMock(AuditTrail::class);
		$trail->method('getObjectUuid')->willReturn($objectUuid);
		$trail->method('getAction')->willReturn($action);
		$trail->method('getCreated')->willReturn($created ?? new DateTime());
		$trail->method('jsonSerialize')->willReturn(
			[
				'objectUuid' => $objectUuid,
				'action' => $action,
				'created' => ($created ?? new DateTime())->format(\DateTimeInterface::ATOM),
			]
		);

		return $trail;
	}//end makeAuditTrail()

	// -------------------------------------------------------------------------
	// D-2.1: logEvent() calls createAuditTrailEntry() with the correct action
	// -------------------------------------------------------------------------

	/**
	 * logEvent() calls createAuditTrailEntry() exactly once per call with the
	 * namespaced action type docudesk.signing.{ACTION}.
	 *
	 * @return void
	 */
	public function testLogEventCallsCreateAuditTrailEntryOnce(): void {
		$returnedTrail = $this->createMock(AuditTrail::class);
		$returnedTrail->method('jsonSerialize')->willReturn(['action' => 'docudesk.signing.SIGNED']);

		$capturedAction = null;
		$capturedContext = null;

		$this->mapperMock
			->expects($this->once())
			->method('createAuditTrailEntry')
			->willReturnCallback(
				function (ObjectEntity $obj, string $action, array $context) use (&$capturedAction, &$capturedContext, $returnedTrail): AuditTrail {
					$capturedAction = $action;
					$capturedContext = $context;
					return $returnedTrail;
				}
			);

		$this->service->logEvent(
			signingRequestId: 'sign-001',
			action:           'SIGNED',
			actorUserId:      'user1',
			actorDisplayName: 'User One',
			ipAddress:        '1.2.3.4',
			signatureLevel:   'advanced',
			provider:         'NativeSigningProvider'
		);

		$this->assertSame('docudesk.signing.SIGNED', $capturedAction);
		$this->assertSame('sign-001', $capturedContext['signRequestId']);
		$this->assertSame('user1', $capturedContext['actorUserId']);
		$this->assertSame('User One', $capturedContext['actorDisplayName']);
		$this->assertSame('1.2.3.4', $capturedContext['ipAddress']);
		$this->assertSame('advanced', $capturedContext['signatureLevel']);
		$this->assertSame('NativeSigningProvider', $capturedContext['provider']);

	}//end testLogEventCallsCreateAuditTrailEntryOnce()

	/**
	 * logEvent() passes the signingRequestId as the ObjectEntity UUID so that
	 * objectUuid is set correctly on the audit trail entry.
	 *
	 * @return void
	 */
	public function testLogEventSetsCorrectObjectUuidOnStub(): void {
		$returnedTrail = $this->createMock(AuditTrail::class);
		$returnedTrail->method('jsonSerialize')->willReturn([]);

		$capturedObject = null;

		$this->mapperMock
			->method('createAuditTrailEntry')
			->willReturnCallback(
				function (ObjectEntity $obj) use (&$capturedObject, $returnedTrail): AuditTrail {
					$capturedObject = $obj;
					return $returnedTrail;
				}
			);

		$this->service->logEvent(
			signingRequestId: 'sign-uuid-42',
			action:           'CREATED',
			actorUserId:      'u',
			actorDisplayName: 'U',
			ipAddress:        '0.0.0.0'
		);

		$this->assertNotNull($capturedObject);
		$this->assertSame('sign-uuid-42', $capturedObject->getUuid());

	}//end testLogEventSetsCorrectObjectUuidOnStub()

	/**
	 * All seven VALID_ACTIONS produce the correct namespaced action type when
	 * passed to logEvent().
	 *
	 * @return void
	 */
	public function testAllValidActionsProduceNamespacedType(): void {
		$actions = ['CREATED', 'SIGNED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'COMPLETED', 'VIEWED'];

		foreach ($actions as $action) {
			$capturedAction = null;

			$trail = $this->createMock(AuditTrail::class);
			$trail->method('jsonSerialize')->willReturn([]);

			$this->mapperMock
				->expects($this->once())
				->method('createAuditTrailEntry')
				->willReturnCallback(
					function (ObjectEntity $obj, string $act) use (&$capturedAction, $trail): AuditTrail {
						$capturedAction = $act;
						return $trail;
					}
				);

			$this->service->logEvent(
				signingRequestId: 'req',
				action:           $action,
				actorUserId:      'u',
				actorDisplayName: 'U',
				ipAddress:        '0.0.0.0'
			);

			$this->assertSame('docudesk.signing.' . $action, $capturedAction, "Action type mismatch for $action");

			// Re-create mock for next iteration.
			$this->mapperMock = $this->createMock(AuditTrailMapper::class);
			$this->service = new SigningAuditService(
				auditTrailMapper: $this->mapperMock,
				logger:           $this->loggerMock,
				settingsService:  $this->settingsServiceMock,
				config:           $this->configMock
			);
		}//end foreach

	}//end testAllValidActionsProduceNamespacedType()

	/**
	 * logEvent() throws RuntimeException for an unrecognised action.
	 *
	 * @return void
	 */
	public function testLogEventThrowsForInvalidAction(): void {
		$this->mapperMock
			->expects($this->never())
			->method('createAuditTrailEntry');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid audit action: UNKNOWN');

		$this->service->logEvent(
			signingRequestId: 'req',
			action:           'UNKNOWN',
			actorUserId:      'u',
			actorDisplayName: 'U',
			ipAddress:        '0.0.0.0'
		);

	}//end testLogEventThrowsForInvalidAction()

	// -------------------------------------------------------------------------
	// REQ-DDSTR-006: audit entries bind to the REAL signing-request object
	// -------------------------------------------------------------------------

	/**
	 * logEvent() resolves the REAL signing-request ObjectEntity via
	 * ObjectService and passes IT (not a uuid-only stub) to
	 * createAuditTrailEntry() — the entry carries real register/schema/id
	 * linkage (signing-trust-rebuild REQ-DDSTR-006, closing the #289 residual
	 * where every entry anchored to a uuid-only stub).
	 *
	 * @return void
	 */
	public function testLogEventResolvesRealObjectEntity(): void {
		$realEntity = $this->createMock(ObjectEntity::class);
		$realEntity->method('getUuid')->willReturn('sign-001');
		$realEntity->method('getId')->willReturn(42);
		$realEntity->method('getRegister')->willReturn('signing');
		$realEntity->method('getSchema')->willReturn('signingRequest');

		$objectService = $this->getMockBuilder(className: ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['find'])
			->getMock();
		$objectService->expects($this->once())
			->method('find')
			->with(
				$this->equalTo('sign-001'),
				$this->anything(),
				$this->anything(),
				$this->equalTo('signing'),
				$this->equalTo('signingRequest')
			)
			->willReturn($realEntity);

		$this->settingsServiceMock->method('getObjectService')->willReturn($objectService);

		$returnedTrail = $this->createMock(AuditTrail::class);
		$returnedTrail->method('jsonSerialize')->willReturn([]);

		$capturedObject = null;
		$this->mapperMock->method('createAuditTrailEntry')->willReturnCallback(
			function (ObjectEntity $obj) use (&$capturedObject, $returnedTrail): AuditTrail {
				$capturedObject = $obj;
				return $returnedTrail;
			}
		);

		$this->service->logEvent(
			signingRequestId: 'sign-001',
			action:           'SIGNED',
			actorUserId:      'u',
			actorDisplayName: 'U',
			ipAddress:        '0.0.0.0'
		);

		$this->assertSame($realEntity, $capturedObject, 'logEvent() must bind to the REAL resolved ObjectEntity.');

	}//end testLogEventResolvesRealObjectEntity()

	/**
	 * When the signing-request has vanished mid-flight (find() returns null),
	 * logEvent() STILL writes the entry — with the uuid-only fallback stub —
	 * and logs a warning. An unlinked audit entry is acceptable; a dropped one
	 * is not (signing-trust-rebuild REQ-DDSTR-006).
	 *
	 * @return void
	 */
	public function testLogEventFallsBackToStubWithWarningWhenRequestVanished(): void {
		$objectService = $this->getMockBuilder(className: ObjectService::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->onlyMethods(['find'])
			->getMock();
		$objectService->method('find')->willReturn(null);
		$this->settingsServiceMock->method('getObjectService')->willReturn($objectService);

		$this->loggerMock->expects($this->once())->method('warning');

		$returnedTrail = $this->createMock(AuditTrail::class);
		$returnedTrail->method('jsonSerialize')->willReturn([]);

		$capturedObject = null;
		$this->mapperMock->method('createAuditTrailEntry')->willReturnCallback(
			function (ObjectEntity $obj) use (&$capturedObject, $returnedTrail): AuditTrail {
				$capturedObject = $obj;
				return $returnedTrail;
			}
		);

		// Must NOT throw — the entry is still written.
		$this->service->logEvent(
			signingRequestId: 'vanished-request',
			action:           'CANCELLED',
			actorUserId:      'u',
			actorDisplayName: 'U',
			ipAddress:        '0.0.0.0'
		);

		$this->assertNotNull($capturedObject);
		$this->assertSame('vanished-request', $capturedObject->getUuid());

	}//end testLogEventFallsBackToStubWithWarningWhenRequestVanished()

	// -------------------------------------------------------------------------
	// D-3.1: getAuditTrail() queries OR audit trail and filters by objectUuid
	// -------------------------------------------------------------------------

	/**
	 * getAuditTrail() pushes an objectUuid-scoped filter into findAll() —
	 * scoped at the query layer, not a PHP-side post-filter — and returns the
	 * (already-scoped) entries sorted chronologically (signing-trust-rebuild
	 * REQ-DDSTR-007, closing the #289 unbounded-scan residual).
	 *
	 * @return void
	 */
	public function testGetAuditTrailFiltersAndSortsChronologically(): void {
		$older = $this->makeAuditTrail('sign-003', 'docudesk.signing.CREATED', new DateTime('2026-01-01 10:00:00'));
		$newer = $this->makeAuditTrail('sign-003', 'docudesk.signing.SIGNED', new DateTime('2026-01-02 10:00:00'));

		// The mock simulates OR's bounded query: it only returns rows for the
		// requested object_uuid filter — proving the scope is pushed into the
		// mapper call, not recovered by client-side filtering.
		$this->mapperMock
			->expects($this->once())
			->method('findAll')
			->willReturnCallback(
				function (?int $limit, ?int $offset, ?array $filters) use ($older, $newer): array {
					if (($filters['object_uuid'] ?? null) === 'sign-003') {
						return [$newer, $older];
					}

					return [];
				}
			);

		$result = $this->service->getAuditTrail('sign-003');

		$this->assertCount(2, $result, 'Only the object-scoped entries should be returned.');
		$this->assertSame('docudesk.signing.CREATED', $result[0]['action'], 'Oldest entry must come first.');
		$this->assertSame('docudesk.signing.SIGNED', $result[1]['action']);

	}//end testGetAuditTrailFiltersAndSortsChronologically()

	/**
	 * getAuditTrail() does NOT scan unrelated requests: a query scoped to
	 * request A never sees entries that only match by chance (e.g. a shared
	 * action type) on a DIFFERENT object_uuid.
	 *
	 * @return void
	 */
	public function testGetAuditTrailDoesNotLeakUnrelatedRequests(): void {
		$this->mapperMock
			->method('findAll')
			->willReturnCallback(
				function (?int $limit, ?int $offset, ?array $filters): array {
					// Simulate OR actually scoping by object_uuid: an
					// unrelated request's entries are never returned for A's query.
					if (($filters['object_uuid'] ?? null) === 'request-a') {
						return [$this->makeAuditTrail('request-a', 'docudesk.signing.SIGNED')];
					}

					return [$this->makeAuditTrail('request-b', 'docudesk.signing.SIGNED')];
				}
			);

		$result = $this->service->getAuditTrail('request-a');

		$this->assertCount(1, $result);
		$this->assertSame('request-a', $result[0]['objectUuid'] ?? null);

	}//end testGetAuditTrailDoesNotLeakUnrelatedRequests()

	/**
	 * getAuditTrail() returns an empty array when no entries match the UUID.
	 *
	 * @return void
	 */
	public function testGetAuditTrailReturnsEmptyArrayWhenNoMatch(): void {
		$this->mapperMock
			->method('findAll')
			->willReturn([]);

		$result = $this->service->getAuditTrail('non-existent-uuid');

		$this->assertSame([], $result);

	}//end testGetAuditTrailReturnsEmptyArrayWhenNoMatch()

	/**
	 * getAuditTrail() passes an action filter containing all docudesk.signing.*
	 * action types to findAll() so the query is bounded to signing events.
	 *
	 * @return void
	 */
	public function testGetAuditTrailPassesActionFilterToFindAll(): void {
		$capturedFilters = null;

		$this->mapperMock
			->expects($this->once())
			->method('findAll')
			->willReturnCallback(
				function (?int $limit, ?int $offset, ?array $filters) use (&$capturedFilters): array {
					$capturedFilters = $filters;
					return [];
				}
			);

		$this->service->getAuditTrail('any-uuid');

		$this->assertNotNull($capturedFilters);
		$this->assertArrayHasKey('action', $capturedFilters);
		$this->assertArrayHasKey('object_uuid', $capturedFilters, 'The query must be scoped by object_uuid (REQ-DDSTR-007).');
		$this->assertSame('any-uuid', $capturedFilters['object_uuid']);

		// Every VALID_ACTION must appear in the filter string as docudesk.signing.{ACTION}.
		$actionFilter = $capturedFilters['action'];
		foreach (['CREATED', 'SIGNED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'COMPLETED', 'VIEWED'] as $action) {
			$this->assertStringContainsString(
				'docudesk.signing.' . $action,
				$actionFilter,
				"Action filter must include docudesk.signing.$action"
			);
		}

	}//end testGetAuditTrailPassesActionFilterToFindAll()

	// -------------------------------------------------------------------------
	// Regression: interface invariants (finding #289, finding L2)
	// -------------------------------------------------------------------------

	/**
	 * Dead-code rejectUpdate() must not exist (finding #289 — retained check).
	 *
	 * @return void
	 */
	public function testRejectUpdateMethodRemoved(): void {
		$ref = new ReflectionClass(SigningAuditService::class);
		$this->assertFalse(
			$ref->hasMethod('rejectUpdate'),
			'SigningAuditService::rejectUpdate() should be absent; immutability is OR-native.'
		);

	}//end testRejectUpdateMethodRemoved()

	/**
	 * Dead-code rejectDelete() must not exist (finding #289 — retained check).
	 *
	 * @return void
	 */
	public function testRejectDeleteMethodRemoved(): void {
		$ref = new ReflectionClass(SigningAuditService::class);
		$this->assertFalse(
			$ref->hasMethod('rejectDelete'),
			'SigningAuditService::rejectDelete() should be absent; immutability is OR-native.'
		);

	}//end testRejectDeleteMethodRemoved()

	/**
	 * VALID_ACTIONS must include START (finding L2 — signing-session initiation).
	 *
	 * @return void
	 */
	public function testValidActionsIncludesStart(): void {
		$ref = new ReflectionClass(SigningAuditService::class);
		$constant = $ref->getReflectionConstant('VALID_ACTIONS');
		$this->assertNotFalse($constant, 'VALID_ACTIONS constant must exist.');
		$this->assertContains(
			'START',
			$constant->getValue(),
			'VALID_ACTIONS must include START (finding L2).'
		);

	}//end testValidActionsIncludesStart()

	/**
	 * logEvent() and getAuditTrail() public surface must still be present.
	 *
	 * @return void
	 */
	public function testPublicSurfaceStillPresent(): void {
		$ref = new ReflectionClass(SigningAuditService::class);
		$this->assertTrue($ref->hasMethod('logEvent'), 'logEvent() must be present.');
		$this->assertTrue($ref->hasMethod('getAuditTrail'), 'getAuditTrail() must be present.');

	}//end testPublicSurfaceStillPresent()

}//end class
