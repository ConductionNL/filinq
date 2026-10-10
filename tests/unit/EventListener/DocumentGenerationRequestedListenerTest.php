<?php

/**
 * Unit tests for DocumentGenerationRequestedListener
 *
 * The event round trip, end to end inside Filinq: a REAL
 * DocumentGenerationRequestedEvent goes into the listener, the listener
 * drives the REAL DocumentGenerationRequestService, and the result (or the
 * error) comes back on the same event instance, with a REAL
 * DocumentGeneratedEvent dispatched on success. Only the service's
 * boundaries are doubled.
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
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-other-apps-request-a-document-through-a-command-event
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\DocumentGeneratedEvent;
use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\EventListener\DocumentGenerationRequestedListener;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for DocumentGenerationRequestedListener.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentGenerationRequestedListenerTest extends TestCase {

	/**
	 * The render, store and audit path.
	 *
	 * @var DocumentService&MockObject
	 */
	private DocumentService&MockObject $documents;

	/**
	 * Every event the service dispatched.
	 *
	 * @var array<int, object>
	 */
	private array $dispatched = [];

	/**
	 * Build the listener over the real service.
	 *
	 * @param LoggerInterface|null $logger The logger, when a test watches it.
	 *
	 * @return DocumentGenerationRequestedListener
	 */
	private function listener(?LoggerInterface $logger = null): DocumentGenerationRequestedListener {
		$this->documents = $this->createMock(DocumentService::class);
		$this->documents->method('buildOutputTargetPath')->willReturn('DocuDesk/dossiq');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('behandelaar');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$this->dispatched = [];
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(
			function (object $event): void {
				$this->dispatched[] = $event;
			}
		);

		$service = new DocumentGenerationRequestService(
			documents: $this->documents,
			templates: $this->createMock(TemplateService::class),
			slugs: $this->createMock(TemplateSlugResolver::class),
			renderer: $this->createMock(TemplateRenderer::class),
			objectResolver: $this->createMock(DocumentObjectServiceResolver::class),
			userSession: $session,
			eventDispatcher: $dispatcher
		);

		return new DocumentGenerationRequestedListener(
			generator: $service,
			logger: ($logger ?? $this->createMock(LoggerInterface::class))
		);
	}//end listener()

	/**
	 * A handled request carries the document back and announces it.
	 *
	 * @return void
	 */
	public function testRoundTripWritesTheResultAndAnnouncesTheDocument(): void {
		$listener = $this->listener();
		$this->documents->expects($this->once())
			->method('generateFromTemplate')
			->willReturn(
				[
					'content' => '%PDF',
					'html' => '<p>x</p>',
					'format' => 'pdf',
					'metadata' => [],
					'warnings' => [],
					'output' => [
						'mode' => 'files',
						'fileId' => 42,
						'path' => '/behandelaar/files/DocuDesk/dossiq/case-1/Besluit.pdf',
						'name' => 'Besluit.pdf',
						'size' => 99,
					],
				]
			);

		$event = new DocumentGenerationRequestedEvent(
			request: [
				'template' => 'Besluit voor {{ title }}',
				'filename' => 'Besluit',
				'data' => ['title' => 'Aanvraag'],
				'object' => ['register' => 'zaken', 'schema' => 'case', 'id' => 'case-1'],
				'metadata' => ['informatieobjecttype' => 'besluit', 'addressees' => ['party-1']],
			],
			requestingApp: 'dossiq'
		);

		$listener->handle($event);

		$this->assertTrue($event->isHandled());
		$this->assertNull($event->getError());
		$this->assertSame(42, $event->getResult()['fileId']);
		$this->assertSame('dossiq', $event->getResult()['requestingApp']);

		$this->assertCount(1, $this->dispatched);
		$generated = $this->dispatched[0];
		$this->assertInstanceOf(DocumentGeneratedEvent::class, $generated);
		$this->assertSame(42, $generated->getFileId());
		$this->assertSame(['register' => 'zaken', 'schema' => 'case', 'id' => 'case-1'], $generated->getObject());
		$this->assertSame(
			['informatieobjecttype' => 'besluit', 'addressees' => ['party-1']],
			$generated->getMetadata(),
			'metadata is passed through untouched'
		);
		$this->assertSame('dossiq', $generated->getRequestingApp());
		$this->assertSame('inline', $generated->getTemplate()['source']);
		$this->assertSame($event->getResult(), $generated->getDocument());
	}//end testRoundTripWritesTheResultAndAnnouncesTheDocument()

	/**
	 * A refused request comes back with its reason and nothing announced.
	 *
	 * @return void
	 */
	public function testARefusalIsWrittenToTheErrorSlotAndLogged(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error');
		$listener = $this->listener(logger: $logger);
		$this->documents->expects($this->never())->method('generateFromTemplate');

		$event = new DocumentGenerationRequestedEvent(request: ['format' => 'pdf'], requestingApp: 'dossiq');
		$listener->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertNull($event->getResult());
		$this->assertStringContainsString('exactly one template', (string)$event->getError());
		$this->assertSame([], $this->dispatched);
	}//end testARefusalIsWrittenToTheErrorSlotAndLogged()

	/**
	 * Other events are ignored.
	 *
	 * @return void
	 */
	public function testOtherEventsAreIgnored(): void {
		$listener = $this->listener();
		$this->documents->expects($this->never())->method('generateFromTemplate');

		$listener->handle(new Event());

		$this->assertSame([], $this->dispatched);
	}//end testOtherEventsAreIgnored()

	/**
	 * An event nobody handled reads as neither handled nor refused.
	 *
	 * @return void
	 */
	public function testAnUnhandledEventIsDistinguishableFromARefusal(): void {
		$event = new DocumentGenerationRequestedEvent(request: [], requestingApp: 'dossiq');

		$this->assertFalse($event->isHandled());
		$this->assertNull($event->getError());
		$this->assertSame([], $event->getRequest());
		$this->assertSame('dossiq', $event->getRequestingApp());
	}//end testAnUnhandledEventIsDistinguishableFromARefusal()
}//end class
