<?php

/**
 * Correspondence Send Service
 *
 * Sends one composed letter by email through Nextcloud's mailer and logs
 * the send as a correspondence entry: `sent` with when, to whom and by whom,
 * or `failed` with the reason. A send that cannot happen (no recipient the
 * caller can read, no email address, no mail server) is refused before
 * anything is composed, and nothing is logged as sent.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\CorrespondenceSendRefused;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCP\IConfig;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends a composed letter to the email address on its recipient object.
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
 */
class CorrespondenceSendService {

	/**
	 * The refusal for a request that names no recipient.
	 *
	 * @var string
	 */
	public const NO_RECIPIENT = 'Name the recipient: register, schema and id.';

	/**
	 * The refusal for a recipient the caller cannot read.
	 *
	 * @var string
	 */
	public const NOT_FOUND = 'The recipient was not found or you may not read it.';

	/**
	 * The refusal for a recipient without an email address.
	 *
	 * @var string
	 */
	public const NO_ADDRESS = 'This recipient has no email address.';

	/**
	 * The refusal for an instance that has no mail server.
	 *
	 * @var string
	 */
	public const NO_MAIL_SERVER = 'This instance has no mail server, so letters cannot be sent by email.';

	/**
	 * The reason logged when the mail server takes the message but refuses the address.
	 *
	 * @var string
	 */
	public const ADDRESS_REFUSED = 'The mail server refused the recipient address.';

	/**
	 * The fields of a recipient object that hold its email address, in the order they are read.
	 *
	 * @var list<string>
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private const ADDRESS_FIELDS = ['email', 'emailAddress', 'emailadres', 'e-mail', 'mail'];

	/**
	 * Constructor.
	 *
	 * @param CorrespondenceService $correspondence Composes the letter and logs the send.
	 * @param TemplateService $templates Reads the template for the subject of a preview.
	 * @param DocumentObjectServiceResolver $objects Reaches OpenRegister's object service.
	 * @param IMailer $mailer Nextcloud's mailer.
	 * @param IConfig $config System configuration, for the mail mode.
	 * @param LoggerInterface $logger Logger for failed sends.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly CorrespondenceService $correspondence,
		private readonly TemplateService $templates,
		private readonly DocumentObjectServiceResolver $objects,
		private readonly IMailer $mailer,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Where and under which subject a letter would go, without composing or sending it.
	 *
	 * The same refusals as {@see send()}, so the confirm dialog shows them
	 * before the officer confirms.
	 *
	 * @param string $templateId The template the letter is composed from.
	 * @param array $dataRefs The data references, the first being the recipient.
	 *
	 * @return array{to: string, subject: string}
	 *
	 * @throws CorrespondenceSendRefused 400, 404 or 422.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
	 */
	public function preview(string $templateId, array $dataRefs): array {
		$address = $this->addressOf(recipient: $this->readRecipient(ref: self::recipientRef(dataRefs: $dataRefs)));
		$this->assertMailServer();

		return [
			'to' => $address,
			'subject' => self::subjectOf(template: $this->templates->getTemplate(id: $templateId)),
		];

	}//end preview()

	/**
	 * Compose the letter as an email and send it to the recipient's address.
	 *
	 * @param string $templateId The template the letter is composed from.
	 * @param array $dataRefs The data references, the first being the recipient.
	 * @param array $options userId, huisstijlId, caseReference, recipientType, attachPdf.
	 *
	 * @return array{status: string, to: string, subject: string, sentAt: string|null, warnings: array, registerEntry: array}
	 *
	 * @throws CorrespondenceSendRefused 400, 404 or 422, before anything is composed.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md#requirement-a-composed-letter-can-be-sent-by-email-req-lcs-01
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-1
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-2
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-4
	 *
	 * The send never reaches OpenRegister's email leaf: the leaf links Mail-app
	 * messages by account and message id, which a message sent through IMailer
	 * does not have, so a send with or without the leaf installed is the same send.
	 */
	public function send(string $templateId, array $dataRefs, array $options = []): array {
		$address = $this->addressOf(recipient: $this->readRecipient(ref: self::recipientRef(dataRefs: $dataRefs)));
		$this->assertMailServer();

		$letter  = $this->correspondence->compose(templateId: $templateId, dataRefs: $dataRefs, options: (['format' => 'email'] + $options));
		$subject = self::subjectOf(template: $letter['template']);
		$message = $this->message(address: $address, subject: $subject, body: $letter['content']);
		if (($options['attachPdf'] ?? false) === true) {
			$pdf = $this->correspondence->compose(templateId: $templateId, dataRefs: $dataRefs, options: (['format' => 'pdf'] + $options));
			$message->attach($this->mailer->createAttachment($pdf['content'], self::pdfName(subject: $subject), 'application/pdf'));
		}

		$failure = $this->deliver(message: $message);
		$sentAt  = null;
		$sending = ['status' => 'failed', 'errorMessage' => $failure];
		if ($failure === null) {
			$sentAt  = date('c');
			$sending = [
				'status' => 'sent',
				'extra' => ['sentAt' => $sentAt, 'sentTo' => $address, 'sentBy' => (string)($options['userId'] ?? '')],
			];
		}

		$entry = $this->correspondence->logSent(
			templateId: $templateId,
			templateName: (string)($letter['template']['name'] ?? ''),
			dataRefs: $dataRefs,
			options: $options,
			sending: $sending
		);

		return [
			'status' => $sending['status'],
			'to' => $address,
			'subject' => $subject,
			'sentAt' => $sentAt,
			'warnings' => $letter['warnings'],
			'registerEntry' => $entry,
		];

	}//end send()

