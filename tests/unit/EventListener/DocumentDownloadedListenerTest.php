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
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
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
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(41);
		$downloads = $this->createMock(DocumentDownloadLog::class);
		$downloads->expects($this->once())->method('downloaded')->with(fileId: 41, version: '', route: 'files')->willReturn(true);

		$this->listener(downloads: $downloads, node: $file, path: 'Cases/letter.pdf')
			->handle(new BeforeDirectFileDownloadEvent('/Cases/letter.pdf'));

	}//end testADownloadedFileIsDownloaded()

	/**
	 * A download another listener refused did not happen and is not written.
	 *
	 * @return void
	 */
	public function testARefusedDownloadIsNotWritten(): void {
		$downloads = $this->createMock(DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');
		$event = new BeforeDirectFileDownloadEvent('/Cases/letter.pdf');
		$event->setSuccessful(false);

		$this->listener(downloads: $downloads, node: $this->createMock(File::class), path: 'Cases/letter.pdf')->handle($event);

	}//end testARefusedDownloadIsNotWritten()

	/**
	 * A folder, the root and another event write nothing.
	 *
	 * @return void
	 */
	public function testNothingButADownloadedFileIsWritten(): void {
		$downloads = $this->createMock(DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');

		$this->listener(downloads: $downloads, node: $this->createMock(Folder::class), path: 'Cases')
			->handle(new BeforeDirectFileDownloadEvent('/Cases'));
		$this->listener(downloads: $downloads, node: $this->createMock(File::class), path: '')
			->handle(new BeforeDirectFileDownloadEvent('/'));
		$this->listener(downloads: $downloads, node: $this->createMock(File::class), path: 'x')
			->handle(new Event());

	}//end testNothingButADownloadedFileIsWritten()

	/**
	 * Without a signed-in user nothing is resolved or written.
	 *
	 * @return void
	 */
	public function testWithoutAUserNothingIsWritten(): void {
		$downloads = $this->createMock(DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');
		$root = $this->createMock(IRootFolder::class);
		$root->expects($this->never())->method('getUserFolder');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);

		(new DocumentDownloadedListener($downloads, $root, $session, $this->createMock(LoggerInterface::class)))
			->handle(new BeforeDirectFileDownloadEvent('/Cases/letter.pdf'));

	}//end testWithoutAUserNothingIsWritten()

	/**
	 * A path that cannot be resolved is logged and never fails the download.
	 *
	 * @return void
	 */
	public function testAnUnresolvablePathIsLogged(): void {
		$downloads = $this->createMock(DocumentDownloadLog::class);
		$downloads->expects($this->never())->method('downloaded');
		$folder = $this->createMock(Folder::class);
		$folder->method('get')->willThrowException(new NotFoundException('gone'));
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		(new DocumentDownloadedListener($downloads, $root, $this->session(), $logger))
			->handle(new BeforeDirectFileDownloadEvent('/Cases/gone.pdf'));

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
		$folder = $this->createMock(Folder::class);
		$folder->method('get')->with($path)->willReturn($node);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('anna')->willReturn($folder);

		return new DocumentDownloadedListener($downloads, $root, $this->session(), $this->createMock(LoggerInterface::class));

	}//end listener()

	/**
	 * A session for anna.
	 *
	 * @return IUserSession
	 */
	private function session(): IUserSession {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return $session;

	}//end session()
}//end class
