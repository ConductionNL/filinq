<?php

/**
 * Integration test: integriq's watched-folder hand-over reaches filinq through the real dispatcher.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Integration
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-filinq-takes-a-scanned-batch-that-integriqs-watched-folder-hands-over-req-scw-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Integration;

use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCA\Integriq\Event\WatchedFileArrivedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\Server;
use PHPUnit\Framework\TestCase;

/**
 * Dispatches integriq's real WatchedFileArrivedEvent through the IEventDispatcher
 * of a booted Nextcloud, where the listener is wired only by filinq's
 * `Application::register()` (no listener is attached by this test), and reads
 * the event's result the way integriq's ingest job does.
 *
 * Needs an installed Nextcloud with filinq and OpenRegister enabled (the batch is
 * stored as an OpenRegister object) and integriq's event class loadable. Run it
 * from a checkout under the instance's apps directory, with the booting bootstrap:
 *
 *     NC_INTEGRATION_USER=admin vendor/bin/phpunit --bootstrap tests/bootstrap.php \
 *         tests/integration/WatchedFolderScanIntakeTest.php
 *
 * Without a booted instance, the integriq class or `NC_INTEGRATION_USER` the test
 * is skipped, and it says why. It runs in the nightly against the dev stack.
 *
 * @coversNothing
 */
class WatchedFolderScanIntakeTest extends TestCase {

	/**
	 * The watched folder the test declares, under the user's home.
	 *
	 * @var string
	 */
	private string $folder = '';

	/**
	 * The scan profiles as they were before the test declared its own.
	 *
	 * @var string|null
	 */
	private ?string $savedProfiles = null;

	/**
	 * The user whose home holds the watched folder.
	 *
	 * @var string
	 */
	private string $user = '';

	/**
	 * Skip unless a booted Nextcloud with filinq and integriq's event is present.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->user = trim((string)getenv('NC_INTEGRATION_USER'));
		if ($this->user === '') {
			$this->markTestSkipped(message: 'NC_INTEGRATION_USER is not set: no live instance to dispatch on.');
		}

		if (class_exists(\OC\EventDispatcher\EventDispatcher::class) === false || \OC::$server === null) {
			$this->markTestSkipped(message: 'No booted Nextcloud: run with --bootstrap tests/bootstrap.php inside an installed instance.');
		}

		if (class_exists(WatchedFileArrivedEvent::class) === false) {
			$this->markTestSkipped(message: 'integriq is not installed: its WatchedFileArrivedEvent cannot be loaded.');
		}

	}//end setUp()

	/**
	 * Put the scan profiles back and remove the watched folder.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ($this->folder !== '') {
			$home = Server::get(IRootFolder::class)->getUserFolder($this->user);
			if ($home->nodeExists($this->folder) === true) {
				$home->get($this->folder)->delete();
			}

			$config = Server::get(IAppConfig::class);
			if ($this->savedProfiles === null) {
				$config->deleteKey('filinq', ScanProfileServiceInterface::CONFIG_KEY);
			} else {
				$config->setValueString('filinq', ScanProfileServiceInterface::CONFIG_KEY, $this->savedProfiles);
			}
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * The dispatcher wired by Application::register() reaches the listener, which accepts with the batch uuid.
	 *
	 * @spec openspec/changes/scan-intake-from-a-watched-folder/tasks.md#task-2.3
	 *
	 * @return void
	 */
	public function testTheDispatcherReachesTheListener(): void {
		$config = Server::get(IAppConfig::class);
		$this->savedProfiles = null;
		if ($config->hasKey('filinq', ScanProfileServiceInterface::CONFIG_KEY) === true) {
			$this->savedProfiles = $config->getValueString('filinq', ScanProfileServiceInterface::CONFIG_KEY);
		}

		$this->folder = 'filinq-watched-' . bin2hex(random_bytes(4));
		Server::get(ScanProfileServiceInterface::class)->declare(
			[['id' => $this->folder, 'watchedFolder' => $this->folder, 'separatorMode' => 'none']]
		);

		$home = Server::get(IRootFolder::class)->getUserFolder($this->user);
		$folder = $home->newFolder($this->folder);
		$this->assertInstanceOf(expected: Folder::class, actual: $folder);
		$pdf = file_get_contents(__DIR__ . '/../sample-documents/pdfa/pdfa3b-rendered.pdf');
		$this->assertIsString(actual: $pdf);
		$file = $folder->newFile('batch.pdf', $pdf);

		$event = new WatchedFileArrivedEvent(
			synchronizationId: 'sync-' . $this->folder,
			sourceId: 'source-' . $this->folder,
			intakeApp: 'filinq',
			fileId: (int)$file->getId(),
			path: $this->folder . '/batch.pdf',
			ownerUid: $this->user,
		);

		Server::get(IEventDispatcher::class)->dispatchTyped($event);

		$batch = Server::get(ScanBatchServiceInterface::class)->storedFor(fileId: (int)$file->getId());
		$this->assertIsArray(actual: $batch, message: 'The listener stored no batch for the handed-over file.');
		$this->assertNotSame(expected: '', actual: (string)($batch['uuid'] ?? ''));
		$this->assertSame(
			expected: ['accepted' => true, 'intakeApp' => 'filinq', 'reference' => (string)$batch['uuid']],
			actual: $event->getResult()
		);

	}//end testTheDispatcherReachesTheListener()
}//end class
