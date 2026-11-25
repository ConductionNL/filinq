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
use OCA\Filinq\Service\ObjectBoundMailTarget;
use OCA\Filinq\Service\ObjectionDeadlineChecker;
use OCA\Filinq\Service\PolicyMatchService;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\ConsentRequestSender
 * @covers \OCA\Filinq\Service\ConsentService
 */
class ConsentRequestSenderTest extends TestCase {

	/** @var IMailer&MockObject */
	private IMailer $mailer;

	/** @var CorrespondenceService&MockObject */
	private CorrespondenceService $correspondence;

	/** @var IAppConfig&MockObject */
	private IAppConfig $config;

	private ConsentRequestSender $sender;

	protected function setUp(): void {
		$this->mailer         = $this->createMock(IMailer::class);
		$this->correspondence = $this->createMock(CorrespondenceService::class);
		$this->config         = $this->createMock(IAppConfig::class);
		$deadlines            = $this->createMock(ObjectionDeadlineChecker::class);
		$deadlines->method('getObjectionPeriodDays')->willReturn(28);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $p = []): string => vsprintf($text, $p));

		$this->sender = new ConsentRequestSender(
			mailer: $this->mailer,
			correspondence: $this->correspondence,
			deadlines: $deadlines,
			config: $this->config,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class)
		);
	}

	private function context(array $extra): array {
		return $extra + ['documentId' => 'doc-7', 'entityText' => 'Bakkerij Jansen', 'register' => 'r', 'schema' => 's'];
	}

	public function testAPartyWithAnEmailAddressGetsOneMessageAndASentRecord(): void {
		$message = $this->createMock(IMessage::class);
		$message->expects($this->once())->method('setTo')->with(['info@bakkerij-jansen.nl']);
		$message->expects($this->once())->method('setSubject')->with($this->stringContains('doc-7'));
		$message->expects($this->once())->method('setPlainBody')
			->with($this->logicalAnd($this->stringContains('doc-7'), $this->stringContains('28 days')));
		$this->mailer->method('createMessage')->willReturn($message);
		$this->mailer->expects($this->once())->method('send')->with($message);
		$this->correspondence->expects($this->never())->method('generate');

		$before  = new DateTimeImmutable('-1 second');
		$outcome = $this->sender->send(['id' => 'c-1'], $this->context(['contactEmail' => 'info@bakkerij-jansen.nl', 'contactAddress' => 'Dorpsstraat 1']));

		$this->assertSame('sent', $outcome['notificationStatus']);
		$this->assertSame('email', $outcome['notificationChannel']);
		$sentAt = new DateTimeImmutable($outcome['notificationSentAt']);
		$this->assertGreaterThanOrEqual($before, $sentAt);
		$this->assertSame($sentAt->modify('+28 days')->format('c'), $outcome['objectionDeadline']);
		$this->assertNull($outcome['notificationError']);
	}

	public function testAPartyWithOnlyAnAddressGetsALetterFromTheConfiguredTemplate(): void {
		$this->config->method('getValueString')->with('filinq', 'consent_letter_template', '')->willReturn('tpl-1');
		$this->mailer->expects($this->never())->method('send');
		$this->correspondence->expects($this->once())->method('generate')
			->with('tpl-1', [['register' => 'r', 'schema' => 's', 'id' => 'c-1']], ['format' => 'pdf'])
			->willReturn([]);

		$outcome = $this->sender->send(['id' => 'c-1'], $this->context(['contactAddress' => 'Dorpsstraat 1']));

		$this->assertSame(['sent', 'letter'], [$outcome['notificationStatus'], $outcome['notificationChannel']]);
	}

	public function testALetterWithoutATemplateIsNotSentAndSaysWhy(): void {
		$this->config->method('getValueString')->willReturn('');
		$this->correspondence->expects($this->never())->method('generate');

		$outcome = $this->sender->send(['id' => 'c-1'], $this->context(['contactAddress' => 'Dorpsstraat 1']));

		$this->assertSame('failed', $outcome['notificationStatus']);
		$this->assertStringContainsString('template', $outcome['notificationError']);
		$this->assertArrayNotHasKey('objectionDeadline', $outcome);
	}

	public function testAPartyWithoutAnyAddressIsSkippedAndTheRecordSaysItHasNoAddress(): void {
		$this->mailer->expects($this->never())->method('send');
		$this->correspondence->expects($this->never())->method('generate');

		$outcome = $this->sender->send(['id' => 'c-1'], $this->context(['contactEmail' => ' ']));

		$this->assertSame('skipped', $outcome['notificationStatus']);
		$this->assertNull($outcome['notificationSentAt']);
		$this->assertStringContainsString('no email or postal address', $outcome['notificationError']);
	}

	public function testAFailingMailerKeepsTheRecordOpenWithTheReason(): void {
		$this->mailer->method('createMessage')->willReturn($this->createMock(IMessage::class));
		$this->mailer->method('send')->willThrowException(new RuntimeException('SMTP refused'));

		$outcome = $this->sender->send(['id' => 'c-1'], $this->context(['contactEmail' => 'a@b.nl']));

		$this->assertSame('failed', $outcome['notificationStatus']);
		$this->assertStringContainsString('SMTP refused', $outcome['notificationError']);
	}

	public function testAnEmailGoesOutBoundToTheConsentObjectAndTheMailerIsNotUsed(): void {
		$bound = $this->createMock(ObjectBoundMail::class);
		$bound->expects($this->once())->method('toAddress')
			->with(
				'info@bakkerij-jansen.nl',
				'',
				$this->stringContains('doc-7'),
				$this->stringContains('28 days'),
				$this->callback(static fn (ObjectBoundMailTarget $t): bool => [$t->uuid, $t->register, $t->schema] === ['c-1', 'r', 's'])
			)
			->willReturn(ObjectBoundMail::OUTCOME_DISPATCHED);
		$this->mailer->expects($this->never())->method('send');

		$outcome = $this->boundSender(bound: $bound)->send(['id' => 'c-1'], $this->context(['contactEmail' => 'info@bakkerij-jansen.nl']));

		$this->assertSame(['sent', 'email', null], [$outcome['notificationStatus'], $outcome['notificationChannel'], $outcome['notificationError']]);
		$this->assertArrayHasKey('objectionDeadline', $outcome);
	}

	public function testAMailOpenRegisterDidNotSendIsFailedWithTheCause(): void {
		$cases = [
			'failed' => 'could not be sent: failed',
			'no-address' => 'email address is empty',
			'kill-switch' => 'switched off',
		];
		foreach ($cases as $result => $reason) {
			$bound = $this->createMock(ObjectBoundMail::class);
			$bound->method('toAddress')->willReturn($result);

			$outcome = $this->boundSender(bound: $bound)->send(['id' => 'c-1'], $this->context(['contactEmail' => 'a@b.nl']));

			$this->assertSame('failed', $outcome['notificationStatus'], $result);
			$this->assertNull($outcome['notificationSentAt'], $result);
			$this->assertStringContainsString($reason, $outcome['notificationError'], $result);
		}
	}

	public function testWithoutOpenRegistersMailSenderTheMailerSendsIt(): void {
		$bound = $this->createMock(ObjectBoundMail::class);
		$bound->method('toAddress')->willReturn(ObjectBoundMail::OUTCOME_UNAVAILABLE);
		$this->mailer->method('createMessage')->willReturn($this->createMock(IMessage::class));
		$this->mailer->expects($this->once())->method('send');

		$outcome = $this->boundSender(bound: $bound)->send(['id' => 'c-1'], $this->context(['contactEmail' => 'a@b.nl']));

		$this->assertSame('sent', $outcome['notificationStatus']);
	}

	public function testARecordWithoutAChannelLinksNothing(): void {
		$bound = $this->createMock(ObjectBoundMail::class);
		$bound->expects($this->never())->method('toAddress');

		$outcome = $this->boundSender(bound: $bound)->send(['id' => 'c-1'], $this->context([]));

		$this->assertSame('skipped', $outcome['notificationStatus']);
	}

	private function boundSender(ObjectBoundMail $bound): ConsentRequestSender {
		$deadlines = $this->createMock(ObjectionDeadlineChecker::class);
		$deadlines->method('getObjectionPeriodDays')->willReturn(28);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $p = []): string => vsprintf($text, $p));

		return new ConsentRequestSender(
			mailer: $this->mailer,
			correspondence: $this->correspondence,
			deadlines: $deadlines,
			config: $this->config,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class),
			boundMail: $bound
		);
	}

	public function testANewPendingRecordIsSentAndTheOutcomeStored(): void {
		$writer = $this->createMock(ConsentRecordWriter::class);
		$writer->method('findExistingConsent')->willReturn(null);
		$writer->method('createNewConsent')->willReturn(['id' => 'c-1', 'notificationStatus' => 'pending', 'wasUpdated' => false]);
		$sender = $this->createMock(ConsentRequestSender::class);
		$sender->expects($this->once())->method('send')
			->with(['id' => 'c-1', 'notificationStatus' => 'pending', 'wasUpdated' => false], $this->callback(static fn (array $c): bool => $c['contactEmail'] === 'a@b.nl'))
			->willReturn(['notificationStatus' => 'sent', 'notificationChannel' => 'email']);
		$writer->expects($this->once())->method('recordNotification')
			->with($this->anything(), ['notificationStatus' => 'sent', 'notificationChannel' => 'email'], 'r', 's')
			->willReturn(['id' => 'c-1', 'notificationStatus' => 'sent', 'notificationChannel' => 'email']);

		$result = $this->consentService(writer: $writer, sender: $sender)
			->createConsentRequest('doc-7', 'PERSON', 'Jan', 'r', 's', ['contactEmail' => 'a@b.nl']);

		$this->assertSame(['id' => 'c-1', 'notificationStatus' => 'sent', 'notificationChannel' => 'email', 'wasUpdated' => false], $result);
	}

	public function testAStandingConsentRecordAsksNobody(): void {
		$writer = $this->createMock(ConsentRecordWriter::class);
		$writer->method('createNewConsent')->willReturn(['id' => 'c-1', 'notificationStatus' => 'skipped']);
		$writer->expects($this->never())->method('recordNotification');
		$sender = $this->createMock(ConsentRequestSender::class);
		$sender->expects($this->never())->method('send');

		$result = $this->consentService(writer: $writer, sender: $sender)->createConsentRequest('doc-7', 'PERSON', 'Jan', 'r', 's');

		$this->assertSame('skipped', $result['notificationStatus']);
	}

	private function consentService(ConsentRecordWriter $writer, ConsentRequestSender $sender): ConsentService {
		$policies = $this->createMock(PolicyMatchService::class);
		$policies->method('match')->willReturn(null);

		return new ConsentService(
			logger: $this->createMock(LoggerInterface::class),
			deadlineChecker: $this->createMock(ObjectionDeadlineChecker::class),
			updateHandler: $this->createMock(ConsentUpdateHandler::class),
			policyMatcher: $policies,
			scopeValidator: $this->createMock(ConsentScopeValidator::class),
			recordWriter: $writer,
			sender: $sender
		);
	}
}
