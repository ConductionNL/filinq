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
use OCA\Filinq\Service\Intake\AnsweringRegistration;
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

	private AnsweringRegistration&MockObject $registrations;

	/**
	 * The service at 14 October 2026, 09:30 UTC.
	 *
	 * @return IntakeAcknowledgementService
	 */
	private function service(): IntakeAcknowledgementService {
		$this->documents = $this->createMock(DocumentServiceInterface::class);
		$this->reach = $this->createMock(SenderReach::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->registrations = $this->createMock(AnsweringRegistration::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-10-14T09:30:00+00:00'));

		return new IntakeAcknowledgementService($this->documents, $this->reach, $l10n, $time, $this->logger, $this->registrations);
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
					'receivedAt' => '14-10-2026',
					'channel' => 'mail',
					'registrationNumber' => '',
				],
			]
		)->willReturn(['html' => '<p>Ontvangen op 14 oktober</p>', 'content' => 'x']);
		$this->reach->expects($this->once())->method('notifySenderParties')
			->with('doc-1', 'Acknowledgement of receipt: Bezwaar parkeervergunning', 'Ontvangen op 14 oktober')
			->willReturn([]);
		$this->reach->expects($this->once())->method('mailAddress')
			->with('j.devries@example.nl', 'Acknowledgement of receipt: Bezwaar parkeervergunning', 'Ontvangen op 14 oktober')
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
		$formats = [];
		$this->documents->expects($this->exactly(2))->method('generateDocument')->willReturnCallback(
			static function (string $template, array $refs, array $options) use (&$formats): array {
				$formats[] = [$options['format'], ($options['output']['mode'] ?? 'return')];
				return $options['format'] === 'pdf' ? ['content' => '%PDF', 'output' => ['fileId' => 77]] : ['html' => 'body'];
			}
		);
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->reach->expects($this->never())->method('mailAddress');

		$outcome = $service->acknowledge(self::mailed(['channel' => 'scan', 'sender' => 'Fam. de Vries, Dorpsstraat 1']), self::rule());

		$this->assertSame(['queued-as-letter', null, null, 77], [$outcome['status'], $outcome['address'], $outcome['reason'], $outcome['letter']]);
		$this->assertSame([['html', 'return'], ['pdf', 'files']], $formats);
	}//end testAPaperLetterIsQueuedAsALetter()

	/**
	 * A letter for the outgoing post that was not stored is failed, not queued.
	 *
	 * @return void
	 */
	public function testALetterThatWasNotStoredIsFailed(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['html' => 'body', 'output' => ['fileId' => null]]);
		$this->reach->method('notifySenderParties')->willReturn([]);

		$outcome = $service->acknowledge(self::mailed(['channel' => 'scan', 'sender' => 'Fam. de Vries']), self::rule());

		$this->assertSame(['failed', 'the letter for the outgoing post was not stored'], [$outcome['status'], $outcome['reason']]);
		$this->assertArrayNotHasKey('letter', $outcome);
	}//end testALetterThatWasNotStoredIsFailed()

	/**
	 * With an inbound registration, the letter carries its number and the
	 * acknowledgement is registered as the outbound entry answering it (D4).
	 *
	 * @return void
	 */
	public function testAnInboundRegistrationIsNumberedOnTheLetterAndAnswered(): void {
		$service = $this->service();
		$inbound = ['uuid' => 'reg-in', 'unit' => 'post', 'registrationNumber' => '2026-00042'];
		$this->documents->expects($this->once())->method('generateDocument')->with(
			'tpl-ontvangst',
			[],
			$this->callback(static fn (array $options): bool => $options['adHocData']['registrationNumber'] === '2026-00042')
		)->willReturn(['html' => 'body', 'metadata' => ['id' => 'gen-1']]);
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->reach->method('mailAddress')->willReturn('dispatched');
		$this->registrations->expects($this->once())->method('registerAnswer')->with($inbound, 'gen-1')
			->willReturn(['uuid' => 'reg-out', 'answers' => 'reg-in']);

		$outcome = $service->acknowledge(self::mailed(), self::rule(), $inbound);

		$this->assertSame(['sent', 'reg-out'], [$outcome['status'], $outcome['registration']]);
	}//end testAnInboundRegistrationIsNumberedOnTheLetterAndAnswered()

	/**
	 * A queued letter is registered with the stored PDF's entry, not the mail body's.
	 *
	 * @return void
	 */
	public function testAQueuedLetterIsRegisteredWithTheStoredLetter(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturnCallback(
			static fn (string $template, array $refs, array $options): array => $options['format'] === 'pdf'
				? ['output' => ['fileId' => 77], 'metadata' => ['id' => 'gen-pdf']]
				: ['html' => 'body', 'metadata' => ['id' => 'gen-html']]
		);
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->registrations->expects($this->once())->method('registerAnswer')->with($this->anything(), 'gen-pdf')->willReturn(null);

		$outcome = $service->acknowledge(self::mailed(['channel' => 'scan', 'sender' => 'Fam. de Vries']), self::rule(), ['uuid' => 'reg-in', 'unit' => 'post']);

		$this->assertSame('queued-as-letter', $outcome['status']);
		$this->assertArrayNotHasKey('registration', $outcome);
	}//end testAQueuedLetterIsRegisteredWithTheStoredLetter()

	/**
	 * A refused or failed acknowledgement did not go out and is not registered.
	 *
	 * @return void
	 */
	public function testWhatDidNotGoOutIsNotRegistered(): void {
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturn(['html' => 'body', 'metadata' => ['id' => 'gen-1']]);
		$this->reach->method('notifySenderParties')->willReturn([['party' => 'p-1', 'address' => null, 'outcome' => 'refused-by-indicator']]);
		$this->registrations->expects($this->never())->method('registerAnswer');

		$this->assertSame('refused', $service->acknowledge(self::mailed(), self::rule(), ['uuid' => 'reg-in', 'unit' => 'post'])['status']);
	}//end testWhatDidNotGoOutIsNotRegistered()

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

	/**
	 * Live, the saved object holds the translatable subject as a language map
	 * and the received time as ISO; the letter and the subject line name the
	 * Dutch text and a readable date, and the mail body is plain text (an
	 * OpenRegister mail is plain text, so HTML would arrive as tags).
	 *
	 * @return void
	 */
	public function testTheLetterNamesTheSubjectTextAReadableDateAndGoesAsPlainText(): void {
		$service = $this->service();
		$this->documents->expects($this->once())->method('generateDocument')->with(
			'tpl-ontvangst',
			[],
			$this->callback(static function (array $options): bool {
				return $options['adHocData']['subject'] === 'Bezwaar parkeervergunning'
					&& $options['adHocData']['receivedAt'] === '14-10-2026';
			})
		)->willReturn(['html' => "<p>Geachte heer, mevrouw,</p>\n<p>Uw stuk &quot;Bezwaar&quot; is ontvangen.<br>Dank u.</p>\n<p>Met vriendelijke groet,</p>", 'content' => 'x']);
		$expected = "Geachte heer, mevrouw,\n\nUw stuk \"Bezwaar\" is ontvangen.\nDank u.\n\nMet vriendelijke groet,";
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->reach->expects($this->once())->method('mailAddress')
			->with('j.devries@example.nl', 'Acknowledgement of receipt: Bezwaar parkeervergunning', $expected)
			->willReturn('dispatched');

		$outcome = $service->acknowledge(self::mailed(['subject' => ['nl' => 'Bezwaar parkeervergunning', 'en' => 'Objection']]), self::rule());
		$this->assertSame('sent', $outcome['status']);
	}//end testTheLetterNamesTheSubjectTextAReadableDateAndGoesAsPlainText()

	/**
	 * A subject map without Dutch falls back to English, then to the first
	 * language; a receivedAt that is not a date is passed on as it is.
	 *
	 * @return void
	 */
	public function testASubjectWithoutDutchFallsBackAndAnOddDateIsKept(): void {
		$seen = [];
		$service = $this->service();
		$this->documents->method('generateDocument')->willReturnCallback(static function (string $template, array $ids, array $options) use (&$seen): array {
			$seen[] = $options['adHocData'];
			return ['html' => '<p>x</p>', 'content' => 'x'];
		});
		$this->reach->method('notifySenderParties')->willReturn([]);
		$this->reach->method('mailAddress')->willReturn('dispatched');

		$service->acknowledge(self::mailed(['subject' => ['en' => 'Objection', 'de' => 'Einspruch']]), self::rule());
		$service->acknowledge(self::mailed(['subject' => ['de' => 'Einspruch'], 'receivedAt' => 'gisteren']), self::rule());
		$service->acknowledge(self::mailed(['subject' => ['nl' => ['nested']], 'receivedAt' => '']), self::rule());

		$this->assertSame(['Objection', 'Einspruch', ''], array_column($seen, 'subject'));
		$this->assertSame(['14-10-2026', 'gisteren', ''], array_column($seen, 'receivedAt'));
	}//end testASubjectWithoutDutchFallsBackAndAnOddDateIsKept()

}//end class
