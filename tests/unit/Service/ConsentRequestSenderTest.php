<?php

/**
 * ConsentRequestSender: a consent request reaches the third party (REQ-FHB-02).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Filinq\Service\ConsentRecordWriter;
use OCA\Filinq\Service\ConsentRequestSender;
use OCA\Filinq\Service\ConsentScopeValidator;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\ConsentUpdateHandler;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\ObjectBoundMail;
use OCA\Filinq\Service\ObjectBoundMessage;
use OCA\Filinq\Service\ObjectionDeadlineChecker;
use OCA\Filinq\Service\PolicyMatchService;
use OCP\IAppConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A consent request goes to the third party, bound to the consent object, and the record says how it went.
 *
 * @covers \OCA\Filinq\Service\ConsentRequestSender
 * @covers \OCA\Filinq\Service\ConsentService
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 */
class ConsentRequestSenderTest extends TestCase {

	/**
	 * Sends the email bound to the consent object.
	 *
	 * @var ObjectBoundMail&MockObject
	 */
	private ObjectBoundMail $mail;

	/**
	 * Generates the letter.
	 *
	 * @var CorrespondenceService&MockObject
	 */
	private CorrespondenceService $correspondence;

	/**
	 * The consent letter template setting.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig $config;

	/**
	 * The service under test.
	 *
	 * @var ConsentRequestSender
	 */
	private ConsentRequestSender $sender;

	/**
	 * Build the sender with a 28-day objection period.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->mail           = $this->createMock(originalClassName: ObjectBoundMail::class);
		$this->correspondence = $this->createMock(originalClassName: CorrespondenceService::class);
		$this->config         = $this->createMock(originalClassName: IAppConfig::class);
		$deadlines            = $this->createMock(originalClassName: ObjectionDeadlineChecker::class);
		$deadlines->method('getObjectionPeriodDays')->willReturn(28);
		$l10n = $this->createMock(originalClassName: IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $p=[]): string => vsprintf($text, $p));

		$this->sender = new ConsentRequestSender(
			mail: $this->mail,
			correspondence: $this->correspondence,
			deadlines: $deadlines,
			config: $this->config,
			l10n: $l10n,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * The request context, with the defaults every test shares.
	 *
	 * @param array<string, mixed> $extra The fields this test sets.
	 *
	 * @return array<string, mixed>
	 */
	private function context(array $extra): array {
		return $extra + ['documentId' => 'doc-7', 'entityText' => 'Bakkerij Jansen', 'register' => 'r', 'schema' => 's'];

	}//end context()

	/**
	 * A party with an email address gets one mail bound to the consent object and a sent record.
	 *
	 * @return void
	 */
	public function testAPartyWithAnEmailAddressGetsOneMailBoundToTheConsentObject(): void {
		$this->mail->expects($this->once())->method('toAddress')
			->with(
				'info@bakkerij-jansen.nl',
				'',
				$this->callback(
					callback: static fn (ObjectBoundMessage $m): bool => str_contains($m->subject, 'doc-7') === true
						&& str_contains($m->body, '28 days') === true
						&& [$m->object->uuid, $m->object->register, $m->object->schema] === ['c-1', 'r', 's']
				)
			)
			->willReturn(ObjectBoundMail::OUTCOME_DISPATCHED);
		$this->correspondence->expects($this->never())->method('generate');

		$before  = new DateTimeImmutable('-1 second');
		$outcome = $this->sender->send(
			record: ['id' => 'c-1'],
			context: $this->context(extra: ['contactEmail' => 'info@bakkerij-jansen.nl', 'contactAddress' => 'Dorpsstraat 1'])
		);

		$this->assertSame(expected: 'sent', actual: $outcome['notificationStatus']);
		$this->assertSame(expected: 'email', actual: $outcome['notificationChannel']);
		$sentAt = new DateTimeImmutable($outcome['notificationSentAt']);
		$this->assertGreaterThanOrEqual(expected: $before, actual: $sentAt);
		$this->assertSame(expected: $sentAt->modify('+28 days')->format('c'), actual: $outcome['objectionDeadline']);
		$this->assertNull(actual: $outcome['notificationError']);

	}//end testAPartyWithAnEmailAddressGetsOneMailBoundToTheConsentObject()

