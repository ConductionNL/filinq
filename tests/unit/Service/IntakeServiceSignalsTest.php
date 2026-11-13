<?php

/**
 * Unit tests for the signals IntakeService sets and reads: the handling
 * deadline, the content hash and duplicate mark, the overdue list and a moved
 * deadline.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Event\Support\IntakeArrival;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\Intake\ContentHasher;
use OCA\Filinq\Service\Intake\IntakeDeadlines;
use OCA\Filinq\Service\Intake\IntakeOcrQueue;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeFilePlacement;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\IntakeService;
use OCA\Filinq\Service\PartySuggestionService;
use OCA\Filinq\Service\Support\IntakeFiling;
use OCA\Filinq\Service\Support\IntakeRouting;
use OCA\Filinq\Service\Support\IntakeSignals;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Every arrival gets its deadline; an arrival whose bytes came before is marked, never refused.
 *
 * @covers \OCA\Filinq\Service\IntakeService
 * @uses   \OCA\Filinq\Event\IntakeDocumentReceivedEvent
 * @uses   \OCA\Filinq\Event\Support\IntakeArrival
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 * @uses   \OCA\Filinq\Service\Support\IntakeFiling
 * @uses   \OCA\Filinq\Service\Support\IntakeRouting
 * @uses   \OCA\Filinq\Service\Support\IntakeSignals
 */
class IntakeServiceSignalsTest extends TestCase {

	/**
	 * The intake documents.
	 *
	 * @var IntakeRepository&MockObject
	 */
	private IntakeRepository&MockObject $repository;

	/**
	 * The handling deadline.
	 *
	 * @var IntakeDeadlines&MockObject
	 */
	private IntakeDeadlines&MockObject $deadlines;

	/**
	 * The content hash.
	 *
	 * @var ContentHasher&MockObject
	 */
	private ContentHasher&MockObject $hasher;

	/**
	 * The write-rights gate.
	 *
	 * @var IntakeAuthorizationGate&MockObject
	 */
	private IntakeAuthorizationGate&MockObject $gate;

	/**
	 * The default rules.
	 *
	 * @var IntakeDefaultRuleService&MockObject
	 */
	private IntakeDefaultRuleService&MockObject $rules;

	/**
	 * Build the collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->repository = $this->createMock(IntakeRepository::class);
		$this->repository->method('save')->willReturnCallback(
			static fn (array $document, ?string $uuid = null): array => $document + ['uuid' => ($uuid ?? 'new-uuid')]
		);
		$this->deadlines = $this->createMock(IntakeDeadlines::class);
		$this->hasher = $this->createMock(ContentHasher::class);
		$this->gate = $this->createMock(IntakeAuthorizationGate::class);
		$this->rules = $this->createMock(IntakeDefaultRuleService::class);
		$this->rules->method('stamp')->willReturnArgument(0);
	}//end setUp()

	/**
	 * The service.
	 *
	 * @return IntakeService
	 */
	private function service(): IntakeService {
		$ocr = $this->createMock(IntakeOcrQueue::class);
		$ocr->method('mark')->willReturnArgument(0);
		$parties = $this->createMock(PartySuggestionService::class);
		$parties->method('suggestFor')->willReturn([]);

		return new IntakeService(
			$this->repository,
			$this->gate,
			new IntakeFiling(placement: $this->createMock(IntakeFilePlacement::class), ocrQueue: $ocr),
			new IntakeRouting(defaults: $this->rules, routing: $this->createMock(IntakeRoutingService::class), parties: $parties),
			$this->createMock(IUserSession::class),
			$this->createMock(LoggerInterface::class),
			new IntakeSignals(deadlines: $this->deadlines, hasher: $this->hasher)
		);
	}//end service()

	/**
	 * A letter mailed by a sender.
	 *
	 * @param int|null $fileId The file.
	 *
	 * @return IntakeDocumentReceivedEvent
	 */
	private function letter(?int $fileId): IntakeDocumentReceivedEvent {
		return new IntakeDocumentReceivedEvent(
			channel: 'mail',
			fileId: $fileId,
			fileName: 'bezwaar.pdf',
			subject: 'Bezwaar parkeervergunning',
			sender: 'a@b.nl',
			arrival: new IntakeArrival(sourceRef: 'mail-2', receivedAt: '2026-10-05T09:00:00+00:00')
		);
	}//end letter()

	/**
	 * The arrival gets the deadline its rule sets, counted from when it arrived.
	 *
	 * @return void
	 */
	public function testAnArrivalGetsItsDeadline(): void {
		$this->deadlines->expects($this->once())->method('deadlineFor')
			->with('mail', 'a@b.nl', '2026-10-05T09:00:00+00:00')
			->willReturn('2026-10-19');
		$this->hasher->expects($this->never())->method('hashOf');

		$stored = $this->service()->receive(event: $this->letter(fileId: null));

		self::assertSame('2026-10-19', $stored['handlingDeadline']);
		self::assertArrayNotHasKey('contentHash', $stored);
		self::assertArrayNotHasKey('duplicateOf', $stored);
	}//end testAnArrivalGetsItsDeadline()

