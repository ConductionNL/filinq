<?php

/**
 * Unit tests for OrganisationSettingsService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Exception\OrganisationSettingsDeniedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EffectiveSettings;
use OCA\Filinq\Service\OrganisationSettingsService;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The organisation settings: who may write them, what is stored, and the fail-closed paths.
 *
 * @covers \OCA\Filinq\Service\OrganisationSettingsService
 * @uses   \OCA\Filinq\Service\OrganisationSettingsInput
 * @uses   \OCA\Filinq\Exception\OrganisationSettingsDeniedException
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */
class OrganisationSettingsServiceTest extends TestCase {

	private const ORG_A = '11111111-1111-4111-8111-111111111111';

	private const ORG_B = '22222222-2222-4222-8222-222222222222';

	private const HUISSTIJL = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * The group manager.
	 *
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groups;

	/**
	 * The effective settings.
	 *
	 * @var EffectiveSettings&MockObject
	 */
	private EffectiveSettings&MockObject $effective;

	/**
	 * What OpenRegister's organisation service answers.
	 *
	 * @var object
	 */
	private object $organisations;

	/**
	 * Fresh doubles: user "alice", member of organisation A, not an admin.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects   = $this->createMock(ObjectService::class);
		$this->session   = $this->createMock(IUserSession::class);
		$this->groups    = $this->createMock(IGroupManager::class);
		$this->effective = $this->createMock(EffectiveSettings::class);
		$this->effective->method('instanceConsentPeriodDays')->willReturn(28);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->session->method('getUser')->willReturn($user);

		$this->organisations = new class(self::ORG_A) {
			/** @var array<int, array{0: string, 1: string|null}> */
			public array $adminChecks = [];
			public bool $orgAdmin = false;
			public ?\Throwable $failure = null;

			public function __construct(public ?string $active) {
			}

			public function getActiveOrganisation(): ?object {
				if ($this->failure !== null) {
					throw $this->failure;
				}

				if ($this->active === null) {
					return null;
				}

				// OpenRegister's Organisation is a Nextcloud Entity: its
				// getters answer through __call, so method_exists() is false.
				return new class($this->active) {
					public function __construct(private string $uuid) {
					}

					/**
					 * The getters, answered through __call.
					 *
					 * @param string       $method    The getter.
					 * @param array<mixed> $arguments Unused.
					 *
					 * @return mixed
					 */
					public function __call(string $method, array $arguments): mixed {
						return match ($method) {
							'getUuid' => $this->uuid,
							'getName' => 'Demostad',
							default   => null,
						};
					}
				};
			}