	/**
	 * The recipient's data reference: the first one, with register, schema and id.
	 *
	 * @param array $dataRefs The data references of the request.
	 *
	 * @return array{register: string, schema: string, id: string}
	 *
	 * @throws CorrespondenceSendRefused 400 when it names no recipient.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private static function recipientRef(array $dataRefs): array {
		$first = ($dataRefs[0] ?? null);
		if (is_array($first) === false) {
			throw new CorrespondenceSendRefused(message: self::NO_RECIPIENT, code: 400);
		}

		$ref = [];
		foreach (['register', 'schema', 'id'] as $key) {
			$value = ($first[$key] ?? '');
			if (is_scalar($value) === false || trim((string)$value) === '') {
				throw new CorrespondenceSendRefused(message: self::NO_RECIPIENT, code: 400);
			}

			$ref[$key] = trim((string)$value);
		}

		return $ref;

	}//end recipientRef()

	/**
	 * Read the recipient object with the caller's rights.
	 *
	 * @param array{register: string, schema: string, id: string} $ref The recipient's reference.
	 *
	 * @return array<string, mixed> The recipient's data.
	 *
	 * @throws CorrespondenceSendRefused 404 when it is not there or the caller may not read it.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private function readRecipient(array $ref): array {
		try {
			$found = $this->objects->resolve()->find(
				id: $ref['id'],
				options: new ObjectFindOptions(register: $ref['register'], schema: $ref['schema'])
			);
		} catch (Throwable $e) {
			$found = null;
		}

		$data = null;
		if (is_object($found) === true && method_exists($found, 'getObject') === true) {
			$data = $found->getObject();
		}

		if (is_array($data) === false) {
			throw new CorrespondenceSendRefused(message: self::NOT_FOUND, code: 404);
		}

		return $data;

	}//end readRecipient()

	/**
	 * The recipient's email address: the first address field that holds a valid one.
	 *
	 * @param array<string, mixed> $recipient The recipient's data.
	 *
	 * @return string
	 *
	 * @throws CorrespondenceSendRefused 422 when it has none.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private function addressOf(array $recipient): string {
		foreach (self::ADDRESS_FIELDS as $field) {
			$value = ($recipient[$field] ?? null);
			if (is_string($value) === true && $this->mailer->validateMailAddress(trim($value)) === true) {
				return trim($value);
			}
		}

		throw new CorrespondenceSendRefused(message: self::NO_ADDRESS, code: 422);

	}//end addressOf()

	/**
	 * Refuse when the instance sends no mail: Nextcloud's mail mode `null`.
	 *
	 * @return void
	 *
	 * @throws CorrespondenceSendRefused 422.
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private function assertMailServer(): void {
		if ($this->config->getSystemValueString('mail_smtpmode', 'smtp') === 'null') {
			throw new CorrespondenceSendRefused(message: self::NO_MAIL_SERVER, code: 422);
		}

	}//end assertMailServer()

	/**
	 * The message: to the address, under the subject, with the composed body as HTML and as text.
	 *
	 * @param string $address The recipient's address.
	 * @param string $subject The subject line.
	 * @param string $body The composed email body.
	 *
	 * @return IMessage
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private function message(string $address, string $subject, string $body): IMessage {
		$message = $this->mailer->createMessage();
		$message->setTo([$address]);
		$message->setSubject($subject);
		$message->setHtmlBody($body);
		$message->setPlainBody(self::plainText(html: $body));

		return $message;

	}//end message()

	/**
	 * Send the message: null when it went, else the reason it did not.
	 *
	 * @param IMessage $message The message.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private function deliver(IMessage $message): ?string {
		try {
			$refused = $this->mailer->send($message);
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: a letter could not be sent by email', ['exception' => $e->getMessage()]);
			return $e->getMessage();
		}

		if ($refused !== []) {
			$this->logger->warning('Filinq: the mail server refused the letter\'s recipient');
			return self::ADDRESS_REFUSED;
		}

		return null;

	}//end deliver()

	/**
	 * The subject of a letter: the template's subject line, or its name when it has none.
	 *
	 * @param array<string, mixed> $template The template.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private static function subjectOf(array $template): string {
		foreach (['subject', 'name'] as $field) {
			$value = ($template[$field] ?? null);
			if (is_string($value) === true && trim($value) !== '') {
				return trim($value);
			}
		}

		return '';

	}//end subjectOf()

	/**
	 * The plain-text alternative of an HTML body.
	 *
	 * @param string $html The HTML body.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private static function plainText(string $html): string {
		$withBreaks = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr)\b[^>]*>/i', "\n", $html);
		$text       = html_entity_decode(strip_tags((string)$withBreaks), (ENT_QUOTES | ENT_HTML5), 'UTF-8');

		return trim((string)preg_replace("/\n{3,}/", "\n\n", (string)preg_replace('/[ \t]+/', ' ', $text)));

	}//end plainText()

	/**
	 * The attachment's file name: the subject made safe, or "letter".
	 *
	 * @param string $subject The subject line.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/specs/letter-correspondence-generation/spec.md
	 */
	private static function pdfName(string $subject): string {
		$name = trim((string)preg_replace('/[^\p{L}\p{N} ._-]+/u', '', $subject));
		if ($name === '') {
			$name = 'letter';
		}

		return $name . '.pdf';

	}//end pdfName()
}//end class
