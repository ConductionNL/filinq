<?php

/**
 * Unit tests for the signer lifecycle events
 *
 * Verifies the payload the pending-step and chain-completed events carry when
 * SignerEventTranslator raises them, including the nullable role and user id
 * a role-less step or an unattributed completion produces, and that both are
 * ordinary Nextcloud events whose propagation a listener can stop.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Event;

use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\Event\SignerChainCompletedEvent;
use OCA\Filinq\Event\SignerStepPendingEvent;
use OCA\Filinq\Event\SigningProvenance;
use OCA\Filinq\EventListener\SignerEventTranslator;
use OCA\Filinq\Kernel\Contracts\ActiveSigningProviderInterface;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for SignerStepPendingEvent, SignerChainCompletedEvent and the
 * provenance accessor of DocumentSigningRequestedEvent.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(SignerStepPendingEvent::class)]
#[CoversClass(SignerChainCompletedEvent::class)]
#[CoversClass(DocumentSigningRequestedEvent::class)]
#[UsesClass(SignerEventTranslator::class)]
#[UsesClass(SigningProvenance::class)]
class SignerLifecycleEventsTest extends TestCase {

	/**
	 * Events captured from the dispatcher.
	 *
	 * @var list<Event>
	 */
	private array $dispatched = [];

	/**
	 * Translator wired to a capturing dispatcher.
	 *
	 * @var SignerEventTranslator
	 */
	private SignerEventTranslator $translator;

	/**
	 * Wire the translator with a dispatcher that records what it receives.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->dispatched = [];
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')
			->willReturnCallback(
				function (Event $event): void {
					$this->dispatched[] = $event;
				}
			);

		$provider = $this->createMock(ActiveSigningProviderInterface::class);
		$provider->method('getActiveProviderIdentifier')->willReturn('native');

		$this->translator = new SignerEventTranslator(
			providerFactory: $provider,
			dispatcher: $dispatcher,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * A role-less pending step keeps its role null and every other field in place.
	 *
	 * @return void
	 */
	public function testPendingStepWithoutRoleKeepsRoleNull(): void {
		$this->translator->onPositionEnabled(
			sequenceUuid: 'seq-9',
			taskUuid: 'task-3',
			position: 3,
			role: null,
			objectUuid: 'sign-req-9'
		);

		$this->assertCount(1, $this->dispatched);
		$event = $this->dispatched[0];
		$this->assertInstanceOf(SignerStepPendingEvent::class, $event);
		$this->assertSame('seq-9', $event->getSequenceUuid());
		$this->assertSame('task-3', $event->getTaskUuid());
		$this->assertSame(3, $event->getPosition());
		$this->assertNull($event->getRole());
		$this->assertSame('sign-req-9', $event->getObjectUuid());
	}//end testPendingStepWithoutRoleKeepsRoleNull()

	/**
	 * A completed chain carries the final task, signer and status verbatim.
	 *
	 * @return void
	 */
	public function testChainCompletedCarriesFinalTaskAndStatus(): void {
		$this->translator->onSequenceCompleted(
			sequenceUuid: 'seq-2',
			finalTaskUuid: 'task-final',
			userId: 'carol',
			statusOnApprove: 'signed',
			objectUuid: 'sign-req-2'
		);

		$this->assertCount(1, $this->dispatched);
		$event = $this->dispatched[0];
		$this->assertInstanceOf(SignerChainCompletedEvent::class, $event);
		$this->assertSame('seq-2', $event->getSequenceUuid());
		$this->assertSame('task-final', $event->getFinalTaskUuid());
		$this->assertSame('carol', $event->getUserId());
		$this->assertSame('signed', $event->getStatusOnApprove());
		$this->assertSame('sign-req-2', $event->getObjectUuid());
	}//end testChainCompletedCarriesFinalTaskAndStatus()

	/**
	 * An unattributed completion keeps the user id null, and a listener can
	 * stop the event's propagation.
	 *
	 * @return void
	 */
	public function testUnattributedCompletionKeepsUserNullAndCanBeStopped(): void {
		$this->translator->onSequenceCompleted(
			sequenceUuid: 'seq-3',
			finalTaskUuid: 'task-x',
			userId: null,
			statusOnApprove: 'approved',
			objectUuid: 'sign-req-3'
		);

		$event = $this->dispatched[0];
		$this->assertInstanceOf(SignerChainCompletedEvent::class, $event);
		$this->assertNull($event->getUserId());
		$this->assertFalse($event->isPropagationStopped());
		$event->stopPropagation();
		$this->assertTrue($event->isPropagationStopped());
	}//end testUnattributedCompletionKeepsUserNullAndCanBeStopped()

	/**
	 * The signing request hands back the exact provenance it was built with,
	 * and its delegating accessors read from that same block.
	 *
	 * @return void
	 */
	public function testSigningRequestReturnsItsProvenance(): void {
		$provenance = new SigningProvenance(
			sourceApp: 'dossiq',
			subjectRegister: 'zaken',
			subjectSchema: 'besluit',
			subjectId: 'b-7',
			externalReference: 'ext-7',
			correlationId: 'corr-7'
		);
		$event = new DocumentSigningRequestedEvent(provenance: $provenance);

		$this->assertSame($provenance, $event->getProvenance());
		$this->assertSame('dossiq', $event->getSourceApp());
		$this->assertSame('b-7', $event->getSubjectId());
		$this->assertSame('corr-7', $event->getCorrelationId());
		$this->assertSame('SES', $event->getSignatureLevel());
		$this->assertSame('sequential', $event->getSigningMode());
	}//end testSigningRequestReturnsItsProvenance()
}//end class
