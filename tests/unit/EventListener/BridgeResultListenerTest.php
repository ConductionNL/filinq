<?php

/**
 * Unit tests for BridgeResultListener (zgw-document-bridge REQ-DDZGW-003).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookSubject;
use OCA\Filinq\EventListener\BridgeResultListener;
use OCA\Filinq\Service\BridgeService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The recorder hands the run's files to the bridge and never lets a failure
 * reach the anonymisation run.
 *
 * @covers \OCA\Filinq\EventListener\BridgeResultListener
 * @uses   \OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent
 * @uses   \OCA\Filinq\Event\Webhook\DocumentWebhookEvent
 * @uses   \OCA\Filinq\Event\Webhook\DocumentWebhookSubject
 */
class BridgeResultListenerTest extends TestCase {

	/**
	 * It says how many external documents became processed.
	 *
	 * @return void
	 */
	public function testItCountsTheProcessedDocuments(): void {
		$bridge = $this->createMock(originalClassName: BridgeService::class);
		$bridge->expects($this->once())->method('processed')->with(1101, 2201)->willReturn([['uuid' => 'd-1']]);

		$recorder = new BridgeResultListener(bridge: $bridge, logger: $this->createMock(originalClassName: LoggerInterface::class));

		$this->assertSame(expected: 1, actual: $recorder->record(sourceFileId: 1101, resultFileId: 2201));
	}//end testItCountsTheProcessedDocuments()

	/**
	 * A failing save is logged with both file ids and records nothing.
	 *
	 * @return void
	 */
	public function testAFailureIsLoggedAndSwallowed(): void {
		$bridge = $this->createMock(originalClassName: BridgeService::class);
		$bridge->method('processed')->willThrowException(new RuntimeException('lifecycle refused'));
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			$this->stringContains(string: 'not recorded'),
			$this->callback(
				callback: static fn (array $context): bool => $context['sourceFileId'] === 1101
					&& $context['resultFileId'] === 2201
					&& $context['exception'] === 'lifecycle refused'
			)
		);

		$recorder = new BridgeResultListener(bridge: $bridge, logger: $logger);

		$this->assertSame(expected: 0, actual: $recorder->record(sourceFileId: 1101, resultFileId: 2201));
	}//end testAFailureIsLoggedAndSwallowed()

	/**
	 * `document.anonymised` hands its source and output file to the bridge.
	 *
	 * @return void
	 */
	public function testTheAnonymisedEventReachesTheBridge(): void {
		$bridge = $this->createMock(originalClassName: BridgeService::class);
		$bridge->expects($this->once())->method('processed')->with(1101, 2201)->willReturn([]);
		$recorder = new BridgeResultListener(bridge: $bridge, logger: $this->createMock(originalClassName: LoggerInterface::class));

		$recorder->handle(
			event: new DocumentAnonymisedEvent(
				subject: new DocumentWebhookSubject(fileId: 1101, name: 'besluit.pdf', register: 'filinq', schema: 'anonymizationLink', id: 'link-1'),
				actor: 'operator',
				details: ['sourceFileId' => 1101, 'anonymisedFileId' => 2201, 'verificationVerdict' => 'verified'],
				occurredAt: '2026-11-26T09:00:00+00:00'
			)
		);
	}//end testTheAnonymisedEventReachesTheBridge()

	/**
	 * Any other event is ignored.
	 *
	 * @return void
	 */
	public function testOtherEventsAreIgnored(): void {
		$bridge = $this->createMock(originalClassName: BridgeService::class);
		$bridge->expects($this->never())->method('processed');

		(new BridgeResultListener(bridge: $bridge, logger: $this->createMock(originalClassName: LoggerInterface::class)))->handle(event: new Event());
	}//end testOtherEventsAreIgnored()
}//end class
