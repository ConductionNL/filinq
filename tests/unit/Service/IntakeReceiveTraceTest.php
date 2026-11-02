<?php

/**
 * Trace tests for taking a delivered document into the intake inbox.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\Intake\IntakeOcrQueue;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeFilePlacement;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\IntakeService;
use OCA\Filinq\Service\PartySuggestionService;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every path through receive(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\IntakeService
 * @uses   \OCA\Filinq\Event\IntakeDocumentReceivedEvent
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 */
class IntakeReceiveTraceTest extends TestCase {

	/**
	 * What the collaborators were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The deliveries: the event's arguments, and whether the source reference was seen before.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: bool}>
	 */
	public static function deliveries(): array {
		$mail = ['channel' => 'mail', 'fileId' => 12, 'fileName' => 'brief.pdf', 'subject' => 'Bezwaar', 'sender' => 'a@b.nl', 'sourceRef' => 'msg-1', 'receivedAt' => '2026-10-30T09:00:00+00:00', 'arrivedWith' => 'msg-0'];
		return [
			'an unknown channel' => [['channel' => 'fax'] + $mail, false],
			'seen before' => [$mail, true],
			'everything' => [$mail, false],
			'no file' => [['fileId' => null] + $mail, false],
			'nothing arrived with it' => [['arrivedWith' => ''] + $mail, false],
			'no arrival time' => [['receivedAt' => null] + $mail, false],
		];
	}//end deliveries()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param array<string, mixed> $delivery The event's arguments.
	 * @param bool                 $seen     Whether the source reference was delivered before.
	 *
	 * @dataProvider deliveries
	 *
	 * @return void
	 */
	public function testTrace(array $delivery, bool $seen): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, without the file and line of the call site.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				unset($context['file'], $context['line']);
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$trace = [];
		try {
			$trace['stored'] = $this->service($seen, $logger)->receive(new IntakeDocumentReceivedEvent(...$delivery));
		} catch (IntakeRefusedException $e) {
			$trace['refused'] = [$e->getMessage(), $e->getStatus()];
		}

		$trace['calls'] = $this->calls;
		$trace['logs'] = $logger->lines;

		$path = __DIR__ . '/golden/intake-receive-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testTrace()

	/**
	 * The service over recording doubles; the clock-dependent arrival time is fixed by the repository double.
	 *
	 * @param bool           $seen   Whether the source reference was delivered before.
	 * @param AbstractLogger $logger The logger.
	 *
	 * @return IntakeService
	 */
	private function service(bool $seen, AbstractLogger $logger): IntakeService {
		$repository = $this->createMock(IntakeRepository::class);
		$repository->method('findBySourceRef')->willReturnCallback(
			function (string $sourceRef) use ($seen): ?array {
				$this->calls[] = ['findBySourceRef', $sourceRef];
				return $seen === true ? ['uuid' => 'intake-1', 'status' => 'routed'] : null;
			}
		);
		$repository->method('save')->willReturnCallback(
			function (array $document): array {
				$document['receivedAt'] = is_string($document['receivedAt'] ?? null) && str_starts_with($document['receivedAt'], '2026-10-30T09') ? $document['receivedAt'] : 'now';
				$this->calls[] = ['save', $document];
				return ['uuid' => 'intake-2'] + $document;
			}
		);

		$defaults = $this->createMock(IntakeDefaultRuleService::class);
		$defaults->method('stamp')->willReturnCallback(
			function (array $document, string $channel, string $sender): array {
				$this->calls[] = ['stamp', array_keys($document), $channel, $sender];
				return $document + ['confidentiality' => 'intern'];
			}
		);

		$parties = $this->createMock(PartySuggestionService::class);
		$parties->method('suggestFor')->willReturnCallback(
			function (int $fileId): array {
				$this->calls[] = ['suggestFor', $fileId];
				return ['party' => 'p-1'];
			}
		);

		$ocr = $this->createMock(IntakeOcrQueue::class);
		$ocr->method('mark')->willReturnCallback(static fn (array $document): array => $document + ['ocrStatus' => 'queued']);
		$ocr->method('queue')->willReturnCallback(
			function (array $document): void {
				$this->calls[] = ['queue', $document['uuid'] ?? null];
			}
		);

		return new IntakeService(
			$repository,
			$this->createMock(IntakeAuthorizationGate::class),
			$this->createMock(IntakeFilePlacement::class),
			$defaults,
			$this->createMock(IntakeRoutingService::class),
			$parties,
			$this->createMock(IUserSession::class),
			$logger,
			$ocr
		);
	}//end service()
}//end class
