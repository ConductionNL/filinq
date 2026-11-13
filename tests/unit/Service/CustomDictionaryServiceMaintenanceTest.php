<?php

/**
 * Unit tests for CustomDictionaryService's dictionary and term maintenance
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

use InvalidArgumentException;
use OCA\Filinq\Service\CustomDictionaryAccessGate;
use OCA\Filinq\Service\CustomDictionaryPayloadNormaliser;
use OCA\Filinq\Service\CustomDictionaryRepository;
use OCA\Filinq\Service\CustomDictionaryService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Service\OrganisationService;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Updating, deleting, listing and adding/removing terms of a dictionary, over
 * the real repository, access gate and normaliser with OpenRegister faked.
 *
 * @covers \OCA\Filinq\Service\CustomDictionaryService
 * @uses   \OCA\Filinq\Service\CustomDictionaryRepository
 * @uses   \OCA\Filinq\Service\CustomDictionaryAccessGate
 * @uses   \OCA\Filinq\Service\CustomDictionaryPayloadNormaliser
 */
class CustomDictionaryServiceMaintenanceTest extends TestCase {

	/**
	 * The stored dictionaries, by id.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $dictionaries = [];

	/**
	 * The stored terms.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $terms = [];

	/**
	 * Saves made, as [schema, object, uuid].
	 *
	 * @var list<array{0: string, 1: array, 2: ?string}>
	 */
	private array $saved = [];

	/**
	 * Deletes made, as "schema:uuid".
	 *
	 * @var list<string>
	 */
	private array $deleted = [];