			public function isOrganisationAdmin(string $uuid, ?string $userId = null): bool {
				$this->adminChecks[] = [$uuid, $userId];
				return $this->orgAdmin;
			}
		};
	}//end setUp()

	/**
	 * The service under test.
	 *
	 * @return OrganisationSettingsService
	 */
	private function service(): OrganisationSettingsService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\OrganisationService')->willReturn($this->organisations);

		return new OrganisationSettingsService(
			objectResolver: $resolver,
			container: $container,
			userSession: $this->session,
			groupManager: $this->groups,
			effective: $this->effective,
			logger: $this->createMock(LoggerInterface::class),
		);
	}//end service()

	/**
	 * A stored row as OpenRegister returns it.
	 *
	 * @param string               $organisation Its organisation stamp.
	 * @param array<string, mixed> $fields       Its fields.
	 *
	 * @return array<string, mixed>
	 */
	private function row(string $organisation, array $fields = []): array {
		return $fields + ['name' => 'Demostad', '@self' => ['id' => 'stored-uuid', 'organisation' => $organisation]];
	}//end row()

	/**
	 * A member sees the organisation and its overrides, but may not edit.
	 *
	 * @return void
	 */
	public function testAMemberReadsTheSettingsButMayNotEdit(): void {
		$this->objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'organisationSettings', [])
			->willReturn([$this->row(self::ORG_A, ['consentPeriodDays' => 42])]);

		$view = $this->service()->view();

		$this->assertSame(['uuid' => self::ORG_A, 'name' => 'Demostad'], $view['organisation']);
		$this->assertFalse($view['canEdit']);
		$this->assertSame(42, $view['settings']['consentPeriodDays']);
		$this->assertSame('stored-uuid', $view['settings']['uuid']);
		$this->assertNull($view['settings']['defaultHuisstijl']);
		$this->assertSame(['consentPeriodDays' => 28], $view['instanceDefaults']);
		$this->assertSame([[self::ORG_A, 'alice']], $this->organisations->adminChecks);
	}//end testAMemberReadsTheSettingsButMayNotEdit()

	/**
	 * Without an active organisation the view is empty and nothing is read.
	 *
	 * @return void
	 */
	public function testNoActiveOrganisationIsAnEmptyState(): void {
		$this->organisations->active = null;
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		$view = $this->service()->view();

		$this->assertNull($view['organisation']);
		$this->assertNull($view['settings']);
		$this->assertFalse($view['canEdit']);
	}//end testNoActiveOrganisationIsAnEmptyState()

	/**
	 * A failing organisation lookup is an empty state too, not every organisation.
	 *
	 * @return void
	 */
	public function testAFailingOrganisationLookupIsAnEmptyState(): void {
		$this->organisations->failure = new RuntimeException('down');
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		$this->assertNull($this->service()->view()['organisation']);
	}//end testAFailingOrganisationLookupIsAnEmptyState()

	/**
	 * A regular member's write is refused before anything is read or stored.
	 *
	 * @return void
	 */
	public function testAMemberMayNotWrite(): void {
		$this->objects->expects($this->never())->method('saveObject');
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		$this->expectException(OrganisationSettingsDeniedException::class);
		$this->service()->save(input: ['consentPeriodDays' => 42]);
	}//end testAMemberMayNotWrite()

	/**
	 * Without an active organisation a write is refused, even for an instance admin.
	 *
	 * @return void
	 */
	public function testNoActiveOrganisationRefusesAWrite(): void {
		$this->organisations->active = null;
		$this->groups->method('isAdmin')->willReturn(true);
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(OrganisationSettingsDeniedException::class);
		$this->service()->save(input: ['consentPeriodDays' => 42]);
	}//end testNoActiveOrganisationRefusesAWrite()

	/**
	 * An organisation admin creates the object; the organisation filter stays on and no stamp is sent.
	 *
	 * @return void
	 */
	public function testAnOrganisationAdminCreatesTheObject(): void {
		$this->organisations->orgAdmin = true;
		$saved = [];
		$this->objects->method('searchObjectsBySlug')->willReturnCallback(
			static function () use (&$saved): array {
				return $saved === [] ? [] : [$saved];
			}
		);
		$this->objects->expects($this->once())->method('saveObject')->willReturnCallback(
			function (array $object, ObjectSaveOptions $options, bool $_rbac = true, bool $_multitenancy = true) use (&$saved) {
				$this->assertFalse($_rbac);
				$this->assertTrue($_multitenancy);
				$this->assertNull($options->uuid);
				$this->assertSame('filinq', $options->target->register);
				$this->assertSame('organisationSettings', $options->target->schema);
				$this->assertSame(
					['name' => 'Demostad', 'consentPeriodDays' => 42, 'anonymizationProfile' => null, 'defaultHuisstijl' => null, 'notes' => null],
					$object
				);
				$saved = $object + ['@self' => ['id' => 'new-uuid', 'organisation' => self::ORG_A]];
				return $this->createMock(\OCA\OpenRegister\Contract\ObjectEntityInterface::class);
			}
		);

		$view = $this->service()->save(input: ['consentPeriodDays' => '42', '@self' => ['organisation' => self::ORG_B], 'organisation' => self::ORG_B]);

		$this->assertTrue($view['canEdit']);
		$this->assertSame(42, $view['settings']['consentPeriodDays']);
		$this->assertSame('new-uuid', $view['settings']['uuid']);
	}//end testAnOrganisationAdminCreatesTheObject()

	/**
	 * An instance admin updates the existing object by its uuid, without an organisation-admin lookup.
	 *
	 * @return void
	 */
	public function testAnInstanceAdminUpdatesTheExistingObject(): void {
		$this->groups->method('isAdmin')->with('alice')->willReturn(true);
		$this->objects->method('searchObjectsBySlug')->willReturn([$this->row(self::ORG_A, ['consentPeriodDays' => 42])]);
		$this->objects->expects($this->once())->method('saveObject')->with(
			$this->callback(static fn (array $object): bool => $object['consentPeriodDays'] === null && $object['notes'] === 'Back to the default.'),
			$this->callback(static fn (ObjectSaveOptions $options): bool => $options->uuid === 'stored-uuid'),
			false
		);

		$this->service()->save(input: ['consentPeriodDays' => '', 'notes' => ' Back to the default. ']);

		$this->assertSame([], $this->organisations->adminChecks);
	}//end testAnInstanceAdminUpdatesTheExistingObject()

	/**
	 * A visible object stamped with another organisation is never written over.
	 *
	 * @return void
	 */
	public function testAnObjectOfAnotherOrganisationIsNotWrittenOver(): void {
		$this->groups->method('isAdmin')->willReturn(true);
		$this->objects->method('searchObjectsBySlug')->willReturn([$this->row(self::ORG_B)]);
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('another organisation');
		$this->service()->save(input: ['consentPeriodDays' => 42]);
	}//end testAnObjectOfAnotherOrganisationIsNotWrittenOver()

	/**
	 * Two visible objects mean the scope did not hold; nothing is changed.
	 *
	 * @return void
	 */
	public function testTwoVisibleObjectsAreRefused(): void {
		$this->groups->method('isAdmin')->willReturn(true);
		$this->objects->method('searchObjectsBySlug')->willReturn([$this->row(self::ORG_A), $this->row(self::ORG_A)]);
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('More than one');
		$this->service()->save(input: ['consentPeriodDays' => 42]);
	}//end testTwoVisibleObjectsAreRefused()

	/**
	 * A default huisstijl must resolve inside the caller's organisation.
	 *
	 * @return void
	 */
	public function testAHuisstijlOutsideTheOrganisationIsRefused(): void {
		$this->groups->method('isAdmin')->willReturn(true);
		$this->objects->expects($this->once())->method('find')->willThrowException(new RuntimeException('not found'));
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(InvalidArgumentException::class);
		$this->service()->save(input: ['defaultHuisstijl' => self::HUISSTIJL]);
	}//end testAHuisstijlOutsideTheOrganisationIsRefused()

	/**
	 * A visible huisstijl is stored as the default.
	 *
	 * @return void
	 */
	public function testAVisibleHuisstijlIsStored(): void {
		$this->groups->method('isAdmin')->willReturn(true);
		$this->objects->expects($this->once())->method('find')
			->with(self::HUISSTIJL, $this->anything())
			->willReturn($this->createMock(\OCA\OpenRegister\Contract\ObjectEntityInterface::class));
		$this->objects->method('searchObjectsBySlug')->willReturn([]);
		$this->objects->expects($this->once())->method('saveObject')->with(
			$this->callback(static fn (array $object): bool => $object['defaultHuisstijl'] === self::HUISSTIJL)
		);

		$this->service()->save(input: ['defaultHuisstijl' => strtoupper(self::HUISSTIJL)]);
	}//end testAVisibleHuisstijlIsStored()

	/**
	 * A failing organisation-admin check refuses.
	 *
	 * @return void
	 */
	public function testAFailingAdminCheckRefuses(): void {
		$organisations = $this->organisations;
		$this->organisations = new class($organisations) {
			public function __construct(private object $inner) {
			}

			public function getActiveOrganisation(): ?object {
				return $this->inner->getActiveOrganisation();
			}

			public function isOrganisationAdmin(string $uuid, ?string $userId = null): bool {
				throw new RuntimeException('down');
			}
		};
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(OrganisationSettingsDeniedException::class);
		$this->service()->save(input: ['consentPeriodDays' => 42]);
	}//end testAFailingAdminCheckRefuses()
}//end class