	/**
	 * A mail OpenRegister did not send fails the record with the cause in words.
	 *
	 * @return void
	 */
	public function testAMailOpenRegisterDidNotSendIsFailedWithTheCause(): void {
		$this->mail->method('toAddress')->willReturnOnConsecutiveCalls('failed', 'no-address', 'kill-switch');

		foreach (['could not be sent: failed', 'email address is empty', 'switched off'] as $reason) {
			$outcome = $this->sender->send(record: ['id' => 'c-1'], context: $this->context(extra: ['contactEmail' => 'a@b.nl']));

			$this->assertSame(expected: 'failed', actual: $outcome['notificationStatus'], message: $reason);
			$this->assertNull(actual: $outcome['notificationSentAt'], message: $reason);
			$this->assertStringContainsString(needle: $reason, haystack: $outcome['notificationError']);
		}

	}//end testAMailOpenRegisterDidNotSendIsFailedWithTheCause()

	/**
	 * A party with only an address gets a letter from the configured template.
	 *
	 * @return void
	 */
	public function testAPartyWithOnlyAnAddressGetsALetterFromTheConfiguredTemplate(): void {
		$this->config->method('getValueString')->with('filinq', 'consent_letter_template', '')->willReturn('tpl-1');
		$this->mail->expects($this->never())->method('toAddress');
		$this->correspondence->expects($this->once())->method('generate')
			->with('tpl-1', [['register' => 'r', 'schema' => 's', 'id' => 'c-1']], ['format' => 'pdf'])
			->willReturn([]);

		$outcome = $this->sender->send(record: ['id' => 'c-1'], context: $this->context(extra: ['contactAddress' => 'Dorpsstraat 1']));

		$this->assertSame(expected: ['sent', 'letter'], actual: [$outcome['notificationStatus'], $outcome['notificationChannel']]);

	}//end testAPartyWithOnlyAnAddressGetsALetterFromTheConfiguredTemplate()

	/**
	 * A letter without a template is not sent and says why.
	 *
	 * @return void
	 */
	public function testALetterWithoutATemplateIsNotSentAndSaysWhy(): void {
		$this->config->method('getValueString')->willReturn('');
		$this->correspondence->expects($this->never())->method('generate');

		$outcome = $this->sender->send(record: ['id' => 'c-1'], context: $this->context(extra: ['contactAddress' => 'Dorpsstraat 1']));

		$this->assertSame(expected: 'failed', actual: $outcome['notificationStatus']);
		$this->assertStringContainsString(needle: 'template', haystack: $outcome['notificationError']);
		$this->assertArrayNotHasKey(key: 'objectionDeadline', array: $outcome);

	}//end testALetterWithoutATemplateIsNotSentAndSaysWhy()

	/**
	 * A party without any address is skipped: nothing is sent and nothing is linked.
	 *
	 * @return void
	 */
	public function testAPartyWithoutAnyAddressIsSkippedAndTheRecordSaysItHasNoAddress(): void {
		$this->mail->expects($this->never())->method('toAddress');
		$this->correspondence->expects($this->never())->method('generate');

		$outcome = $this->sender->send(record: ['id' => 'c-1'], context: $this->context(extra: ['contactEmail' => ' ']));

		$this->assertSame(expected: 'skipped', actual: $outcome['notificationStatus']);
		$this->assertNull(actual: $outcome['notificationSentAt']);
		$this->assertStringContainsString(needle: 'no email or postal address', haystack: $outcome['notificationError']);

	}//end testAPartyWithoutAnyAddressIsSkippedAndTheRecordSaysItHasNoAddress()

