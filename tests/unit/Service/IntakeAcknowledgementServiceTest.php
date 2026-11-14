<?php

/**
 * Unit tests for IntakeAcknowledgementService (REQ-IAS-001, REQ-IAS-002).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\Intake\SenderReach;
use OCA\Filinq\Service\IntakeAcknowledgementService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Each branch of design D3, a refusing indicator and a failing send.
 *
 * @covers \OCA\Filinq\Service\IntakeAcknowledgementService
 */
class IntakeAcknowledgementServiceTest extends TestCase {

	private DocumentServiceInterface&MockObject $documents;

	private SenderReach&MockObject $reach;

	private LoggerInterface&MockObject $logger;

	/**
	 * The service at 14 October 2026, 09:30 UTC.
	 *
	 * @return IntakeAcknowledgementService
	 */
	private function service(): IntakeAcknowledgementService {
		$this->documents = $this->createMock(DocumentServiceInterface::class);
		$this->reach = $this->createMock(SenderReach::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-10-14T09:30:00+00:00'));

		return new IntakeAcknowledgementService($this->documents, $this->reach, $l10n, $time, $this->logger);
	}//end service()

	/**
	 * A mailed letter waiting in the inbox.
	 *
	 * @param array<string, mixed> $extra Overrides.
	 *
	 * @return array<string, mixed>
	 */
	private static function mailed(array $extra = []): array {
		return array_merge(
			[
				'uuid' => 'doc-1',
				'channel' => 'mail',
				'subject' => 'Bezwaar parkeervergunning',
				'sender' => 'j.devries@example.nl',
				'receivedAt' => '2026-10-14T09:12:00+00:00',
				'status' => 'received',
			],
			$extra
		);
	}//end mailed()

	/**
	 * The rule asking for an acknowledgement.
	 *
	 * @return array<string, mixed>
	 */
	private static function rule(): array {
		return ['uuid' => 'rule-mail', 'channel' => 'mail', 'acknowledgeWith' => 'tpl-ontvangst'];
	}//end rule()

	/**
	 * Without a linked party, a mailed document's address gets the rendered
	 * acknowledgement and the outcome is sent.
	 *
	 * @return void
	 */
	public function testAMailedLetterIsAcknowledgedToItsAddress(): void {
		$service = $this->service();
		$this->documents->expects($this->once())->method('generateDocument')->with(
			'tpl-ontvangst',
			[],
			[
				'format' => 'html',
				'adHocData' => [
					'subject' => 'Bezwaar parkeervergunning',
					'sender' => 'j.devries@example.nl',
					'receivedAt' => '2026-10-14T09:12:00+00:00',
					'channel' => 'mail',
				],
			]
		)->willReturn(['html' => '<p>Ontvangen op 14 oktober</p>', 'content' => 'x']);
		$this->reach->expects($this->once())->method('notifySenderParties')
			->with('doc-1', 'Acknowledgement of receipt: Bezwaar parkeervergunning', '<p>Ontvangen op 14 oktober</p>')
			->willReturn([]);
		$this->reach->expects($this->once())->method('mailAddress')
			->with('j.devries@example.nl', 'Acknowledgement of receipt: Bezwaar parkeervergunning', '<p>Ontvangen op 14 oktober</p>')
			->willReturn('dispatched');

		$this->assertSame(
			['status' => 'sent', 'address' => 'j.devries@example.nl', 'at' => '2026-10-14T09:30:00+00:00', 'reason' => null],
			$service->acknowledge(self::mailed(), self::rule())
		);
	}//end testAMailedLetterIsAcknowledgedToItsAddress()

	/**
	 * A party linked as sender is reached first, and its address recorded.
	 *
	 * @return void
	 */
	public function testALinkedPartyIsReachedFirst(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['html' => 'body']);
		$this->reach->method('notifySenderParties')->willReturn([['party' => 'p-1', 'address' => 'secretariaat@vereniging.nl', 'outcome' => 'dispatched']]);
		$this->reach->expects($this->never())->method('mailAddress');

		$outcome = $service->acknowledge(self::mailed(), self::rule());

		$this->assertSame(['sent', 'secretariaat@vereniging.nl', null], [$outcome['status'], $outcome['address'], $outcome['reason']]);
	}//end testALinkedPartyIsReachedFirst()

