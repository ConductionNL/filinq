<?php

/**
 * Characterization tests for DocumentSigningRequestedListener::handle()
 *
 * Pins the exact request data built from the event, which id marks the
 * event handled, that a missing id leaves it unhandled, and that any failure
 * is logged instead of thrown.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use Error;
use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\Event\SigningProvenance;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the signing-request listener.
 *
 * @covers \OCA\Filinq\EventListener\DocumentSigningRequestedListener
 * @uses   \OCA\Filinq\Event\DocumentSigningRequestedEvent
 * @uses   \OCA\Filinq\Event\SigningProvenance
 */
class DocumentSigningRequestedListenerTraceTest extends TestCase {

	private const SIGNERS = [['userId' => 'bob', 'order' => 1], ['email' => 'guardian@example.org', 'guardianOf' => 'kim']];

	private SigningServiceInterface&MockObject $signing;

	/**
	 * @var array<int, array{0: string, 1: string, 2: array<string, mixed>}> Every log line.
	 */
	private array $logs = [];

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningServiceInterface::class);

	}//end setUp()

	/**
	 * The request data mirrors the event; an `id` marks the event handled and is logged.
	 *
	 * @return void
	 */
	public function testCreatedRequestMarksTheEventHandled(): void {
		$this->signing->expects($this->once())->method('createRequest')->with(
			$this->identicalTo(
				[
					'documentFileId' => 'file-99',
					'documentName' => 'Invoice 42',
					'signatureLevel' => 'QES',
					'signingMode' => 'parallel',
					'signers' => self::SIGNERS,
					'sourceApp' => 'shillinq',
					'subjectRegister' => 'finance',
					'subjectSchema' => 'invoice',
					'subjectId' => 'inv-42',
					'subjectLabel' => 'Invoice 42',
					'externalReference' => 'ext-42',
					'correlationId' => 'corr-42',
				]
			)
		)->willReturn(['id' => 'req-7', 'uuid' => 'uuid-7']);

		$event = $this->event();
		$this->listener()->handle($event);

		$this->assertTrue($event->isHandled());
		$this->assertSame('req-7', $event->getSigningRequestId());
		$this->assertSame(
			[['info', 'Filinq: handled DocumentSigningRequestedEvent', ['sourceApp' => 'shillinq', 'subjectId' => 'inv-42', 'signingRequestId' => 'req-7']]],
			$this->logs
		);

	}//end testCreatedRequestMarksTheEventHandled()

	/**
	 * Without an `id` the `uuid` is used, and a numeric id is read as text.
	 *
	 * @return void
	 */
	public function testUuidAndNumericIds(): void {
		$this->signing->method('createRequest')->willReturnOnConsecutiveCalls(['uuid' => 'uuid-8'], ['id' => 9], ['id' => null, 'uuid' => 'uuid-10']);

		$ids = [];
		foreach ([1, 2, 3] as $ignored) {
			$event = $this->event();
			$this->listener()->handle($event);
			$this->assertTrue($event->isHandled());
			$ids[] = $event->getSigningRequestId();
		}

		$this->assertSame(['uuid-8', '9', 'uuid-10'], $ids);

	}//end testUuidAndNumericIds()

	/**
	 * A request without an id, or with an empty one, leaves the event unhandled with a warning.
	 *
	 * @return void
	 */
	public function testNoIdLeavesTheEventUnhandled(): void {
		$this->signing->method('createRequest')->willReturnOnConsecutiveCalls([], ['id' => '']);

		foreach ([1, 2] as $ignored) {
			$event = $this->event();
			$this->listener()->handle($event);
			$this->assertFalse($event->isHandled());
			$this->assertNull($event->getSigningRequestId());
		}

		$warning = ['warning', 'Filinq: DocumentSigningRequestedEvent not handled (no request id)', ['sourceApp' => 'shillinq', 'subjectId' => 'inv-42']];
		$this->assertSame([$warning, $warning], $this->logs);

	}//end testNoIdLeavesTheEventUnhandled()

	/**
	 * Any failure, an Error included, is logged and the event stays unhandled.
	 *
	 * @return void
	 */
	public function testFailureIsLoggedNotThrown(): void {
		$this->signing->method('createRequest')->willThrowException(new Error('provider gone'));

		$event = $this->event();
		$this->listener()->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertSame(
			[['error', 'Filinq: DocumentSigningRequestedListener failed', ['sourceApp' => 'shillinq', 'subjectId' => 'inv-42', 'exception' => 'provider gone']]],
			$this->logs
		);

	}//end testFailureIsLoggedNotThrown()

	/**
	 * Another event reaches no request and no log.
	 *
	 * @return void
	 */
	public function testOtherEventIsIgnored(): void {
		$this->signing->expects($this->never())->method('createRequest');

		$this->listener()->handle(new Event());

		$this->assertSame([], $this->logs);

	}//end testOtherEventIsIgnored()

	/**
	 * A request event with distinct values in every field.
	 *
	 * @return DocumentSigningRequestedEvent The event.
	 */
	private function event(): DocumentSigningRequestedEvent {
		return new DocumentSigningRequestedEvent(
			provenance: new SigningProvenance(
				sourceApp: 'shillinq',
				subjectRegister: 'finance',
				subjectSchema: 'invoice',
				subjectId: 'inv-42',
				externalReference: 'ext-42',
				correlationId: 'corr-42'
			),
			subjectLabel: 'Invoice 42',
			documentReference: 'file-99',
			signers: self::SIGNERS,
			signatureLevel: 'QES',
			signingMode: 'parallel'
		);

	}//end event()

	/**
	 * The listener over a recording logger.
	 *
	 * @return DocumentSigningRequestedListener The listener.
	 */
	private function listener(): DocumentSigningRequestedListener {
		$logger = $this->createMock(LoggerInterface::class);
		foreach (['info', 'warning', 'error'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string $message, array $context = []) use ($level): void {
					$this->logs[] = [$level, $message, $context];
				}
			);
		}

		return new DocumentSigningRequestedListener(signingService: $this->signing, logger: $logger);

	}//end listener()
}//end class
