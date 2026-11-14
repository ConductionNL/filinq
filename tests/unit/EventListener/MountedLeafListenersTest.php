<?php

/**
 * Unit tests for the five listeners that register a mounted Filinq leaf.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
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

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\RegisterDocumentIntakeLeafListener;
use OCA\Filinq\EventListener\RegisterDocumentsLeafListener;
use OCA\Filinq\EventListener\RegisterDownloadAllFilesLeafListener;
use OCA\Filinq\EventListener\RegisterMergeToPdfLeafListener;
use OCA\Filinq\EventListener\RegisterSigningFolderLeafListener;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Pins the whole descriptor, the ignored events and the failure log of each listener.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\EventListener\MountedLeafListener
 * @covers \OCA\Filinq\EventListener\RegisterDocumentIntakeLeafListener
 * @covers \OCA\Filinq\EventListener\RegisterDocumentsLeafListener
 * @covers \OCA\Filinq\EventListener\RegisterDownloadAllFilesLeafListener
 * @covers \OCA\Filinq\EventListener\RegisterMergeToPdfLeafListener
 * @covers \OCA\Filinq\EventListener\RegisterSigningFolderLeafListener
 */
class MountedLeafListenersTest extends TestCase {

	/**
	 * Listener, id, label, icon, group, surfaces.
	 *
	 * @return array<string, array{0: class-string, 1: string, 2: string, 3: string, 4: string, 5: array<int, string>}>
	 */
	public static function listeners(): array {
		return [
			'DocumentIntake' => [RegisterDocumentIntakeLeafListener::class, 'filinq-document-intake', 'Documents waiting to be filed', 'InboxArrowDown', 'documents', ['detail-page', 'single-entity']],
			'Documents' => [RegisterDocumentsLeafListener::class, 'filinq-documents', 'Documents', 'FileDocumentMultipleOutline', 'documents', ['detail-page', 'single-entity']],
			'DownloadAllFiles' => [RegisterDownloadAllFilesLeafListener::class, 'filinq-download-all-files', 'Download all files', 'FolderZipOutline', 'documents', ['detail-page', 'single-entity']],
			'MergeToPdf' => [RegisterMergeToPdfLeafListener::class, 'filinq-merge-to-pdf', 'Merge to PDF', 'FilePdfBox', 'documents', ['detail-page', 'single-entity']],
			'SigningFolder' => [RegisterSigningFolderLeafListener::class, 'filinq-signing-folder', 'Waiting for your signature', 'FileSign', 'signing', ['user-dashboard', 'app-dashboard']],
		];
	}

	/**
	 * Build a listener with a marking translator.
	 *
	 * @param class-string $class The listener.
	 * @param LoggerInterface|null $logger Optional logger.
	 *
	 * @return object
	 */
	private function listener(string $class, ?LoggerInterface $logger = null): object {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => '[nl] ' . $text);
		return new $class($l10n, ($logger ?? $this->createMock(LoggerInterface::class)));
	}

	/**
	 * @dataProvider listeners
	 *
	 * @param class-string $class The listener.
	 * @param string $id The leaf id.
	 * @param string $label The untranslated label.
	 * @param string $icon The icon.
	 * @param string $group The group.
	 * @param array<int, string> $surfaces The surfaces.
	 */
	public function testRegistersTheWholeDescriptor(string $class, string $id, string $label, string $icon, string $group, array $surfaces): void {
		$event = new RegisterLeafProvidersEvent();
		$this->listener(class: $class)->handle($event);

		$leaves = $event->getLeaves();
		$this->assertCount(1, $leaves);
		$this->assertNull($leaves[0]['provider']);
		$descriptor = $leaves[0]['descriptor'];
		$this->assertSame(
			[$id, '[nl] ' . $label, $icon, [LeafDescriptor::KIND_RENDER_SURFACE], 'filinq', $group, $surfaces, $id, null, LeafDescriptor::RENDER_MODE_MOUNT],
			[
				$descriptor->getId(), $descriptor->getLabel(), $descriptor->getIcon(), $descriptor->getKinds(), $descriptor->getRequiredApp(),
				$descriptor->getGroup(), $descriptor->getSurfaces(), $descriptor->getReferenceType(), $descriptor->getRequiresPermission(), $descriptor->getRenderMode(),
			]
		);
	}

	/**
	 * @dataProvider listeners
	 *
	 * @param class-string $class The listener.
	 * @param string $id The leaf id.
	 */
	public function testIgnoresOtherEventsAndLogsAFailedRegistration(string $class, string $id): void {
		$quiet = $this->createMock(LoggerInterface::class);
		$quiet->expects($this->never())->method('warning');
		$this->listener(class: $class, logger: $quiet)->handle(new Event());

		$boom = new RuntimeException('catalogue closed');
		$event = $this->createMock(RegisterLeafProvidersEvent::class);
		$event->method('registerLeaf')->willThrowException($boom);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'Filinq could not register the ' . $id . ' leaf: catalogue closed',
			['exception' => $boom]
		);

		$this->listener(class: $class, logger: $logger)->handle($event);
	}
}
