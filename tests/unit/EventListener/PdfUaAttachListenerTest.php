<?php

/**
 * The PDF/UA attach listener queues one check for a PDF attached to a
 * register object, through the real file events, and does nothing else.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\BackgroundJob\PdfUaAttachCheckJob;
use OCA\Filinq\EventListener\PdfUaAttachListener;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @covers \OCA\Filinq\EventListener\PdfUaAttachListener
 * @covers \OCA\Filinq\EventListener\PdfUaEventRegistrar
 * @uses   \OCA\Filinq\BackgroundJob\JobQueue
 */
class PdfUaAttachListenerTest extends TestCase {

	/**
	 * The jobs the real queue handed to the job list.
	 *
	 * @var array<int, array{0: string, 1: mixed}>
	 */
	private array $queued = [];

	/**
	 * The listener over the real queue and a recording job list.
	 *
	 * @param LoggerInterface|null $logger The log.
	 *
	 * @return PdfUaAttachListener The listener.
	 */
	private function listener(?LoggerInterface $logger = null): PdfUaAttachListener {
		$jobList = $this->createMock(IJobList::class);
		$jobList->method('add')->willReturnCallback(
			function ($job, $argument = null): void {
				$this->queued[] = [$job, $argument];
			}
		);

		return new PdfUaAttachListener(new JobQueue($jobList), $logger ?? new NullLogger());

	}//end listener()

	/**
	 * A file node.
	 *
	 * @param string $path The node path.
	 * @param string $mime The MIME type.
	 *
	 * @return File The file.
	 */
	private function file(string $path, string $mime = 'application/pdf'): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(812);
		$file->method('getPath')->willReturn($path);
		$file->method('getMimetype')->willReturn($mime);
		$file->expects($this->never())->method('getContent');

		return $file;

	}//end file()

	public function testTheListenerOnlyQueues(): void {
		$conformance = $this->createMock(AccessibilityConformanceService::class);
		$conformance->expects($this->never())->method($this->anything());

		$this->listener()->handle(new NodeCreatedEvent($this->file(path: '/petra/files/Open Registers/Publicaties/besluit.pdf')));

		$this->assertSame([[PdfUaAttachCheckJob::class, ['fileId' => 812]]], $this->queued);

	}//end testTheListenerOnlyQueues()

	public function testANewVersionIsCheckedAgain(): void {
		$this->listener()->handle(new NodeWrittenEvent($this->file(path: '/petra/files/Open Registers/Publicaties/besluit.pdf')));

		$this->assertSame([[PdfUaAttachCheckJob::class, ['fileId' => 812]]], $this->queued);

	}//end testANewVersionIsCheckedAgain()

	public function testAFileOutsideOpenRegisterIsIgnored(): void {
		$listener = $this->listener();
		$listener->handle(new NodeCreatedEvent($this->file(path: '/petra/files/Documents/besluit.pdf')));
		$listener->handle(new NodeCreatedEvent($this->file(path: '/petra/files/Documents/Open Registers/besluit.pdf')));
		$listener->handle(new NodeCreatedEvent($this->file(path: '/petra/files/Open Registers')));

		$this->assertSame([], $this->queued);

	}//end testAFileOutsideOpenRegisterIsIgnored()

	public function testANonPdfIsIgnored(): void {
		$this->listener()->handle(new NodeCreatedEvent($this->file(path: '/petra/files/Open Registers/notitie.docx', mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')));

		$this->assertSame([], $this->queued);

	}//end testANonPdfIsIgnored()

	public function testAFolderAndOtherEventsAreIgnored(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/petra/files/Open Registers/Publicaties');
		$listener = $this->listener();
		$listener->handle(new NodeCreatedEvent($folder));
		$listener->handle(new Event());

		$this->assertSame([], $this->queued);

	}//end testAFolderAndOtherEventsAreIgnored()

	public function testAFailingQueueDoesNotFailTheUpload(): void {
		$file = $this->createMock(File::class);
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getPath')->willReturn('/petra/files/Open Registers/besluit.pdf');
		$file->method('getId')->willThrowException(new NotFoundException('gone'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with('PdfUaAttachListener: could not queue the PDF/UA check');

		$this->listener(logger: $logger)->handle(new NodeCreatedEvent($file));

		$this->assertSame([], $this->queued);

	}//end testAFailingQueueDoesNotFailTheUpload()

	public function testTheBootstrapRegistersTheListenerForBothEvents(): void {
		$context = new class implements IRegistrationContext {
			/**
			 * The (event, listener) pairs registered.
			 *
			 * @var array<int, array{0: string, 1: string}>
			 */
			public array $pairs = [];

			public function registerService(string $name, callable $factory, bool $shared = true): void {
			}

			public function registerAlias(string $alias, string $target): void {
			}

			public function registerServiceAlias(string $alias, string $target): void {
			}

			public function registerParameter(string $name, mixed $value): void {
			}

			public function registerEventListener(string $event, string $listener, int $priority = 0): void {
				unset($priority);
				$this->pairs[] = [$event, $listener];
			}

			/**
			 * Every other registration member, ignored.
			 *
			 * @param string            $name      The member.
			 * @param array<int, mixed> $arguments Its arguments.
			 *
			 * @return void
			 */
			public function __call(string $name, array $arguments): void {
				unset($name, $arguments);
			}
		};

		(new RegistrationBootstrap())->register(context: $context);

		$this->assertContains([NodeCreatedEvent::class, PdfUaAttachListener::class], $context->pairs);
		$this->assertContains([NodeWrittenEvent::class, PdfUaAttachListener::class], $context->pairs);

	}//end testTheBootstrapRegistersTheListenerForBothEvents()
}//end class