	/**
	 * The same bytes under another source reference are kept, and marked as a duplicate of the first.
	 *
	 * @return void
	 */
	public function testTheSameBytesAreMarkedAsADuplicate(): void {
		$this->deadlines->method('deadlineFor')->willReturn('2026-10-19');
		$this->hasher->expects($this->once())->method('hashOf')->with(12)->willReturn('abc123');
		$this->repository->method('findBySourceRef')->with('mail-2')->willReturn(null);
		$this->repository->expects($this->once())->method('findByContentHash')->with('abc123')->willReturn(['uuid' => 'scan-1']);
		$this->repository->expects($this->once())->method('save');

		$stored = $this->service()->receive(event: $this->letter(fileId: 12));

		self::assertSame('abc123', $stored['contentHash']);
		self::assertSame('scan-1', $stored['duplicateOf']);
		self::assertSame('received', $stored['status']);
	}//end testTheSameBytesAreMarkedAsADuplicate()

	/**
	 * New bytes, or bytes that cannot be read, mark nothing.
	 *
	 * @return void
	 */
	public function testNewOrUnreadableBytesMarkNothing(): void {
		$this->deadlines->method('deadlineFor')->willReturn('2026-10-19');
		$this->hasher->method('hashOf')->willReturnOnConsecutiveCalls('fresh', null);
		$this->repository->expects($this->once())->method('findByContentHash')->with('fresh')->willReturn(null);

		$fresh = $this->service()->receive(event: $this->letter(fileId: 12));
		self::assertSame('fresh', $fresh['contentHash']);
		self::assertArrayNotHasKey('duplicateOf', $fresh);

		$unreadable = $this->service()->receive(event: $this->letter(fileId: 13));
		self::assertArrayNotHasKey('contentHash', $unreadable);
		self::assertArrayNotHasKey('duplicateOf', $unreadable);
	}//end testNewOrUnreadableBytesMarkNothing()

	/**
	 * A re-delivery under the same source reference is skipped before any signal is set.
	 *
	 * @return void
	 */
	public function testARedeliveryIsSkippedFirst(): void {
		$this->repository->method('findBySourceRef')->willReturn(['uuid' => 'first', 'status' => 'received']);
		$this->deadlines->expects($this->never())->method('deadlineFor');
		$this->hasher->expects($this->never())->method('hashOf');
		$this->repository->expects($this->never())->method('save');

		self::assertSame(['uuid' => 'first', 'status' => 'received'], $this->service()->receive(event: $this->letter(fileId: 12)));
	}//end testARedeliveryIsSkippedFirst()

	/**
	 * A registrar moves a waiting document's deadline and the reason is kept.
	 *
	 * @return void
	 */
	public function testADeadlineMovesWithItsReason(): void {
		$this->repository->method('findByUuid')->with('doc-1')->willReturn(['uuid' => 'doc-1', 'status' => 'received', 'handlingDeadline' => '2026-10-14']);
		$this->gate->method('mayWrite')->willReturn(true);

		$moved = $this->service()->changeDeadline(uuid: 'doc-1', deadline: '2026-10-21', reason: '  waiting for the attachment ');

		self::assertSame('2026-10-21', $moved['handlingDeadline']);
		self::assertSame('waiting for the attachment', $moved['deadlineReason']);
		self::assertSame('doc-1', $moved['uuid']);
	}//end testADeadlineMovesWithItsReason()

	/**
	 * A move without a reason, with something that is not a date, on a document that left, or without rights is refused.
	 *
	 * @return void
	 */
	public function testABadMoveIsRefused(): void {
		$this->repository->method('findByUuid')->willReturnMap(
			[
				['waiting', ['uuid' => 'waiting', 'status' => 'received']],
				['gone', ['uuid' => 'gone', 'status' => 'rejected']],
			]
		);
		$this->gate->method('mayWrite')->willReturn(false);
		$this->repository->expects($this->never())->method('save');

		$cases = [
			'no reason' => [['waiting', '2026-10-21', ' '], 400],
			'not a date' => [['waiting', '21-10-2026', 'later'], 400],
			'no such day' => [['waiting', '2026-02-30', 'later'], 400],
			'left the inbox' => [['gone', '2026-10-21', 'later'], 409],
			'no rights' => [['waiting', '2026-10-21', 'later'], 403],
		];
		foreach ($cases as $name => [[$uuid, $deadline, $reason], $status]) {
			try {
				$this->service()->changeDeadline(uuid: $uuid, deadline: $deadline, reason: $reason);
				self::fail($name.' was accepted.');
			} catch (IntakeRefusedException $e) {
				self::assertSame($status, $e->getStatus(), $name);
			}
		}
	}//end testABadMoveIsRefused()
}//end class
