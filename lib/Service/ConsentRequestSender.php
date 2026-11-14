<?php

/**
 * Consent Request Sender
 *
 * Sends a consent request to the third party it names: by email when an email
 * address is known, as a letter from the correspondence composer otherwise.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/finish-half-built-capabilities/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends one consent request and says how it went.
 *
 * The outcome is what the consent record keeps: the channel, the moment it was
 * sent and the end of the objection period counted from that moment; or, when
 * nothing could be sent, why. A request that cannot be sent leaves the record
 * open.
 *
 * @spec openspec/changes/finish-half-built-capabilities/specs/consent-management/spec.md
 */
class ConsentRequestSender {

	/**
	 * The app setting naming the correspondence template a consent letter is generated from.
	 */
	public const LETTER_TEMPLATE_KEY = 'consent_letter_template';

	/**
	 * Constructor.
	 *
	 * @param IMailer                  $mailer          Sends the email.
	 * @param CorrespondenceService    $correspondence  Generates the letter.
	 * @param ObjectionDeadlineChecker $deadlines       The objection period.
	 * @param IAppConfig               $config          The consent letter template setting.
	 * @param IL10N                    $l10n            Words the email.
	 * @param LoggerInterface          $logger          Logger.
	 */
	public function __construct(
		private readonly IMailer $mailer,
		private readonly CorrespondenceService $correspondence,
		private readonly ObjectionDeadlineChecker $deadlines,
		private readonly IAppConfig $config,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Send the request for a new consent record.
	 *
	 * @param array<string, mixed> $record  The stored consent record (with its id).
	 * @param array<string, mixed> $context The request: documentId, entityText, contactEmail, contactAddress, register, schema.
	 *
	 * @return array<string, mixed> The fields to store on the record: `notificationStatus`, and
	 *                              `notificationChannel`, `notificationSentAt` and `objectionDeadline`
	 *                              when it went, or `notificationError` when it did not.
	 *
	 * @spec openspec/changes/finish-half-built-capabilities/specs/consent-management/spec.md
	 */
	public function send(array $record, array $context): array {
		$email   = trim((string)($context['contactEmail'] ?? ''));
		$address = trim((string)($context['contactAddress'] ?? ''));

		try {
			if ($email !== '') {
				$this->sendEmail(email: $email, context: $context);
				return $this->sent(channel: 'email');
			}

			if ($address !== '') {
				return $this->sendLetter(record: $record, context: $context);
			}
		} catch (Throwable $e) {
			$this->logger->warning(
				message: 'Consent request could not be sent: ' . $e->getMessage(),
				context: ['documentId' => $context['documentId'] ?? null]
			);
			return $this->unsent(reason: $this->l10n->t('The consent request could not be sent: %s', [$e->getMessage()]));
		}

		return $this->unsent(reason: $this->l10n->t('The third party has no email or postal address, so no consent request was sent.'));
	}//end send()

	/**
	 * Email the request: what will be published, the objection period and how to respond.
	 *
	 * @param string               $email   The third party's email address.
	 * @param array<string, mixed> $context The request.
	 *
	 * @return void
	 */
	private function sendEmail(string $email, array $context): void {
		$days    = $this->deadlines->getObjectionPeriodDays();
		$message = $this->mailer->createMessage();
		$message->setTo([$email]);
		$message->setSubject($this->l10n->t('Request for consent to publish document %s', [(string)($context['documentId'] ?? '')]));
		$message->setPlainBody(
			$this->l10n->t(
				'We intend to publish document %1$s, which names %2$s. You have %3$d days from today to object. To object or consent, reply to this email.',
				[(string)($context['documentId'] ?? ''), (string)($context['entityText'] ?? ''), $days]
			)
		);
		$this->mailer->send($message);
	}//end sendEmail()

	/**
	 * Generate the request as a letter from the configured correspondence template.
	 *
	 * @param array<string, mixed> $record  The stored consent record.
	 * @param array<string, mixed> $context The request.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function sendLetter(array $record, array $context): array {
		$template = trim($this->config->getValueString('filinq', self::LETTER_TEMPLATE_KEY, ''));
		if ($template === '') {
			return $this->unsent(reason: $this->l10n->t('No consent letter template is configured, so no letter was generated.'));
		}

		$this->correspondence->generate(
			templateId: $template,
			dataRefs: [[
				'register' => (string)($context['register'] ?? ''),
				'schema' => (string)($context['schema'] ?? ''),
				'id' => (string)($record['id'] ?? ($record['uuid'] ?? '')),
			]],
			options: ['format' => 'pdf']
		);

		return $this->sent(channel: 'letter');
	}//end sendLetter()

	/**
	 * The outcome of a request that went: the channel, now, and the deadline counted from now.
	 *
	 * @param string $channel email or letter.
	 *
	 * @return array<string, mixed>
	 */
	private function sent(string $channel): array {
		$sentAt = new DateTimeImmutable();
		$days   = $this->deadlines->getObjectionPeriodDays();

		return [
			'notificationStatus' => 'sent',
			'notificationChannel' => $channel,
			'notificationSentAt' => $sentAt->format('c'),
			'objectionDeadline' => $sentAt->modify('+' . $days . ' days')->format('c'),
			'notificationError' => null,
		];
	}//end sent()

	/**
	 * The outcome of a request that did not go: the record stays open and says why.
	 *
	 * @param string $reason Why.
	 *
	 * @return array<string, mixed>
	 */
	private function unsent(string $reason): array {
		return [
			'notificationStatus' => 'failed',
			'notificationChannel' => null,
			'notificationSentAt' => null,
			'notificationError' => $reason,
		];
	}//end unsent()
}//end class