	/**
	 * Set up the stored dictionaries and terms.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->dictionaries = [
			'dict-a' => [
				'id' => 'dict-a',
				'label' => 'Projectnamen',
				'matchMode' => 'exact',
				'@self' => ['organisation' => 'org-a', 'slug' => 'projectnamen'],
			],
			'dict-b' => [
				'id' => 'dict-b',
				'label' => 'Andere',
				'@self' => ['organisation' => 'org-b', 'slug' => 'andere'],
			],
		];
		$this->terms        = [
			['id' => 'term-1', 'value' => 'Zuidas', 'dictionary' => 'dict-a'],
			['id' => 'term-2', 'value' => 'Noordlijn', 'dictionary' => 'projectnamen'],
			['id' => '', 'value' => 'Idless', 'dictionary' => 'dict-a'],
			['id' => 'term-3', 'value' => 'Elders', 'dictionary' => 'dict-b'],
			['id' => 'term-4', 'value' => 'Los'],
		];

	}//end setUp()

	/**
	 * The service, for a caller with access to org-a only.
	 *
	 * @param bool $maintainer Whether the caller may maintain dictionaries.
	 *
	 * @return CustomDictionaryService
	 */
	private function service(bool $maintainer = true): CustomDictionaryService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			fn (string $registerSlug, string $schemaSlug): array => $schemaSlug === CustomDictionaryService::SCHEMA_TERM ? $this->terms : array_values($this->dictionaries)
		);
		$objectService->method('find')->willReturnCallback(
			function (...$args): ?array {
				if ($args[2] === CustomDictionaryService::SCHEMA_TERM) {
					foreach ($this->terms as $term) {
						if ($term['id'] === $args[0]) {
							return $term;
						}
					}

					return null;
				}

				return ($this->dictionaries[$args[0]] ?? null);
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$args): array {
				$this->saved[] = [$args[2], $args[0], ($args[3] ?? null)];

				return $args[0] + ['id' => ($args[3] ?? 'new-id')];
			}
		);
		$objectService->method('deleteObject')->willReturnCallback(
			function (...$args): bool {
				$this->deleted[] = $args[2] . ':' . $args[0];

				return true;
			}
		);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($objectService);

		$organisationService = $this->createMock(OrganisationService::class);
		$organisationService->method('hasAccessToOrganisation')->willReturnCallback(
			static fn (string $uuid): bool => $uuid === 'org-a'
		);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($organisationService);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->willReturn(false);
		$groupManager->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $group): bool => $maintainer === true && $group === CustomDictionaryAccessGate::MAINTAINER_GROUP
		);

		return new CustomDictionaryService(
			repository: new CustomDictionaryRepository(settingsService: $settingsService, logger: new NullLogger()),
			accessGate: new CustomDictionaryAccessGate(
				container: $container,
				appManager: $appManager,
				userSession: $userSession,
				logger: new NullLogger(),
				groupManager: $groupManager
			),
			normaliser: new CustomDictionaryPayloadNormaliser(),
			logger: new NullLogger()
		);

	}//end service()

	/**
	 * Updating merges over the stored record, drops computed fields and keeps the uuid.
	 *
	 * @return void
	 */
	public function testUpdateMergesOverTheStoredRecord(): void {
		$result = $this->service()->updateDictionary('dict-a', ['label' => 'Nieuwe naam', 'termCount' => 99, 'matchMode' => 'bogus']);

		$this->assertCount(1, $this->saved);
		[$schema, $object, $uuid] = $this->saved[0];
		$this->assertSame('customDictionary', $schema);
		$this->assertSame('dict-a', $uuid);
		$this->assertSame('Nieuwe naam', $object['label']);
		$this->assertArrayNotHasKey('termCount', $object);
		$this->assertArrayNotHasKey('@self', $object);
		$this->assertSame((new CustomDictionaryPayloadNormaliser())->sanitizeMatchMode('bogus'), $object['matchMode']);
		$this->assertSame(2, $result['termCount']);

	}//end testUpdateMergesOverTheStoredRecord()

	/**
	 * Deleting removes the dictionary's own terms (by id and by slug), then the dictionary.
	 *
	 * @return void
	 */
	public function testDeleteRemovesItsTermsThenTheDictionary(): void {
		$this->service()->deleteDictionary('dict-a');

		$this->assertSame(
			['customDictionaryTerm:term-1', 'customDictionaryTerm:term-2', 'customDictionary:dict-a'],
			$this->deleted
		);

	}//end testDeleteRemovesItsTermsThenTheDictionary()

	/**
	 * A caller who may not maintain dictionaries cannot delete one.
	 *
	 * @return void
	 */
	public function testDeleteNeedsMaintainerRights(): void {
		try {
			$this->service(maintainer: false)->deleteDictionary('dict-a');
			$this->fail('A non-maintainer deleted a dictionary.');
		} catch (RuntimeException $e) {
			$this->assertSame([], $this->deleted);
		}

	}//end testDeleteNeedsMaintainerRights()

	/**
	 * Terms are listed for an accessible dictionary, matched by id or slug.
	 *
	 * @return void
	 */
	public function testListTermsMatchesByIdOrSlug(): void {
		$values = array_column($this->service()->listTerms('dict-a'), 'value');

		$this->assertSame(['Zuidas', 'Noordlijn', 'Idless'], $values);

	}//end testListTermsMatchesByIdOrSlug()

	/**
	 * Another organisation's dictionary is refused.
	 *
	 * @return void
	 */
	public function testListTermsRefusesAnInaccessibleDictionary(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('You do not have access to this custom dictionary.');

		$this->service()->listTerms('dict-b');

	}//end testListTermsRefusesAnInaccessibleDictionary()

	/**
	 * A new term is trimmed, labelled and linked to the dictionary id.
	 *
	 * @return void
	 */
	public function testCreateTermStoresATrimmedLinkedTerm(): void {
		$this->service()->createTerm('dict-a', ['value' => '  Westpoort  ', 'label' => 'Haven']);

		$this->assertSame(
			[['customDictionaryTerm', ['value' => 'Westpoort', 'label' => 'Haven', 'dictionary' => 'dict-a'], null]],
			$this->saved
		);

	}//end testCreateTermStoresATrimmedLinkedTerm()

	/**
	 * A blank term is refused before anything is stored.
	 *
	 * @return void
	 */
	public function testCreateTermRefusesABlankValue(): void {
		try {
			$this->service()->createTerm('dict-a', ['value' => '   ']);
			$this->fail('A blank term was accepted.');
		} catch (InvalidArgumentException $e) {
			$this->assertSame('Term value must not be blank.', $e->getMessage());
			$this->assertSame([], $this->saved);
		}

	}//end testCreateTermRefusesABlankValue()

	/**
	 * A term of this dictionary is deleted.
	 *
	 * @return void
	 */
	public function testDeleteTermRemovesATermOfThisDictionary(): void {
		$this->service()->deleteTerm('dict-a', 'term-2');

		$this->assertSame(['customDictionaryTerm:term-2'], $this->deleted);

	}//end testDeleteTermRemovesATermOfThisDictionary()

	/**
	 * A term of another dictionary, an unlinked term or an unknown term is not found.
	 *
	 * @return void
	 */
	public function testDeleteTermRefusesForeignOrUnknownTerms(): void {
		foreach (['term-3', 'term-4', 'term-404'] as $termId) {
			try {
				$this->service()->deleteTerm('dict-a', $termId);
				$this->fail($termId . ' was deleted through dict-a.');
			} catch (DoesNotExistException $e) {
				$this->assertSame('Term not found for this dictionary.', $e->getMessage());
			}
		}

		$this->assertSame([], $this->deleted);

	}//end testDeleteTermRefusesForeignOrUnknownTerms()
}//end class
