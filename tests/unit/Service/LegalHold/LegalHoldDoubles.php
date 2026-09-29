<?php

/**
 * Doubles for the legal hold tests: an in-memory register, records with a
 * retention block, a file lock provider and a notification manager.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\LegalHold
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\LegalHold;

use DateTime;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\LegalHold\LegalHoldAuthority;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseService;
use OCA\Filinq\Service\LegalHold\LegalHoldFanOut;
use OCA\Filinq\Service\LegalHold\LegalHoldFileFreeze;
use OCA\Filinq\Service\LegalHold\LegalHoldNotifications;
use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Shared set-up for LegalHoldCaseServiceTest, LegalHoldCaseControllerTest and LegalHoldCaseSchemaTest.
 */
trait LegalHoldDoubles {

	/** @var array<string, array<string, mixed>> Stored cases by uuid. */
	protected array $cases = [];

	/** @var array<string, ObjectEntity> Records by uuid. */
	protected array $records = [];

	/** @var array<int, string> Records the caller may read with their own rights. */
	protected array $readable = [];

	/** @var array<int, string> Owner app of each file lock, by file id. */
	protected array $locks = [];

	/** @var bool Whether files_lock is installed. */
	protected bool $lockProvider = true;

	/** @var array<int, int> File ids whose lock throws. */
	protected array $lockFailsOn = [];

	/** @var array<int, File> Files by id. */
	protected array $files = [];

	/** @var array<int, array{user: string, subject: string, name: string}> Notifications sent. */
	protected array $sent = [];

	/** @var array<int, string> Admin user ids. */
	protected array $admins = ['admin'];

	/** @var array<string, array<int, string>> Group => members. */
	protected array $members = ['legal' => ['alice']];

	/** @var string The raw authority setting. */
	protected string $authoritySetting = '["legal"]';

	/** @var array<int, array<string, mixed>> Every payload written to the register. */
	protected array $written = [];

	/** @var bool Whether the register refuses every write. */
	protected bool $registerDown = false;

	/** @var FakeLegalHoldService|null OpenRegister's hold service. */
	protected ?FakeLegalHoldService $holdService = null;

	/**
	 * A record in scope, with an owner and a file.
	 *
	 * @param string $uuid   The record.
	 * @param string $owner  Its owner.
	 * @param int    $fileId Its file, 0 for none.
	 *
	 * @return ObjectEntity The record.
	 */
	protected function record(string $uuid, string $owner = 'bob', int $fileId = 0): ObjectEntity {
		$record = new ObjectEntity();
		$record->setUuid($uuid);
		$record->setOwner($owner);
		$data = ['title' => 'Record ' . $uuid];
		if ($fileId > 0) {
			$data['fileId'] = $fileId;
			$file = $this->createMock(File::class);
			$file->method('getId')->willReturn($fileId);
			$this->files[$fileId] = $file;
		}

		$record->setObject($data);
		$this->records[$uuid] = $record;
		$this->readable[] = $uuid;

		return $record;

	}//end record()

