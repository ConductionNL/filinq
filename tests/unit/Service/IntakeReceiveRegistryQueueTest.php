<?php

/**
 * An arrival queues its base registry lookup after it is saved and returns
 * without asking any registry; a duplicate delivery queues nothing.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Event\Support\IntakeArrival;
use OCA\Filinq\Service\Intake\IntakeOcrQueue;
use OCA\Filinq\Service\Intake\RegistryEnrichmentQueue;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeFilePlacement;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\IntakeService;
use OCA\Filinq\Service\PartySuggestionService;
use OCA\Filinq\Service\Support\IntakeFiling;
use OCA\Filinq\Service\Support\IntakeRouting;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\IntakeService
 * @uses   \OCA\Filinq\Event\IntakeDocumentReceivedEvent
 * @uses   \OCA\Filinq\Event\Support\IntakeArrival
 * @uses   \OCA\Filinq\Service\Support\IntakeFiling
 * @uses   \OCA\Filinq\Service\Support\IntakeRouting
 */
class IntakeReceiveRegistryQueueTest extends TestCase {

	/**
	 * What happened, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The service over recording doubles.
	 *
	 * @param bool $seen Whether the source reference was delivered before.
	 *
	 * @return IntakeService The service under test.
	 */
	private function service(bool $seen): IntakeService {
		$repository = $this->createMock(IntakeRepository::class);
		$repository->method('findBySourceRef')->willReturn($seen === true ? ['uuid' => 'intake-1'] : null);
		$repository->method('save')->willReturnCallback(
			function (array $document): array {
				$this->calls[] = ['save'];
				return ['uuid' => 'intake-2'] + $document;
			}
		);

		$defaults = $this->createMock(IntakeDefaultRuleService::class);
		$defaults->method('stamp')->willReturnArgument(0);

		$ocr = $this->createMock(IntakeOcrQueue::class);
		$ocr->method('mark')->willReturnArgument(0);

		$registry = $this->createMock(RegistryEnrichmentQueue::class);
		$registry->method('queue')->willReturnCallback(
			function (array $document): void {
				$this->calls[] = ['registry', $document['uuid'] ?? null, $document['file'] ?? null];
			}
		);

		return new IntakeService(
			$repository,
			$this->createMock(IntakeAuthorizationGate::class),
			new IntakeFiling(placement: $this->createMock(IntakeFilePlacement::class), ocrQueue: $ocr, registryQueue: $registry),
			new IntakeRouting(defaults: $defaults, routing: $this->createMock(IntakeRoutingService::class), parties: $this->createMock(PartySuggestionService::class)),
			$this->createMock(IUserSession::class),
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The arrival event for one mailed letter.
	 *
	 * @return IntakeDocumentReceivedEvent The event.
	 */
	private static function letter(): IntakeDocumentReceivedEvent {
		return new IntakeDocumentReceivedEvent(
			channel: 'mail',
			fileId: 12,
			fileName: 'offerte.pdf',
			subject: 'Offerte',
			sender: 'info@voorbeeld-bv.nl',
			arrival: new IntakeArrival(sourceRef: 'msg-1', receivedAt: '2026-10-30T09:00:00+00:00')
		);

	}//end letter()

	/**
	 * The lookup is queued for the stored document, after the save.
	 *
	 * @return void
	 */
	public function testTheLookupIsQueuedAfterTheSave(): void {
		$stored = $this->service(seen: false)->receive(event: self::letter());

		$this->assertSame('intake-2', $stored['uuid']);
		$this->assertSame([['save'], ['registry', 'intake-2', 12]], $this->calls);
		$this->assertArrayNotHasKey('registrySuggestion', $stored, 'receive() returns before any lookup');

	}//end testTheLookupIsQueuedAfterTheSave()

	/**
	 * A delivery seen before is returned as it was, without a second lookup.
	 *
	 * @return void
	 */
	public function testADuplicateDeliveryQueuesNothing(): void {
		$this->service(seen: true)->receive(event: self::letter());

		$this->assertSame([], $this->calls);

	}//end testADuplicateDeliveryQueuesNothing()
}//end class