	/**
	 * A mailer that refuses the mail keeps the record open with the reason.
	 *
	 * @return void
	 */
	public function testAFailingMailerKeepsTheRecordOpenWithTheReason(): void {
		$this->mail->method('toAddress')->willThrowException(new RuntimeException('SMTP refused'));

		$outcome = $this->sender->send(record: ['id' => 'c-1'], context: $this->context(extra: ['contactEmail' => 'a@b.nl']));

		$this->assertSame(expected: 'failed', actual: $outcome['notificationStatus']);
		$this->assertStringContainsString(needle: 'SMTP refused', haystack: $outcome['notificationError']);

	}//end testAFailingMailerKeepsTheRecordOpenWithTheReason()

	/**
	 * A new pending record is sent and the outcome stored.
	 *
	 * @return void
	 */
	public function testANewPendingRecordIsSentAndTheOutcomeStored(): void {
		$writer = $this->createMock(originalClassName: ConsentRecordWriter::class);
		$writer->method('findExistingConsent')->willReturn(null);
		$writer->method('createNewConsent')->willReturn(['id' => 'c-1', 'notificationStatus' => 'pending', 'wasUpdated' => false]);
		$sender = $this->createMock(originalClassName: ConsentRequestSender::class);
		$sender->expects($this->once())->method('send')
			->with(
				['id' => 'c-1', 'notificationStatus' => 'pending', 'wasUpdated' => false],
				$this->callback(callback: static fn (array $c): bool => $c['contactEmail'] === 'a@b.nl')
			)
			->willReturn(['notificationStatus' => 'sent', 'notificationChannel' => 'email']);
		$writer->expects($this->once())->method('recordNotification')
			->with($this->anything(), ['notificationStatus' => 'sent', 'notificationChannel' => 'email'], 'r', 's')
			->willReturn(['id' => 'c-1', 'notificationStatus' => 'sent', 'notificationChannel' => 'email']);

		$result = $this->consentService(writer: $writer, sender: $sender)
			->createConsentRequest(
				documentId: 'doc-7',
				entityType: 'PERSON',
				entityText: 'Jan',
				register: 'r',
				schema: 's',
				extra: ['contactEmail' => 'a@b.nl']
			);

		$this->assertSame(
			expected: ['id' => 'c-1', 'notificationStatus' => 'sent', 'notificationChannel' => 'email', 'wasUpdated' => false],
			actual: $result
		);

	}//end testANewPendingRecordIsSentAndTheOutcomeStored()

	/**
	 * A standing-consent record asks nobody.
	 *
	 * @return void
	 */
	public function testAStandingConsentRecordAsksNobody(): void {
		$writer = $this->createMock(originalClassName: ConsentRecordWriter::class);
		$writer->method('createNewConsent')->willReturn(['id' => 'c-1', 'notificationStatus' => 'skipped']);
		$writer->expects($this->never())->method('recordNotification');
		$sender = $this->createMock(originalClassName: ConsentRequestSender::class);
		$sender->expects($this->never())->method('send');

		$result = $this->consentService(writer: $writer, sender: $sender)
			->createConsentRequest(documentId: 'doc-7', entityType: 'PERSON', entityText: 'Jan', register: 'r', schema: 's');

		$this->assertSame(expected: 'skipped', actual: $result['notificationStatus']);

	}//end testAStandingConsentRecordAsksNobody()

	/**
	 * The consent service with the given writer and sender.
	 *
	 * @param ConsentRecordWriter  $writer The record writer.
	 * @param ConsentRequestSender $sender The request sender.
	 *
	 * @return ConsentService
	 */
	private function consentService(ConsentRecordWriter $writer, ConsentRequestSender $sender): ConsentService {
		$policies = $this->createMock(originalClassName: PolicyMatchService::class);
		$policies->method('match')->willReturn(null);

		return new ConsentService(
			logger: $this->createMock(originalClassName: LoggerInterface::class),
			deadlineChecker: $this->createMock(originalClassName: ObjectionDeadlineChecker::class),
			updateHandler: $this->createMock(originalClassName: ConsentUpdateHandler::class),
			policyMatcher: $policies,
			scopeValidator: $this->createMock(originalClassName: ConsentScopeValidator::class),
			recordWriter: $writer,
			sender: $sender
		);

	}//end consentService()
}//end class