	/**
	 * A party with a refuse-send indicator is not written to, and the reason is kept.
	 *
	 * @return void
	 */
	public function testARefusingIndicatorIsRecordedAsRefused(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['html' => 'body']);
		$this->reach->method('notifySenderParties')->willReturn([['party' => 'p-1', 'address' => null, 'outcome' => 'refused-by-indicator']]);
		$this->reach->expects($this->never())->method('mailAddress');

		$outcome = $service->acknowledge(self::mailed(), self::rule());

		$this->assertSame(['refused', null, 'refused-by-indicator'], [$outcome['status'], $outcome['address'], $outcome['reason']]);
	}//end testARefusingIndicatorIsRecordedAsRefused()

	/**
	 * A party without an address falls through to the mailed address.
	 *
	 * @return void
	 */
	public function testAPartyWithoutAnAddressFallsThroughToTheMailAddress(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['content' => 'body']);
		$this->reach->method('notifySenderParties')->willReturn([['party' => 'p-1', 'address' => null, 'outcome' => 'no-address']]);
		$this->reach->expects($this->once())->method('mailAddress')->with('j.devries@example.nl', $this->anything(), 'body')->willReturn('dispatched');

		$this->assertSame('sent', $service->acknowledge(self::mailed(), self::rule())['status']);
	}//end testAPartyWithoutAnAddressFallsThroughToTheMailAddress()

	/**
	 * A scanned letter from a sender without an e-mail address is queued as a letter.
	 *
	 * @return void
	 */
	public function testAPaperLetterIsQueuedAsALetter(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['html' => 'body']);
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->reach->expects($this->never())->method('mailAddress');

		$outcome = $service->acknowledge(self::mailed(['channel' => 'scan', 'sender' => 'Fam. de Vries, Dorpsstraat 1']), self::rule());

		$this->assertSame(['queued-as-letter', null, null], [$outcome['status'], $outcome['address'], $outcome['reason']]);
	}//end testAPaperLetterIsQueuedAsALetter()

	/**
	 * A send the mail server refuses is failed with the send unit's reason.
	 *
	 * @return void
	 */
	public function testAFailingSendIsRecordedWithItsReason(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['html' => 'body']);
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->reach->method('mailAddress')->willReturn('failed');

		$this->assertSame(
			['status' => 'failed', 'address' => 'j.devries@example.nl', 'at' => '2026-10-14T09:30:00+00:00', 'reason' => 'failed'],
			$service->acknowledge(self::mailed(), self::rule())
		);
	}//end testAFailingSendIsRecordedWithItsReason()

	/**
	 * A render or reach that throws is answered as failed and logged, never thrown.
	 *
	 * @return void
	 */
	public function testAnExceptionIsAnsweredAsFailed(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willThrowException(new RuntimeException('template not found'));
		$this->reach->expects($this->never())->method('notifySenderParties');
		$this->logger->expects($this->once())->method('warning');

		$outcome = $service->acknowledge(self::mailed(), self::rule());

		$this->assertSame(['failed', null, 'template not found'], [$outcome['status'], $outcome['address'], $outcome['reason']]);
	}//end testAnExceptionIsAnsweredAsFailed()

	/**
	 * No template on the rule, or a document no longer waiting, sends nothing.
	 *
	 * @return void
	 */
	public function testNothingIsSentWithoutATemplateOrForADocumentNoLongerWaiting(): void {
		$service = $this->service();
		$this->documents->expects($this->never())->method('generateDocument');

		$this->assertNull($service->acknowledge(self::mailed(), ['uuid' => 'rule-mail', 'acknowledgeWith' => '  ']));
		$this->assertNull($service->acknowledge(self::mailed(), ['uuid' => 'rule-mail']));
		$this->assertNull($service->acknowledge(self::mailed(['status' => 'rejected']), self::rule()));
	}//end testNothingIsSentWithoutATemplateOrForADocumentNoLongerWaiting()
}//end class
