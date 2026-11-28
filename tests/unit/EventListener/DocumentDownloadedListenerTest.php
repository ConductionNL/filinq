<?php

/**
 * Unit tests for DocumentDownloadedListener
 *
 * A person's direct download of a file is the `downloaded` action of the
 * document it is, written through the download log with route `files`; a
 * refused download, a folder, a missing path or no session writes nothing
 * (specify-capability-rows REQ-SCR-007).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\DocumentDownloadedListener;
use OCA\Filinq\Service\Audit\DocumentDownloadLog;
use OCP\EventDispatcher\Event;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

// The OCP file events are not autoloaded in the unit bootstrap
// (vendor/nextcloud/ocp has no autoload entry). Load them, and only them,
// from the OCP package the first time a test here names one.
spl_autoload_register(
	static function (string $class): void {
		foreach (['OCP\\Files\\Events\\', 'OCP\\EventDispatcher\\'] as $prefix) {
			if (str_starts_with($class, $prefix) === true) {
				$path = __DIR__ . '/../../../vendor/nextcloud/ocp/' . str_replace('\\', '/', $class) . '.php';
				if (is_file($path) === true) {
					require_once $path;
				}

				return;
			}
		}
	}
);

/**
 * @covers \OCA\Filinq\EventListener\DocumentDownloadedListener
 */
#[CoversClass(DocumentDownloadedListener::class)]
class DocumentDownloadedListenerTest extends TestCase {

	/**
	 * A downloaded file is one `downloaded` entry, route `files`, on that file.
	 *
	 * @return void
	 */
	public function testADownloadedFileIsDownloaded(): void {
		$file = $this->createMock(originalClassName: File::class);
		$file->expects($this->any())->method(constraint: 'getId')->willReturn(41);
		$downloads = $this->createMock(originalClassName: DocumentDownloadLog::class);
		$downloads->expects($this->once())->method('downloaded')->with(fileId: 41, version: '', route: 'files')->willReturn(value: true);

		$this->listener(downloads: $downloads, node: $file, path: 'Cases/letter.pdf')
			->handle(event: new BeforeDirectFileDownloadEvent(path: '/Cases/letter.pdf'));

	}//end testADownloadedFileIsDownloaded()

	/**
	 * A download another listener refused did not happen and is not written.
	 *
	 * @return void
	 */
	public function testARefusedDownloadIsNotWritten(): void {
		$downloads = $this->createMock(originalClassName: DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');
		$event = new BeforeDirectFileDownloadEvent(path: '/Cases/letter.pdf');
		$event->setSuccessful(successful: false);

		$this->listener(downloads: $downloads, node: $this->createMock(originalClassName: File::class), path: 'Cases/letter.pdf')->handle(event: $event);

	}//end testARefusedDownloadIsNotWritten()

	/**
	 * A folder, the root and another event write nothing.
	 *
	 * @return void
	 */
	public function testNothingButADownloadedFileIsWritten(): void {
		$downloads = $this->createMock(originalClassName: DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');

		$this->listener(downloads: $downloads, node: $this->createMock(originalClassName: Folder::class), path: 'Cases')
			->handle(event: new BeforeDirectFileDownloadEvent(path: '/Cases'));
		$this->listener(downloads: $downloads, node: $this->createMock(originalClassName: File::class), path: '')
			->handle(event: new BeforeDirectFileDownloadEvent(path: '/'));
		$this->listener(downloads: $downloads, node: $this->createMock(originalClassName: File::class), path: 'x')
			->handle(event: new Event());

	}//end testNothingButADownloadedFileIsWritten()

	/**
	 * Without a signed-in user nothing is resolved or written.
	 *
	 * @return void
	 */
	public function testWithoutAUserNothingIsWritten(): void {
		$downloads = $this->createMock(originalClassName: DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');
		$root = $this->createMock(originalClassName: IRootFolder::class);
		$root->expects($this->never())->method('getUserFolder');
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->expects($this->any())->method(constraint: 'getUser')->willReturn(value: null);

		$listener = new DocumentDownloadedListener(
			downloads: $downloads,
			rootFolder: $root,
			userSession: $session,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);
		$listener->handle(event: new BeforeDirectFileDownloadEvent(path: '/Cases/letter.pdf'));

	}//end testWithoutAUserNothingIsWritten()

	/**
	 * A path that cannot be resolved is logged and never fails the download.
	 *
	 * @return void
	 */
	public function testAnUnresolvablePathIsLogged(): void {
		$downloads = $this->createMock(originalClassName: DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');
		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->expects($this->any())->method(constraint: 'get')->willThrowException(exception: new NotFoundException(message: 'gone'));
		$root = $this->createMock(originalClassName: IRootFolder::class);
		$root->expects($this->any())->method(constraint: 'getUserFolder')->willReturn(value: $folder);
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		(new DocumentDownloadedListener(downloads: $downloads, rootFolder: $root, userSession: $this->session(), logger: $logger))
			->handle(event: new BeforeDirectFileDownloadEvent(path: '/Cases/gone.pdf'));

	}//end testAnUnresolvablePathIsLogged()

	/**
	 * The listener for anna, whose files hold one node at a path.
	 *
	 * @param DocumentDownloadLog $downloads The download log double.
	 * @param mixed               $node      The node at the path.
	 * @param string              $path      The path, relative to anna's files.
	 *
	 * @return DocumentDownloadedListener
	 */
	private function listener(DocumentDownloadLog $downloads, mixed $node, string $path): DocumentDownloadedListener {
		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->expects($this->any())->method(constraint: 'get')->with($path)->willReturn(value: $node);
		$root = $this->createMock(originalClassName: IRootFolder::class);
		$root->expects($this->any())->method(constraint: 'getUserFolder')->with('anna')->willReturn(value: $folder);

		return new DocumentDownloadedListener(
			downloads: $downloads,
			rootFolder: $root,
			userSession: $this->session(),
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end listener()

	/**
	 * A session for anna.
	 *
	 * @return IUserSession
	 */
	private function session(): IUserSession {
		$user = $this->createMock(originalClassName: IUser::class);
		$user->expects($this->any())->method(constraint: 'getUID')->willReturn(value: 'anna');
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->expects($this->any())->method(constraint: 'getUser')->willReturn(value: $user);

		return $session;

	}//end session()
}//end class
