<?php

/**
 * Unit tests for DocumentFileActionListener
 *
 * A written file is edited, a file about to be deleted is deleted and a new
 * file share is shared, each written into the document's trail; anything
 * else writes nothing (specify-capability-rows REQ-SCR-007).
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

use OCA\Filinq\EventListener\DocumentFileActionListener;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCP\EventDispatcher\Event;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

// The OCP file and share events are not autoloaded in the unit bootstrap
// (vendor/nextcloud/ocp has no autoload entry). Load them, and only them,
// from the OCP package the first time a test here names one.
spl_autoload_register(
	static function (string $class): void {
		foreach (['OCP\\Files\\Events\\', 'OCP\\Share\\', 'OCP\\EventDispatcher\\'] as $prefix) {
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
 * @covers \OCA\Filinq\EventListener\DocumentFileActionListener
 */
#[CoversClass(DocumentFileActionListener::class)]
class DocumentFileActionListenerTest extends TestCase {

	/**
	 * A written file is an edit of the document.
	 *
	 * @return void
	 */
	public function testAWrittenFileIsEdited(): void {
		$this->expectAction(event: new NodeWrittenEvent($this->file(id: 41)), fileId: 41, action: 'edited');

	}//end testAWrittenFileIsEdited()

	/**
	 * A file about to be deleted is a deletion of the document.
	 *
	 * @return void
	 */
	public function testAFileAboutToBeDeletedIsDeleted(): void {
		$this->expectAction(event: new BeforeNodeDeletedEvent($this->file(id: 42)), fileId: 42, action: 'deleted');

	}//end testAFileAboutToBeDeletedIsDeleted()

	/**
	 * A new share of a file is a share of the document.
	 *
	 * @return void
	 */
	public function testANewFileShareIsShared(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getNodeType')->willReturn('file');
		$share->method('getNodeId')->willReturn(43);

		$this->expectAction(event: new ShareCreatedEvent($share), fileId: 43, action: 'shared');

	}//end testANewFileShareIsShared()

	/**
	 * A folder, a folder share and an unrelated event write nothing.
	 *
	 * @return void
	 */
	public function testNothingButAFileIsAudited(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->never())->method('record');
		$listener = new DocumentFileActionListener($audit, $this->createMock(LoggerInterface::class));
		$folderShare = $this->createMock(IShare::class);
		$folderShare->method('getNodeType')->willReturn('folder');

		$listener->handle(new NodeWrittenEvent($this->createMock(Folder::class)));
		$listener->handle(new ShareCreatedEvent($folderShare));
		$listener->handle(new Event());

	}//end testNothingButAFileIsAudited()

	/**
	 * A failing audit never fails the file action: it is logged.
	 *
	 * @return void
	 */
	public function testAFailingAuditIsLogged(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->method('record')->willThrowException(new RuntimeException('down'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		(new DocumentFileActionListener($audit, $logger))->handle(new NodeWrittenEvent($this->file(id: 41)));

	}//end testAFailingAuditIsLogged()

	/**
	 * Handle the event and expect exactly this one action to be recorded.
	 *
	 * @param Event  $event  The event.
	 * @param int    $fileId The file it is about.
	 * @param string $action The action expected.
	 *
	 * @return void
	 */
	private function expectAction(Event $event, int $fileId, string $action): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->once())->method('record')->with(fileId: $fileId, action: $action)->willReturn(true);

		(new DocumentFileActionListener($audit, $this->createMock(LoggerInterface::class)))->handle($event);

	}//end expectAction()

	/**
	 * A file with this id.
	 *
	 * @param int $id The file id.
	 *
	 * @return File
	 */
	private function file(int $id): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);

		return $file;

	}//end file()
}//end class
