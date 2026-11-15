<?php

/**
 * Unit tests for TemplateService's edit locks and duplication
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

use DateTime;
use Exception;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * A live lock held by someone else blocks acquiring and releasing; an expired
 * or unreadable lock does not. Duplicates are saved as a fresh "(kopie)".
 *
 * @covers \OCA\Filinq\Service\TemplateService
 * @uses   \OCA\Filinq\Service\OpenRegisterResolver
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 */
class TemplateServiceLockTest extends TestCase {

	/**
	 * The fake OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The app config holding the lock timeout.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig&MockObject $config;

	/**
	 * The objects saved, in order.
	 *
	 * @var list<array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Set up the object service and config.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object, string $register, string $schema): array {
				TestCase::assertSame('reg-t', $register);
				TestCase::assertSame('sch-t', $schema);
				$this->saved[] = $object;

				return $object;
			}
		);

		$this->config = $this->createMock(IAppConfig::class);
		$this->config->method('getValueString')
			->with('filinq', 'templates.lock_timeout_minutes', '15')
			->willReturn('15');

	}//end setUp()

	/**
	 * The service, over a stored template.
	 *
	 * @param array<string, mixed>|null $template The stored template, or null for none.
	 *
	 * @return TemplateService
	 */
	private function service(?array $template): TemplateService {
		$this->objectService->method('find')->willReturnCallback(
			static function () use ($template): ?array {
				if ($template === null) {
					throw new DoesNotExistException('nope');
				}

				return $template;
			}
		);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($this->objectService);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getAllSettings')->willReturn(
			['configuration' => ['template_register' => 'reg-t', 'template_schema' => 'sch-t']]
		);

		$versions = $this->createMock(TemplateVersionService::class);
		$versions->method('getNextVersionNumber')->willReturn(0);

		return new TemplateService(
			new OpenRegisterAccess($apps, $container),
			new OpenRegisterResolver($settings),
			$versions,
			$this->createMock(IUserSession::class),
			$this->config
		);

	}//end service()

	/**
	 * A lock time the given number of minutes ago.
	 *
	 * @param int $minutes Minutes ago.
	 *
	 * @return string
	 */
	private function minutesAgo(int $minutes): string {
		return (new DateTime('-' . $minutes . ' minutes'))->format('c');

	}//end minutesAgo()

	/**
	 * An unknown template is a 404.
	 *
	 * @return void
	 */
	public function testAnUnknownTemplateIsNotFound(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Template not found');
		$this->expectExceptionCode(404);

		$this->service(null)->acquireLock('tpl-1', 'alice');

	}//end testAnUnknownTemplateIsNotFound()

	/**
	 * A live lock of someone else refuses with a 409 naming the holder.
	 *
	 * @return void
	 */
	public function testALiveForeignLockRefusesAcquiring(): void {
		$lockedAt = $this->minutesAgo(5);

		try {
			$this->service(['id' => 'tpl-1', 'lockedBy' => 'bob', 'lockedAt' => $lockedAt])->acquireLock('tpl-1', 'alice');
			$this->fail('A live foreign lock was taken over.');
		} catch (Exception $e) {
			$this->assertSame(409, $e->getCode());
			$this->assertSame(
				['error' => 'Template is locked by another user', 'lockedBy' => 'bob', 'lockedAt' => $lockedAt],
				json_decode($e->getMessage(), true)
			);
			$this->assertSame([], $this->saved);
		}

	}//end testALiveForeignLockRefusesAcquiring()

	/**
	 * An expired foreign lock is taken over by the caller.
	 *
	 * @return void
	 */
	public function testAnExpiredLockIsTakenOver(): void {
		$result = $this->service(['id' => 'tpl-1', 'name' => 'Brief', 'lockedBy' => 'bob', 'lockedAt' => $this->minutesAgo(30)])
			->acquireLock('tpl-1', 'alice');

		$this->assertSame('alice', $result['lockedBy']);
		$this->assertSame('tpl-1', $result['id']);
		$this->assertSame('Brief', $result['name']);
		$this->assertCount(1, $this->saved);
		$this->assertLessThan(60, abs((new DateTime())->getTimestamp() - (new DateTime($result['lockedAt']))->getTimestamp()));

	}//end testAnExpiredLockIsTakenOver()

	/**
	 * Releasing someone else's live lock is a 403.
	 *
	 * @return void
	 */
	public function testReleasingAForeignLiveLockIsForbidden(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot release lock held by another user');
		$this->expectExceptionCode(403);

		$this->service(['id' => 'tpl-1', 'lockedBy' => 'bob', 'lockedAt' => $this->minutesAgo(1)])->releaseLock('tpl-1', 'alice');

	}//end testReleasingAForeignLiveLockIsForbidden()

	/**
	 * The holder releases their own lock.
	 *
	 * @return void
	 */
	public function testTheHolderReleasesTheirOwnLock(): void {
		$own = $this->service(['id' => 'tpl-1', 'lockedBy' => 'alice', 'lockedAt' => $this->minutesAgo(1)])->releaseLock('tpl-1', 'alice');

		$this->assertNull($own['lockedBy']);
		$this->assertNull($own['lockedAt']);
		$this->assertCount(1, $this->saved);

	}//end testTheHolderReleasesTheirOwnLock()

	/**
	 * A lock time that cannot be read counts as expired.
	 *
	 * @return void
	 */
	public function testAnUnreadableLockTimeCountsAsExpired(): void {
		$garbled = $this->service(['id' => 'tpl-2', 'lockedBy' => 'bob', 'lockedAt' => 'not a time'])->releaseLock('tpl-2', 'alice');

		$this->assertNull($garbled['lockedBy']);
		$this->assertSame('tpl-2', $garbled['id']);

	}//end testAnUnreadableLockTimeCountsAsExpired()

	/**
	 * A duplicate carries the content under a "(kopie)" name, with defaults, and no id or lock.
	 *
	 * @return void
	 */
	public function testDuplicateSavesAFreshCopy(): void {
		$copy = $this->service([
			'id' => 'tpl-1',
			'name' => 'Brief',
			'content' => '<p>Hallo</p>',
			'namespace' => 'filinq',
			'lockedBy' => 'bob',
			'slug' => 'brief',
		])->duplicateTemplate('tpl-1');

		$expected = [
			'name' => 'Brief (kopie)',
			'description' => '',
			'content' => '<p>Hallo</p>',
			'namespace' => 'filinq',
			'format' => 'A4',
			'orientation' => 'P',
			'category' => '',
			'tags' => [],
		];
		$this->assertSame([$expected], $this->saved);
		$this->assertSame($expected, $copy);

	}//end testDuplicateSavesAFreshCopy()

	/**
	 * A duplicate saved as an entity is returned serialised.
	 *
	 * @return void
	 */
	public function testDuplicateSerialisesAnEntityResult(): void {
		$entity = $this->createMock(ObjectEntity::class);
		$entity->method('jsonSerialize')->willReturn(['id' => 'tpl-9', 'name' => 'Brief (kopie)']);

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturn($entity);
		$this->objectService = $objectService;

		$copy = $this->service(['id' => 'tpl-1', 'name' => 'Brief', 'namespace' => 'filinq'])->duplicateTemplate('tpl-1');

		$this->assertSame(['id' => 'tpl-9', 'name' => 'Brief (kopie)'], $copy);

	}//end testDuplicateSerialisesAnEntityResult()
}//end class
