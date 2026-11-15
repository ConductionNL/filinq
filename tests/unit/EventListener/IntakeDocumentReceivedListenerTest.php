<?php

/**
 * Unit tests for IntakeDocumentReceivedListener.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Event\Support\IntakeArrival;
use OCA\Filinq\EventListener\IntakeDocumentReceivedListener;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A delivered document is handed to the intake service; a failure is logged, never thrown.
 *
 * @covers \OCA\Filinq\EventListener\IntakeDocumentReceivedListener
 * @uses   \OCA\Filinq\Event\IntakeDocumentReceivedEvent
 * @uses   \OCA\Filinq\Event\Support\IntakeArrival
 */
class IntakeDocumentReceivedListenerTest extends TestCase {

	/**
	 * Other events are ignored.
	 *
	 * @return void
	 */
	public function testIgnoresOtherEvents(): void {
		$intake = $this->createMock(IntakeServiceInterface::class);
		$intake->expects($this->never())->method('receive');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method($this->anything());

		(new IntakeDocumentReceivedListener(intake: $intake, logger: $logger))->handle(event: new Event());
	}//end testIgnoresOtherEvents()

	/**
	 * A received document is passed to intake and its uuid logged with the channel.
	 *
	 * @return void
	 */
	public function testReceivesAndLogsTheUuid(): void {
		$event = new IntakeDocumentReceivedEvent(channel: IntakeDocumentReceivedEvent::CHANNEL_SCAN, fileId: 9);
		$intake = $this->createMock(IntakeServiceInterface::class);
		$intake->expects($this->once())->method('receive')->with($event)->willReturn(['uuid' => 'u-1']);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('error');
		$logger->expects($this->once())->method('info')->with(
			'[IntakeDocumentReceivedListener] a document is waiting in the intake inbox',
			$this->callback(static fn (array $c): bool => $c['channel'] === 'scan' && $c['uuid'] === 'u-1')
		);

		(new IntakeDocumentReceivedListener(intake: $intake, logger: $logger))->handle(event: $event);
	}//end testReceivesAndLogsTheUuid()

	/**
	 * A document without a uuid logs an empty one.
	 *
	 * @return void
	 */
	public function testMissingUuidLogsEmpty(): void {
		$intake = $this->createMock(IntakeServiceInterface::class);
		$intake->method('receive')->willReturn([]);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('info')->with(
			$this->anything(),
			$this->callback(static fn (array $c): bool => $c['uuid'] === '' && $c['channel'] === 'mail')
		);

		(new IntakeDocumentReceivedListener(intake: $intake, logger: $logger))
			->handle(event: new IntakeDocumentReceivedEvent(channel: IntakeDocumentReceivedEvent::CHANNEL_MAIL));
	}//end testMissingUuidLogsEmpty()

	/**
	 * A failing intake is logged as an error with channel, source and cause, and does not escape.
	 *
	 * @return void
	 */
	public function testFailureIsLoggedNotThrown(): void {
		$event = new IntakeDocumentReceivedEvent(
			channel: IntakeDocumentReceivedEvent::CHANNEL_DIGITAL_POST,
			arrival: new IntakeArrival(sourceRef: 'msg-42')
		);
		$intake = $this->createMock(IntakeServiceInterface::class);
		$intake->method('receive')->willThrowException(new RuntimeException('register down'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');
		$logger->expects($this->once())->method('error')->with(
			'[IntakeDocumentReceivedListener] could not take in a delivered document',
			$this->callback(
				static fn (array $c): bool => $c['channel'] === 'digitalPost' && $c['sourceRef'] === 'msg-42'
					&& $c['error'] === 'register down'
			)
		);

		(new IntakeDocumentReceivedListener(intake: $intake, logger: $logger))->handle(event: $event);
	}//end testFailureIsLoggedNotThrown()
}//end class