	/**
	 * The service under test, wired to the doubles.
	 *
	 * @return LegalHoldCaseService The service.
	 */
	protected function service(): LegalHoldCaseService {
		$this->holdService = ($this->holdService ?? new FakeLegalHoldService());
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService());

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			fn (string $class) => ($class === 'OCA\OpenRegister\Service\Archival\LegalHoldService' ? $this->holdService : throw new RuntimeException('Unknown ' . $class))
		);

		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturnCallback(static fn (): DateTime => new DateTime('2026-09-29T10:00:00+00:00'));

		$fanOut = new LegalHoldFanOut(
			records: new LegalHoldRecordFreeze(objectResolver: $resolver, locator: new OpenRegisterServiceLocator($apps, $container)),
			files: new LegalHoldFileFreeze(lockManager: $this->lockManager(), rootFolder: $this->rootFolder()),
			time: $time
		);

		return new LegalHoldCaseService(
			authority: $this->authority(),
			cases: new LegalHoldCaseRepository(objectResolver: $resolver),
			fanOut: $fanOut,
			notifications: new LegalHoldNotifications(
				notifications: $this->notificationManager(),
				users: $this->userManager(),
				time: $time,
				logger: new NullLogger()
			),
			time: $time
		);

	}//end service()

	/**
	 * The authority gate over the configured setting.
	 *
	 * @return LegalHoldAuthority The gate.
	 */
	protected function authority(): LegalHoldAuthority {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (): string => $this->authoritySetting);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(fn (string $uid): bool => in_array($uid, $this->admins, true));
		$groups->method('isInGroup')->willReturnCallback(fn (string $uid, string $group): bool => in_array($uid, ($this->members[$group] ?? []), true));

		return new LegalHoldAuthority(appConfig: $config, groupManager: $groups);

	}//end authority()

	/**
	 * OpenRegister's ObjectService over the in-memory register.
	 *
	 * @return ObjectService The double.
	 */
	protected function objectService(): ObjectService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters = [], bool $_rbac = true): array {
				if ($schemaSlug !== 'legalHoldCase' || $_rbac !== false) {
					throw new RuntimeException('Hold cases are read past RBAC only.');
				}

				$hits = [];
				foreach ($this->cases as $uuid => $row) {
					foreach ($filters as $field => $value) {
						if (($row[$field] ?? null) !== $value) {
							continue 2;
						}
					}

					$hits[] = array_merge($row, ['@self' => ['id' => $uuid]]);
				}

				return $hits;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null, bool $_rbac = true): array {
				if ($schema !== 'legalHoldCase' || $_rbac !== false) {
					throw new RuntimeException('Hold cases are written past RBAC only.');
				}

				if ($this->registerDown === true) {
					throw new RuntimeException('SQLSTATE[HY000]: database is locked at /var/www/html/lib/private/DB.php:42');
				}

				$this->written[] = $object;
				$uuid = ($uuid ?? 'case-' . (count($this->cases) + 1));
				$this->cases[$uuid] = $object;

				return array_merge($object, ['@self' => ['id' => $uuid]]);
			}
		);
		$objects->method('find')->willReturnCallback(
			function (string $id = '', string $register = '', string $schema = '', bool $_rbac = true, bool $_multitenancy = true, bool $_render = true) {
				if ($_render !== false) {
					throw new RuntimeException('Records are read unrendered, so the retention block is there.');
				}

				if ($_rbac === true && in_array($id, $this->readable, true) === false) {
					throw new RuntimeException('Forbidden');
				}

				return ($this->records[$id] ?? null);
			}
		);

		return $objects;

	}//end objectService()

	/**
	 * files_lock, in memory.
	 *
	 * @return ILockManager The double.
	 */
	protected function lockManager(): ILockManager {
		$locks = $this->createMock(ILockManager::class);
		$locks->method('isLockProviderAvailable')->willReturnCallback(fn (): bool => $this->lockProvider);
		$locks->method('lock')->willReturnCallback(
			function (LockContext $context): ILock {
				$fileId = $context->getNode()->getId();
				if (in_array($fileId, $this->lockFailsOn, true) === true || isset($this->locks[$fileId]) === true) {
					throw new RuntimeException('File is locked');
				}

				$this->locks[$fileId] = $context->getOwner();

				return $this->lockOf(owner: $context->getOwner(), type: $context->getType());
			}
		);
		$locks->method('unlock')->willReturnCallback(
			function (LockContext $context): void {
				unset($this->locks[$context->getNode()->getId()]);
			}
		);
		$locks->method('getLocks')->willReturnCallback(
			fn (int $fileId): array => (isset($this->locks[$fileId]) === true ? [$this->lockOf(owner: $this->locks[$fileId], type: ILock::TYPE_APP)] : [])
		);

		return $locks;

	}//end lockManager()

	/**
	 * A lock as files_lock reports it.
	 *
	 * @param string $owner The owner.
	 * @param int    $type  The lock type.
	 *
	 * @return ILock The lock.
	 */
	private function lockOf(string $owner, int $type): ILock {
		$lock = $this->createMock(ILock::class);
		$lock->method('getOwner')->willReturn($owner);
		$lock->method('getType')->willReturn($type);

		return $lock;

	}//end lockOf()

	/**
	 * The file tree.
	 *
	 * @return IRootFolder The double.
	 */
	protected function rootFolder(): IRootFolder {
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(fn (int $id) => ($this->files[$id] ?? null));

		return $root;

	}//end rootFolder()

	/**
	 * The notification manager, recording what is sent.
	 *
	 * @return IManager The double.
	 */
	protected function notificationManager(): IManager {
		$manager = $this->createMock(IManager::class);
		$manager->method('createNotification')->willReturnCallback(
			function (): INotification {
				$state = new \ArrayObject();
				$notification = $this->createMock(INotification::class);
				foreach (['setApp', 'setDateTime', 'setObject'] as $method) {
					$notification->method($method)->willReturnSelf();
				}

				$notification->method('setUser')->willReturnCallback(
					function (string $user) use ($state, $notification): INotification {
						$state['user'] = $user;
						return $notification;
					}
				);
				$notification->method('setSubject')->willReturnCallback(
					function (string $subject, array $parameters) use ($state, $notification): INotification {
						$state['subject'] = $subject;
						$state['name'] = $parameters['name'];
						return $notification;
					}
				);
				$notification->method('getUser')->willReturnCallback(fn (): string => (string) $state['user']);
				$notification->method('getSubject')->willReturnCallback(fn (): string => (string) $state['subject']);
				$notification->method('getSubjectParameters')->willReturnCallback(fn (): array => ['name' => $state['name']]);

				return $notification;
			}
		);
		$manager->method('notify')->willReturnCallback(
			function (INotification $notification): void {
				$this->sent[] = ['user' => $notification->getUser(), 'subject' => $notification->getSubject(), 'name' => (string) $notification->getSubjectParameters()['name']];
			}
		);

		return $manager;

	}//end notificationManager()

	/**
	 * Users that exist: everyone but "ghost".
	 *
	 * @return IUserManager The double.
	 */
	protected function userManager(): IUserManager {
		$users = $this->createMock(IUserManager::class);
		$users->method('userExists')->willReturnCallback(static fn (string $uid): bool => $uid !== 'ghost');

		return $users;

	}//end userManager()

	/**
	 * Validate a payload against the real legalHoldCase fragment, as hard validation does.
	 *
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return void
	 */
	protected function assertValidCase(array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$schema = $register['components']['schemas']['legalHoldCase'];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels'], $property['title']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(['type' => 'object', 'required' => $schema['required'], 'properties' => $properties, 'additionalProperties' => false]);
		unset($payload['uuid'], $payload['@self']);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), 'legalHoldCase: ' . $message);

	}//end assertValidCase()

	/**
	 * A valid case input.
	 *
	 * @param array<int, string> $documents The document refs.
	 * @param array<int, string> $dossiers  The dossier refs.
	 *
	 * @return array<string, mixed> The input.
	 */
	protected function input(array $documents, array $dossiers = []): array {
		return [
			'name' => 'Bezwaar 2026-004',
			'holdType' => 'woo-appeal',
			'reason' => 'Bezwaar tegen Woo-besluit 2026-004.',
			'caseReference' => 'BZW-2026-004',
			'custodian' => 'carol',
			'scopeDocuments' => $documents,
			'scopeDossiers' => $dossiers,
		];

	}//end input()
}//end trait
