<?php

/**
 * Unit tests for CustomDictionaryService — custom-dictionary-recognition
 *
 * Covers the organisation-gate matrix (REQ-DDCDR-001 / REQ-DDCDR-004) and
 * CSV/newline import dedupe (REQ-DDCDR-005).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\CustomDictionaryAccessGate;
use OCA\DocuDesk\Service\CustomDictionaryPayloadNormaliser;
use OCA\DocuDesk\Service\CustomDictionaryRepository;
use OCA\DocuDesk\Service\CustomDictionaryService;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Service\OrganisationService;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Tests for CustomDictionaryService's organisation gate and import parsing.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryServiceTest extends TestCase {

	/**
	 * Build a service with a stubbed ObjectService and organisation gate.
	 *
	 * @param ObjectService $objectService ObjectService double.
	 * @param array<string, bool> $organisationAccess Map of organisationUuid => hasAccess.
	 * @param string|null $currentUserId Current user id, or null for unauthenticated.
	 *
	 * @return CustomDictionaryService
	 */
	private function makeService(
		ObjectService $objectService,
		array $organisationAccess = [],
		?string $currentUserId = 'alice',
	): CustomDictionaryService {
		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn($objectService);

		$organisationService = $this->createMock(OrganisationService::class);
		$organisationService->method('hasAccessToOrganisation')->willReturnCallback(
			static fn (string $uuid): bool => ($organisationAccess[$uuid] ?? false)
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $class) use ($organisationService) {
				if ($class === 'OCA\OpenRegister\Service\OrganisationService') {
					return $organisationService;
				}

				return null;
			}
		);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);

		$userSession = $this->createMock(IUserSession::class);
		if ($currentUserId === null) {
			$userSession->method('getUser')->willReturn(null);
		} else {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($currentUserId);
			$userSession->method('getUser')->willReturn($user);
		}

		return new CustomDictionaryService(
			repository: new CustomDictionaryRepository(
				settingsService: $settingsService,
				logger: new NullLogger()
			),
			accessGate: new CustomDictionaryAccessGate(
				container: $container,
				appManager: $appManager,
				userSession: $userSession,
				logger: new NullLogger()
			),
			normaliser: new CustomDictionaryPayloadNormaliser(),
			logger: new NullLogger()
		);

	}//end makeService()

	/**
	 * A dictionary owned by organisation B is not returned to a caller whose
	 * only accessible organisation is A (REQ-DDCDR-001 scenario).
	 *
	 * @return void
	 */
	public function testListDictionariesIsOrganisationScoped(): void {
		$dictionaries = [
			[
				'id' => 'dict-a',
				'slug' => 'dict-a',
				'label' => 'Projectnamen',
				'@self' => ['organisation' => 'org-a', 'slug' => 'dict-a'],
			],
			[
				'id' => 'dict-b',
				'slug' => 'dict-b',
				'label' => 'Andere organisatie',
				'@self' => ['organisation' => 'org-b', 'slug' => 'dict-b'],
			],
		];

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $registerSlug, string $schemaSlug) use ($dictionaries): array {
				if ($schemaSlug === CustomDictionaryService::SCHEMA_DICTIONARY) {
					return $dictionaries;
				}

				return [];
			}
		);

		$service = $this->makeService(objectService: $objectService, organisationAccess: ['org-a' => true, 'org-b' => false]);

		$result = $service->listDictionaries();

		$this->assertCount(1, $result);
		$this->assertSame('dict-a', $result[0]['id']);
		$this->assertArrayHasKey('termCount', $result[0]);

	}//end testListDictionariesIsOrganisationScoped()

	/**
	 * A dictionary with no `@self.organisation` at all is fail-closed
	 * (inaccessible), never treated as "everyone may see it".
	 *
	 * @return void
	 */
	public function testDictionaryWithoutOrganisationIsFailClosed(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn(
			[
				['id' => 'dict-orphan', 'label' => 'Orphan', '@self' => []],
			]
		);

		$service = $this->makeService(objectService: $objectService, organisationAccess: []);

		$this->assertSame([], $service->listDictionaries());

	}//end testDictionaryWithoutOrganisationIsFailClosed()

	/**
	 * getDictionary() throws DoesNotExistException for an unknown UUID.
	 *
	 * @return void
	 */
	public function testGetDictionaryThrowsNotFoundForUnknownUuid(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn(null);

		$service = $this->makeService(objectService: $objectService);

		$this->expectException(DoesNotExistException::class);
		$service->getDictionary(uuid: 'does-not-exist');

	}//end testGetDictionaryThrowsNotFoundForUnknownUuid()

	/**
	 * Editing another organisation's dictionary is refused with a
	 * RuntimeException (mapped to HTTP 403 by the controller) — REQ-DDCDR-004 scenario.
	 *
	 * @return void
	 */
	public function testUpdateAnotherOrganisationsDictionaryIsForbidden(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn(
			['id' => 'dict-a', 'label' => 'Projectnamen', '@self' => ['organisation' => 'org-a']]
		);
		$objectService->expects($this->never())->method('saveObject');

		$service = $this->makeService(objectService: $objectService, organisationAccess: ['org-a' => false]);

		$this->expectException(RuntimeException::class);
		$service->updateDictionary(uuid: 'dict-a', data: ['label' => 'Gehackt']);

	}//end testUpdateAnotherOrganisationsDictionaryIsForbidden()

	/**
	 * A permitted caller can create a dictionary; an absent/invalid
	 * matchMode defaults to caseInsensitive.
	 *
	 * @return void
	 */
	public function testCreateDictionaryDefaultsMatchMode(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn([]);
		$objectService->expects($this->once())
			->method('saveObject')
			->with($this->callback(static function (array $data): bool {
				return ($data['matchMode'] ?? null) === 'caseInsensitive'
					&& ($data['active'] ?? null) === true;
			}))
			->willReturnCallback(
				static fn (array $object): array => array_merge(
					$object,
					['id' => 'new-dict', '@self' => ['organisation' => 'org-a', 'slug' => 'straatnamen']]
				)
			);

		$service = $this->makeService(objectService: $objectService, organisationAccess: ['org-a' => true]);

		$created = $service->createDictionary(data: ['label' => 'Straatnamen']);

		$this->assertSame('caseInsensitive', $created['matchMode']);
		$this->assertSame(0, $created['termCount']);

	}//end testCreateDictionaryDefaultsMatchMode()

	/**
	 * Import adds new terms and skips duplicates (case-insensitive) and
	 * blank lines — REQ-DDCDR-005 scenario, verbatim numbers.
	 *
	 * @return void
	 */
	public function testImportDedupesAndSkipsBlanks(): void {
		$dictionary = ['id' => 'dict-a', 'label' => 'Steden', '@self' => ['organisation' => 'org-a', 'slug' => 'dict-a']];
		$existingTerm = ['id' => 'term-1', 'value' => 'Ridderkerk', 'dictionary' => 'dict-a'];

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($dictionary);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $registerSlug, string $schemaSlug) use ($existingTerm): array {
				if ($schemaSlug === CustomDictionaryService::SCHEMA_TERM) {
					return [$existingTerm];
				}

				return [];
			}
		);

		$createdValues = [];
		$objectService->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$createdValues): array {
				$createdValues[] = $object['value'];
				return $object;
			}
		);

		$service = $this->makeService(objectService: $objectService, organisationAccess: ['org-a' => true]);

		$result = $service->importTerms(
			dictionaryUuid: 'dict-a',
			content: "Ridderkerk\nBarendrecht\n  ",
			isCsv: false
		);

		$this->assertSame(['added' => 1, 'skipped' => 2, 'total' => 3], $result);
		$this->assertSame(['Barendrecht'], $createdValues);

	}//end testImportDedupesAndSkipsBlanks()

	/**
	 * CSV import reads the first column as the value and the second as an
	 * optional per-term label.
	 *
	 * @return void
	 */
	public function testImportParsesCsvValueAndLabelColumns(): void {
		$dictionary = ['id' => 'dict-a', 'label' => 'Steden', '@self' => ['organisation' => 'org-a', 'slug' => 'dict-a']];

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($dictionary);
		$objectService->method('searchObjectsBySlug')->willReturn([]);

		$captured = [];
		$objectService->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$captured): array {
				$captured[] = $object;
				return $object;
			}
		);

		$service = $this->makeService(objectService: $objectService, organisationAccess: ['org-a' => true]);

		$result = $service->importTerms(
			dictionaryUuid: 'dict-a',
			content: "Operatie Zilverreiger,Codenaam project X\nDossier Karekiet\n",
			isCsv: true
		);

		$this->assertSame(['added' => 2, 'skipped' => 0, 'total' => 2], $result);
		$this->assertSame('Operatie Zilverreiger', $captured[0]['value']);
		$this->assertSame('Codenaam project X', $captured[0]['label']);
		$this->assertSame('Dossier Karekiet', $captured[1]['value']);
		$this->assertNull($captured[1]['label']);

	}//end testImportParsesCsvValueAndLabelColumns()

	/**
	 * listActiveDictionariesForDetection() skips inactive dictionaries and
	 * dictionaries with no non-blank terms.
	 *
	 * @return void
	 */
	public function testListActiveDictionariesForDetectionSkipsInactiveAndEmpty(): void {
		$active = ['id' => 'dict-active', 'label' => 'Active', 'active' => true, 'matchMode' => 'exact', '@self' => ['organisation' => 'org-a', 'slug' => 'dict-active']];
		$inactive = ['id' => 'dict-inactive', 'label' => 'Inactive', 'active' => false, 'matchMode' => 'exact', '@self' => ['organisation' => 'org-a', 'slug' => 'dict-inactive']];
		$empty = ['id' => 'dict-empty', 'label' => 'Empty', 'active' => true, 'matchMode' => 'exact', '@self' => ['organisation' => 'org-a', 'slug' => 'dict-empty']];

		$terms = [
			['id' => 't1', 'value' => 'Foo', 'label' => null, 'dictionary' => 'dict-active'],
			['id' => 't2', 'value' => '   ', 'label' => null, 'dictionary' => 'dict-inactive'],
			['id' => 't3', 'value' => '', 'label' => null, 'dictionary' => 'dict-empty'],
		];

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $registerSlug, string $schemaSlug) use ($active, $inactive, $empty, $terms): array {
				if ($schemaSlug === CustomDictionaryService::SCHEMA_DICTIONARY) {
					return [$active, $inactive, $empty];
				}

				return $terms;
			}
		);

		$service = $this->makeService(objectService: $objectService, organisationAccess: ['org-a' => true]);

		$result = $service->listActiveDictionariesForDetection();

		$this->assertCount(1, $result);
		$this->assertSame('Active', $result[0]['label']);
		$this->assertSame('Foo', $result[0]['terms'][0]['value']);

	}//end testListActiveDictionariesForDetectionSkipsInactiveAndEmpty()
}//end class
