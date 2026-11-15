<?php

/**
 * Coverage tests for PolicyCrudService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use InvalidArgumentException;
use JsonSerializable;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\PolicyCrudService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The policy CRUD layer: listing and lookup through OpenRegister's slug-aware
 * calls, the per-surface permission gates, payload shaping and the standing
 * consent scope guard.
 *
 * @covers \OCA\Filinq\Service\PolicyCrudService
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyCrudServiceCoverageTest extends TestCase {

	/**
	 * OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * Consent service (scope validation).
	 *
	 * @var ConsentService&MockObject
	 */
	private ConsentService&MockObject $consentService;

	/**
	 * Group manager.
	 *
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groupManager;

	/**
	 * User session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Build the mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->objectService = $this->createMock(ObjectService::class);
		$this->consentService = $this->createMock(ConsentService::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Build the service under test.
	 *
	 * @return PolicyCrudService
	 */
	private function service(): PolicyCrudService {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($this->objectService);

		return new PolicyCrudService(
			$settings,
			$this->consentService,
			$this->groupManager,
			$this->userSession,
			$this->logger
		);

	}//end service()

	/**
	 * Log in a user with the given uid.
	 *
	 * @param string $uid The user id.
	 *
	 * @return void
	 */
	private function loginAs(string $uid): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$this->userSession->method('getUser')->willReturn($user);

	}//end loginAs()

	/**
	 * Log in a non-admin member of exactly the given group.
	 *
	 * @param string $group The group the user belongs to.
	 *
	 * @return void
	 */
	private function loginAsMemberOf(string $group): void {
		$this->loginAs('officer');
		$this->groupManager->method('isAdmin')->with('officer')->willReturn(false);
		$this->groupManager->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $g): bool => $uid === 'officer' && $g === $group
		);

	}//end loginAsMemberOf()

	/**
	 * A JSON-serialisable result row, as OpenRegister entities are.
	 *
	 * @param array<string, mixed> $data The serialised payload.
	 *
	 * @return JsonSerializable
	 */
	private function entity(array $data): JsonSerializable {
		return new class($data) implements JsonSerializable {
			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $data The payload.
			 *
			 * @return void
			 */
			public function __construct(private array $data) {
			}//end __construct()

			/**
			 * Serialise.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return $this->data;
			}//end jsonSerialize()
		};

	}//end entity()

	/**
	 * A saved-object entity returning the given payload.
	 *
	 * @param array<string, mixed> $data The payload.
	 *
	 * @return ObjectEntity
	 */
	private function saved(array $data): ObjectEntity {
		$entity = new ObjectEntity();
		$entity->setObject($data);
		return $entity;

	}//end saved()

	/**
	 * Prohibitions are listed via the slug-aware search, serialising entities
	 * and keeping arrays while dropping anything else.
	 *
	 * @return void
	 */
	public function testListProhibitionsSerialisesEntitiesAndKeepsArrays(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'publicationProhibition', $this->anything(), false, false)
			->willReturn([$this->entity(['id' => 'a']), ['id' => 'b'], 'garbage', 42]);

		$this->assertSame([['id' => 'a'], ['id' => 'b']], $this->service()->listProhibitions());

	}//end testListProhibitionsSerialisesEntitiesAndKeepsArrays()

	/**
	 * A count (int) result from the search yields an empty list.
	 *
	 * @return void
	 */
	public function testListProhibitionsReturnsEmptyOnIntegerResult(): void {
		$this->objectService->method('searchObjectsBySlug')->willReturn(7);

		$this->assertSame([], $this->service()->listProhibitions());

	}//end testListProhibitionsReturnsEmptyOnIntegerResult()

	/**
	 * Only `scope: entity` consents are standing consents; a missing scope
	 * means document.
	 *
	 * @return void
	 */
	public function testListStandingConsentsKeepsOnlyEntityScope(): void {
		$this->objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'publicationConsent', $this->anything(), false, false)
			->willReturn(
				[
					['id' => '1', 'scope' => 'document'],
					['id' => '2', 'scope' => 'entity'],
					['id' => '3'],
					$this->entity(['id' => '4', 'scope' => 'entity']),
				]
			);

		$this->assertSame(
			[['id' => '2', 'scope' => 'entity'], ['id' => '4', 'scope' => 'entity']],
			$this->service()->listStandingConsents()
		);

	}//end testListStandingConsentsKeepsOnlyEntityScope()

	/**
	 * A prohibition lookup serialises the found entity.
	 *
	 * @return void
	 */
	public function testGetProhibitionSerialisesTheFoundEntity(): void {
		$this->objectService->expects($this->once())
			->method('find')
			->with('u-1', 'filinq', 'publicationProhibition', false, false)
			->willReturn($this->entity(['id' => 'u-1', 'active' => true]));

		$this->assertSame(['id' => 'u-1', 'active' => true], $this->service()->getProhibition('u-1'));

	}//end testGetProhibitionSerialisesTheFoundEntity()

	/**
	 * A lookup that finds nothing yields null.
	 *
	 * @return void
	 */
	public function testGetProhibitionReturnsNullWhenNotFound(): void {
		$this->objectService->method('find')->willReturn(null);

		$this->assertNull($this->service()->getProhibition('missing'));

	}//end testGetProhibitionReturnsNullWhenNotFound()

	/**
	 * A non-serialisable lookup result is cast to an array.
	 *
	 * @return void
	 */
	public function testGetProhibitionCastsAPlainResult(): void {
		$this->objectService->method('find')->willReturn(['id' => 'p', 'entityText' => 'Jan']);

		$this->assertSame(['id' => 'p', 'entityText' => 'Jan'], $this->service()->getProhibition('p'));

	}//end testGetProhibitionCastsAPlainResult()

	/**
	 * A standing-consent lookup returns the record only when its scope is entity.
	 *
	 * @return void
	 */
	public function testGetStandingConsentAssertsEntityScope(): void {
		$this->objectService->method('find')->willReturnMap(
			[
				['e', 'filinq', 'publicationConsent', false, false, true, true, ['id' => 'e', 'scope' => 'entity']],
				['d', 'filinq', 'publicationConsent', false, false, true, true, ['id' => 'd', 'scope' => 'document']],
				['n', 'filinq', 'publicationConsent', false, false, true, true, ['id' => 'n']],
				['x', 'filinq', 'publicationConsent', false, false, true, true, null],
			]
		);

		$service = $this->service();
		$this->assertSame(['id' => 'e', 'scope' => 'entity'], $service->getStandingConsent('e'));
		$this->assertNull($service->getStandingConsent('d'));
		$this->assertNull($service->getStandingConsent('n'));
		$this->assertNull($service->getStandingConsent('x'));

	}//end testGetStandingConsentAssertsEntityScope()

	/**
	 * Creating a prohibition strips framework params, defaults `active` to
	 * true and returns the saved object payload.
	 *
	 * @return void
	 */
	public function testCreateProhibitionStripsParamsAndDefaultsActive(): void {
		$this->loginAsMemberOf('filinq-prohibition-admins');

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(
				['entityText' => 'Jan', 'active' => true],
				'filinq',
				'publicationProhibition',
				null,
				false,
				false
			)
			->willReturn($this->saved(['id' => 'new', 'entityText' => 'Jan']));

		$result = $this->service()->createProhibition(
			['entityText' => 'Jan', '_route' => 'r', '_method' => 'POST', 'id' => 'x', 'uuid' => 'y']
		);

		$this->assertSame(['id' => 'new', 'entityText' => 'Jan'], $result);

	}//end testCreateProhibitionStripsParamsAndDefaultsActive()

	/**
	 * An explicit `active: false` survives creation, and admins bypass the
	 * group check entirely.
	 *
	 * @return void
	 */
	public function testCreateProhibitionKeepsExplicitInactiveForAdmin(): void {
		$this->loginAs('admin');
		$this->groupManager->expects($this->once())->method('isAdmin')->with('admin')->willReturn(true);
		$this->groupManager->expects($this->never())->method('isInGroup');

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['active' => false], 'filinq', 'publicationProhibition')
			->willReturn($this->saved(['active' => false]));

		$this->assertSame(['active' => false], $this->service()->createProhibition(['active' => false]));

	}//end testCreateProhibitionKeepsExplicitInactiveForAdmin()

	/**
	 * Without an authenticated user a prohibition write is refused.
	 *
	 * @return void
	 */
	public function testCreateProhibitionRequiresAUser(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Prohibition create requires an authenticated user.');

		$this->service()->createProhibition(['entityText' => 'Jan']);

	}//end testCreateProhibitionRequiresAUser()

	/**
	 * A non-admin outside the prohibition group is refused, even when in the
	 * standing-consent group.
	 *
	 * @return void
	 */
	public function testUpdateProhibitionRequiresProhibitionGroup(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Prohibition update requires membership in the "filinq-prohibition-admins" group.');

		$this->service()->updateProhibition('u-1', ['entityText' => 'Jan']);

	}//end testUpdateProhibitionRequiresProhibitionGroup()

	/**
	 * Updating a prohibition saves under the given uuid without injecting
	 * `active`.
	 *
	 * @return void
	 */
	public function testUpdateProhibitionSavesUnderTheUuid(): void {
		$this->loginAsMemberOf('filinq-prohibition-admins');

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(['entityText' => 'Piet'], 'filinq', 'publicationProhibition', 'u-9', false, false)
			->willReturn($this->saved(['id' => 'u-9', 'entityText' => 'Piet']));

		$this->assertSame(
			['id' => 'u-9', 'entityText' => 'Piet'],
			$this->service()->updateProhibition('u-9', ['entityText' => 'Piet', 'id' => 'other'])
		);

	}//end testUpdateProhibitionSavesUnderTheUuid()

	/**
	 * A failing save is logged with its context and rethrown unchanged.
	 *
	 * @return void
	 */
	public function testSaveFailureIsLoggedAndRethrown(): void {
		$this->loginAsMemberOf('filinq-prohibition-admins');
		$failure = new Exception('db down');
		$this->objectService->method('saveObject')->willThrowException($failure);

		$this->logger->expects($this->once())
			->method('error')
			->with(
				'PolicyCrudService: save failed',
				['schema' => 'publicationProhibition', 'uuid' => 'u-2', 'error' => 'db down']
			);

		try {
			$this->service()->updateProhibition('u-2', ['entityText' => 'Jan']);
			$this->fail('Expected the save failure to be rethrown.');
		} catch (Exception $e) {
			$this->assertSame($failure, $e);
		}

	}//end testSaveFailureIsLoggedAndRethrown()

	/**
	 * Deleting a prohibition is gated, then reaches deleteObject with RBAC off.
	 *
	 * @return void
	 */
	public function testDeleteProhibitionCallsDeleteObject(): void {
		$this->loginAsMemberOf('filinq-prohibition-admins');

		$this->objectService->expects($this->once())
			->method('deleteObject')
			->with('u-3', 'filinq', 'publicationProhibition', false, false);

		$this->service()->deleteProhibition('u-3');

	}//end testDeleteProhibitionCallsDeleteObject()

	/**
	 * A refused delete never reaches OpenRegister.
	 *
	 * @return void
	 */
	public function testDeleteProhibitionRefusedForOutsider(): void {
		$this->loginAsMemberOf('some-other-group');
		$this->objectService->expects($this->never())->method('deleteObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Prohibition delete requires membership');

		$this->service()->deleteProhibition('u-3');

	}//end testDeleteProhibitionRefusedForOutsider()

	/**
	 * Creating a standing consent forces scope=entity, defaults active, validates
	 * the payload and saves it.
	 *
	 * @return void
	 */
	public function testCreateStandingConsentForcesEntityScopeAndValidates(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$expected = ['matchRules' => [['type' => 'exact']], 'scope' => 'entity', 'active' => true];

		$this->consentService->expects($this->once())
			->method('validatePublicationConsentData')
			->with($expected);
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with($expected, 'filinq', 'publicationConsent', null, false, false)
			->willReturn($this->saved(['id' => 'sc-1']));

		$this->assertSame(
			['id' => 'sc-1'],
			$this->service()->createStandingConsent(
				['matchRules' => [['type' => 'exact']], 'scope' => 'document', '_route' => 'x']
			)
		);

	}//end testCreateStandingConsentForcesEntityScopeAndValidates()

	/**
	 * A validation failure prevents the save.
	 *
	 * @return void
	 */
	public function testCreateStandingConsentValidationFailureStopsTheSave(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$this->consentService->method('validatePublicationConsentData')
			->willThrowException(new InvalidArgumentException('matchRules required'));
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('matchRules required');

		$this->service()->createStandingConsent(['consentMethod' => 'x']);

	}//end testCreateStandingConsentValidationFailureStopsTheSave()

	/**
	 * Standing-consent writes need an authenticated user.
	 *
	 * @return void
	 */
	public function testCreateStandingConsentRequiresAUser(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->consentService->expects($this->never())->method('validatePublicationConsentData');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Standing-consent create requires an authenticated user.');

		$this->service()->createStandingConsent([]);

	}//end testCreateStandingConsentRequiresAUser()

	/**
	 * A prohibition admin is not thereby a standing-consent admin.
	 *
	 * @return void
	 */
	public function testCreateStandingConsentRequiresStandingConsentGroup(): void {
		$this->loginAsMemberOf('filinq-prohibition-admins');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Standing-consent create requires membership in the "filinq-standing-consent-admins" group.');

		$this->service()->createStandingConsent([]);

	}//end testCreateStandingConsentRequiresStandingConsentGroup()

	/**
	 * Updating merges the change over the existing record, keeps scope=entity
	 * and saves under the uuid.
	 *
	 * @return void
	 */
	public function testUpdateStandingConsentMergesOverExisting(): void {
		$this->loginAs('admin');
		$this->groupManager->method('isAdmin')->willReturn(true);

		$this->objectService->expects($this->once())
			->method('find')
			->with('sc-1', 'filinq', 'publicationConsent', false, false)
			->willReturn(['id' => 'sc-1', 'scope' => 'entity', 'consentMethod' => 'mail', 'active' => true]);

		$expected = ['id' => 'sc-1', 'scope' => 'entity', 'consentMethod' => 'phone', 'active' => true];
		$this->consentService->expects($this->once())->method('validatePublicationConsentData')->with($expected);
		$this->objectService->expects($this->once())
			->method('saveObject')
			->with($expected, 'filinq', 'publicationConsent', 'sc-1', false, false)
			->willReturn($this->saved($expected));

		$this->assertSame(
			$expected,
			$this->service()->updateStandingConsent('sc-1', ['consentMethod' => 'phone', 'scope' => 'document'])
		);

	}//end testUpdateStandingConsentMergesOverExisting()

	/**
	 * Updating a consent that is not a standing consent is refused.
	 *
	 * @return void
	 */
	public function testUpdateStandingConsentRefusesUnknownRecord(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$this->objectService->method('find')->willReturn(['id' => 'd', 'scope' => 'document']);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Standing consent not found: d');

		$this->service()->updateStandingConsent('d', ['consentMethod' => 'x']);

	}//end testUpdateStandingConsentRefusesUnknownRecord()

	/**
	 * Deleting an existing standing consent reaches deleteObject.
	 *
	 * @return void
	 */
	public function testDeleteStandingConsentCallsDeleteObject(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$this->objectService->method('find')->willReturn(['id' => 'sc-2', 'scope' => 'entity']);

		$this->objectService->expects($this->once())
			->method('deleteObject')
			->with('sc-2', 'filinq', 'publicationConsent', false, false);

		$this->service()->deleteStandingConsent('sc-2');

	}//end testDeleteStandingConsentCallsDeleteObject()

	/**
	 * Deleting a missing standing consent throws and deletes nothing.
	 *
	 * @return void
	 */
	public function testDeleteStandingConsentRefusesMissingRecord(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$this->objectService->method('find')->willReturn(null);
		$this->objectService->expects($this->never())->method('deleteObject');

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Standing consent not found: gone');

		$this->service()->deleteStandingConsent('gone');

	}//end testDeleteStandingConsentRefusesMissingRecord()

	/**
	 * The public gate dispatches per surface: a prohibition admin passes the
	 * prohibition gate but fails the standing-consent gate.
	 *
	 * @return void
	 */
	public function testRequirePolicyPermissionDispatchesPerSurface(): void {
		$this->loginAsMemberOf('filinq-prohibition-admins');
		$service = $this->service();

		$service->requirePolicyPermission('prohibition', 'read');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Standing-consent read requires membership in the "filinq-standing-consent-admins" group.');

		$service->requirePolicyPermission('standingConsent', 'read');

	}//end testRequirePolicyPermissionDispatchesPerSurface()

	/**
	 * A standing-consent admin passes the standing-consent gate.
	 *
	 * @return void
	 */
	public function testRequirePolicyPermissionPassesStandingConsentMember(): void {
		$this->loginAsMemberOf('filinq-standing-consent-admins');
		$this->groupManager->expects($this->atLeastOnce())->method('isInGroup');

		$this->service()->requirePolicyPermission('standingConsent', 'update');

		$this->expectException(RuntimeException::class);
		$this->service()->requirePolicyPermission('prohibition', 'update');

	}//end testRequirePolicyPermissionPassesStandingConsentMember()

	/**
	 * An unknown surface is an error, never a silent pass.
	 *
	 * @return void
	 */
	public function testRequirePolicyPermissionRejectsUnknownSurface(): void {
		$this->userSession->expects($this->never())->method('getUser');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown policy surface: bogus');

		$this->service()->requirePolicyPermission('bogus', 'read');

	}//end testRequirePolicyPermissionRejectsUnknownSurface()
}//end class
