<?php

/**
 * Which organisations EntityCatalogue::scope limits a search to.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\EntitySearch\EntityCatalogue;
use OCP\App\IAppManager;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

/**
 * An admin is unscoped, anyone else gets the uuids of their organisations;
 * no OpenRegister or unreadable organisations refuse.
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntityCatalogue
 * @uses   \OCA\Filinq\Exception\EntitySearchRefusedException
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityCatalogueScopeTest extends TestCase {

	/**
	 * An organisation as OpenRegister hands it out.
	 *
	 * @param mixed $uuid What getUuid answers.
	 *
	 * @return object The organisation.
	 */
	private static function organisation(mixed $uuid): object {
		return new class ($uuid) {

			/**
			 * Constructor.
			 *
			 * @param mixed $uuid The uuid.
			 */
			public function __construct(private readonly mixed $uuid) {
			}//end __construct()

			/**
			 * The uuid.
			 *
			 * @return mixed
			 */
			public function getUuid(): mixed {
				return $this->uuid;
			}//end getUuid()
		};

	}//end organisation()

	/**
	 * A catalogue whose organisation service answers $organisations, or throws it.
	 *
	 * @param mixed              $organisations What getUserOrganisations answers; a Throwable is thrown.
	 * @param array<int, string> $installed     The installed apps.
	 * @param int                $lookups       How often the organisation service must be fetched.
	 *
	 * @return EntityCatalogue The catalogue.
	 */
	private function catalogue(mixed $organisations, array $installed = ['files', 'openregister'], int $lookups = 1): EntityCatalogue {
		$service = new class ($organisations) {

			/**
			 * Constructor.
			 *
			 * @param mixed $answer The answer.
			 */
			public function __construct(private readonly mixed $answer) {
			}//end __construct()

			/**
			 * The caller's organisations.
			 *
			 * @return mixed
			 */
			public function getUserOrganisations(): mixed {
				if ($this->answer instanceof \Throwable) {
					throw $this->answer;
				}

				return $this->answer;
			}//end getUserOrganisations()
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->exactly($lookups))->method('get')->with('OCA\OpenRegister\Service\OrganisationService')->willReturn($service);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($installed);
		$db = $this->createMock(IDBConnection::class);
		$db->expects($this->never())->method('getQueryBuilder');

		return new EntityCatalogue($db, $container, $apps);

	}//end catalogue()

	/**
	 * An admin is not scoped, and their organisations are never read.
	 *
	 * @return void
	 */
	public function testAnAdminIsUnscoped(): void {
		$this->assertNull($this->catalogue(organisations: new RuntimeException('not read'), lookups: 0)->scope(isAdmin: true));
	}//end testAnAdminIsUnscoped()

	/**
	 * Anyone else gets their organisations' uuids in order, skipping what is
	 * not an organisation and organisations without a uuid.
	 *
	 * @return void
	 */
	public function testANonAdminGetsTheirOrganisationUuids(): void {
		$organisations = [
			self::organisation(uuid: 'org-1'),
			['uuid' => 'org-array'],
			new stdClass(),
			'org-string',
			self::organisation(uuid: ''),
			self::organisation(uuid: null),
			self::organisation(uuid: 42),
			self::organisation(uuid: 'org-2'),
		];

		$this->assertSame(['org-1', '42', 'org-2'], $this->catalogue(organisations: $organisations)->scope(isAdmin: false));
	}//end testANonAdminGetsTheirOrganisationUuids()

	/**
	 * Someone in no organisation gets an empty scope, not an unscoped one.
	 *
	 * @return void
	 */
	public function testNoOrganisationsIsAnEmptyScope(): void {
		$this->assertSame([], $this->catalogue(organisations: [])->scope(isAdmin: false));
		$this->assertSame([], $this->catalogue(organisations: null)->scope(isAdmin: false));
	}//end testNoOrganisationsIsAnEmptyScope()

	/**
	 * Organisations that cannot be read refuse and name the cause.
	 *
	 * @return void
	 */
	public function testUnreadableOrganisationsRefuse(): void {
		try {
			$this->catalogue(organisations: new RuntimeException('no session'))->scope(isAdmin: false);
			$this->fail('A scope was handed out without the organisations.');
		} catch (EntitySearchRefusedException $e) {
			$this->assertSame(EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE, $e->getReason());
			$this->assertSame('The caller\'s organisations could not be read: no session', $e->getMessage());
		}
	}//end testUnreadableOrganisationsRefuse()

	/**
	 * Without OpenRegister installed there is no catalogue, for admins too.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterEveryoneIsRefused(): void {
		foreach ([true, false] as $isAdmin) {
			try {
				$this->catalogue(organisations: [self::organisation(uuid: 'org-1')], installed: ['files', 'openregister_old'], lookups: 0)->scope(isAdmin: $isAdmin);
				$this->fail('A scope was handed out without OpenRegister.');
			} catch (EntitySearchRefusedException $e) {
				$this->assertSame(EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE, $e->getReason());
				$this->assertSame('OpenRegister is not installed, so there is no entity catalogue.', $e->getMessage());
			}
		}
	}//end testWithoutOpenRegisterEveryoneIsRefused()
}//end class
