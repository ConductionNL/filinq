<?php

/**
 * Intake Acknowledgement Service: tells the sender an arriving document was received.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\Intake\AnsweringRegistration;
use OCA\Filinq\Service\Intake\SenderReach;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Renders the acknowledgement the matching intake default rule names
 * (`acknowledgeWith`) and reaches the sender in the order of design D3:
 * a party linked in the sender role through openregister's party
 * notification, else a mailed document's e-mail address, else a letter for
 * the outgoing post, stored as a PDF in Files. When an inbound
 * `documentRegistration` names the document, the letter carries its number
 * and is registered as the outbound entry that answers it (D4). The outcome
 * is answered, never thrown: an acknowledgement that cannot be sent never
 * refuses an arrival (D2).
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 */
class IntakeAcknowledgementService {

	public const STATUS_SENT = 'sent';

	public const STATUS_QUEUED_AS_LETTER = 'queued-as-letter';

	public const STATUS_REFUSED = 'refused';

	public const STATUS_FAILED = 'failed';

	/**
	 * Send unit outcomes that mean the mail went out.
	 */
	private const DISPATCHED = ['dispatched', 'sent'];

	/**
	 * Party outcomes that mean the party said no.
	 */
	private const REFUSALS = ['refused-by-indicator', 'refused-opted-out'];

	/**
	 * Constructor.
	 *
	 * @param DocumentServiceInterface $documents Renders the template.
	 * @param SenderReach              $reach     Reaches the sender through openregister.
	 * @param IL10N                    $l10n      The subject line.
	 * @param ITimeFactory             $time      When it happened.
	 * @param LoggerInterface          $logger    Records a failed acknowledgement.
	 * @param AnsweringRegistration|null $registrations Registers the answer; absent, none is registered.
	 */
	public function __construct(
		private readonly DocumentServiceInterface $documents,
		private readonly SenderReach $reach,
		private readonly IL10N $l10n,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
		private readonly AnsweringRegistration|null $registrations = null,
	) {
	}//end __construct()

	/**
	 * Acknowledge one saved intake document under its rule.
	 *
	 * @param array<string, mixed> $document The saved intake document, with its uuid.
	 * @param array<string, mixed> $rule     The intake default rule that matched it.
	 * @param array<string, mixed>|null $inbound The inbound registration naming the document, with its `uuid`,
	 *                                           `unit` and `registrationNumber`; null when there is none.
	 *
	 * @return array<string, mixed>|null The acknowledgement outcome (status, address, at, reason, and letter and
	 *                                   registration when there are), or null when the rule asks for none or the
	 *                                   document is no longer waiting.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-sender-who-refused-contact-is-not-written-to-req-ias-002
	 */
	public function acknowledge(array $document, array $rule, array|null $inbound = null): array|null {
		$template = trim((string)($rule['acknowledgeWith'] ?? ''));
		if ($template === '' || ($document['status'] ?? '') !== IntakeRepository::STATUS_RECEIVED) {
			return null;
		}

		$data = self::letterData(document: $document, inbound: $inbound);
		try {
			$rendered = $this->render(template: $template, data: $data, format: 'html');
			$subject = $this->l10n->t('Acknowledgement of receipt: %s', [$data['subject']]);

			$outcome = $this->reachSender(document: $document, message: ['subject' => $subject, 'body' => self::plainText(html: $rendered['body'])]);
			if ($outcome['status'] === self::STATUS_QUEUED_AS_LETTER) {
				$letter = $this->render(template: $template, data: $data, format: 'pdf');
				$outcome['letter'] = $letter['fileId'];
				$rendered = $letter;
			}
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeAcknowledgementService] the acknowledgement could not be sent',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => ($document['uuid'] ?? ''), 'error' => $e->getMessage()]
			);

			return $this->outcome(status: self::STATUS_FAILED, details: ['reason' => $e->getMessage()]);
		}

		return $this->registerAnswer(outcome: $outcome, inbound: $inbound, letterRecord: $rendered['record']);
	}//end acknowledge()

	/**
	 * What the letter names: the arrival, and the inbound number when there is one (D4).
	 *
	 * @param array<string, mixed>      $document The intake document.
	 * @param array<string, mixed>|null $inbound  The inbound registration.
	 *
	 * @return array<string, string>
	 */
	private static function letterData(array $document, array|null $inbound): array {
		return [
			'subject' => self::text(value: ($document['subject'] ?? '')),
			'sender' => self::text(value: ($document['sender'] ?? '')),
			'receivedAt' => self::receivedDate(receivedAt: (string)($document['receivedAt'] ?? '')),
			'channel' => (string)($document['channel'] ?? ''),
			'registrationNumber' => (string)($inbound['registrationNumber'] ?? ''),
		];
	}//end letterData()

	/**
	 * A field as text. OpenRegister hands a translatable field to a service as a
	 * map of language to text (`{"nl": "Aanvraag"}`), which a cast turns into
	 * "Array" (seen live in the subject of a sent acknowledgement, 25 Nov 2026).
	 * The letter is Dutch, so nl comes first, then en, then the first text.
	 *
	 * @param mixed $value The field.
	 *
	 * @return string The text; '' when there is none.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	private static function text(mixed $value): string {
		if (is_array($value) === false) {
			return is_scalar($value) === true ? (string)$value : '';
		}

		foreach (['nl', 'en'] as $language) {
			if (isset($value[$language]) === true && is_scalar($value[$language]) === true && (string)$value[$language] !== '') {
				return (string)$value[$language];
			}
		}

		foreach ($value as $candidate) {
			if (is_scalar($candidate) === true && (string)$candidate !== '') {
				return (string)$candidate;
			}
		}

		return '';
	}//end text()

	/**
	 * The arrival as the letter shows it: day-month-year, as on the received stamp.
	 *
	 * @param string $receivedAt The arrival moment, ISO 8601.
	 *
	 * @return string The date, or the value as given when it is not a date.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	private static function receivedDate(string $receivedAt): string {
		if (trim($receivedAt) === '') {
			return '';
		}

		try {
			return (new \DateTimeImmutable($receivedAt))->format('d-m-Y');
		} catch (Throwable) {
			return $receivedAt;
		}
	}//end receivedDate()

	/**
	 * The rendered HTML as the plain text a mail body carries: one paragraph per
	 * block, line breaks kept, no markup (seen live: the mail showed `<p>` tags).
	 *
	 * @param string $html The rendered letter.
	 *
	 * @return string The text.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	private static function plainText(string $html): string {
		$text = (string)preg_replace('~<br\s*/?>~i', "\n", $html);
		$text = (string)preg_replace('~</(p|div|h[1-6]|li|tr)\s*>~i', "\n\n", $text);
		$text = html_entity_decode(strip_tags($text), (ENT_QUOTES | ENT_HTML5), 'UTF-8');
		$text = (string)preg_replace("~[ \t]+\n~", "\n", $text);

		return trim((string)preg_replace("~\n{3,}~", "\n\n", $text));
	}//end plainText()

	/**
	 * The acknowledgement rendered from the rule's template: as HTML for a
	 * mail body, or as a PDF stored in Files for the outgoing post.
	 *
	 * @param string                $template The template id.
	 * @param array<string, string> $data     What the letter names.
	 * @param string                $format   html or pdf.
	 *
	 * @return array{body: string, fileId: int|null, record: string} The body, the stored file and the
	 *                                                               generated document entry.
	 *
	 * @throws \RuntimeException When a letter for the outgoing post was not stored.
	 */
	private function render(string $template, array $data, string $format): array {
		$options = ['format' => $format, 'adHocData' => $data];
		if ($format === 'pdf') {
			$options['output'] = ['mode' => 'files'];
		}

		$rendered = $this->documents->generateDocument(templateId: $template, dataRefs: [], options: $options);

		return [
			'body' => (string)($rendered['html'] ?? $rendered['content'] ?? ''),
			'fileId' => self::storedFileId(rendered: $rendered, format: $format),
			'record' => self::generatedRecord(metadata: ($rendered['metadata'] ?? [])),
		];
	}//end render()

	/**
	 * The file a rendering stored, required for a letter for the outgoing post.
	 *
	 * @param array<string, mixed> $rendered What generateDocument answered.
	 * @param string               $format   html or pdf.
	 *
	 * @return int|null The file id, or null when nothing was stored.
	 *
	 * @throws \RuntimeException When a letter for the outgoing post was not stored.
	 */
	private static function storedFileId(array $rendered, string $format): int|null {
		$fileId = ($rendered['output']['fileId'] ?? null);
		if (is_int($fileId) === true && $fileId > 0) {
			return $fileId;
		}

		if ($format === 'pdf') {
			throw new \RuntimeException('the letter for the outgoing post was not stored');
		}

		if (is_int($fileId) === false) {
			return null;
		}

		return $fileId;
	}//end storedFileId()

	/**
	 * The generated document entry a rendering names in its metadata.
	 *
	 * @param mixed $metadata The rendering's metadata.
	 *
	 * @return string The entry's uuid, or '' when there is none.
	 */
	private static function generatedRecord(mixed $metadata): string {
		if (is_array($metadata) === false) {
			return '';
		}

		return (string)($metadata['uuid'] ?? $metadata['id'] ?? ($metadata['@self']['uuid'] ?? ''));
	}//end generatedRecord()

	/**
	 * Register a sent or queued acknowledgement as the outbound answer to the
	 * inbound registration, when there is one (D4). A refused or failed one
	 * did not go out and is not registered.
	 *
	 * @param array<string, mixed>      $outcome      The acknowledgement outcome.
	 * @param array<string, mixed>|null $inbound      The inbound registration.
	 * @param string                    $letterRecord The generated document entry of what went out.
	 *
	 * @return array<string, mixed> The outcome, with `registration` when one was written.
	 */
	private function registerAnswer(array $outcome, array|null $inbound, string $letterRecord): array {
		$wentOut = in_array($outcome['status'], [self::STATUS_SENT, self::STATUS_QUEUED_AS_LETTER], true);
		if ($this->registrations === null || $inbound === null || $wentOut === false) {
			return $outcome;
		}

		$registration = $this->registrations->registerAnswer(inbound: $inbound, letterUuid: $letterRecord);
		if ($registration !== null) {
			$outcome['registration'] = (string)$registration['uuid'];
		}

		return $outcome;
	}//end registerAnswer()

	/**
	 * Reach the sender in the order of design D3.
	 *
	 * @param array<string, mixed>                $document The intake document.
	 * @param array{subject: string, body: string} $message  The subject line and the body.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function reachSender(array $document, array $message): array {
		['subject' => $subject, 'body' => $body] = $message;
		$parties = $this->reach->notifySenderParties(objectUuid: (string)($document['uuid'] ?? ''), subject: $subject, body: $body);
		foreach ($parties as $party) {
			$outcome = (string)($party['outcome'] ?? '');
			if ($outcome === 'no-address') {
				continue;
			}

			return $this->fromSendOutcome(outcome: $outcome, address: ($party['address'] ?? null));
		}

		$address = trim((string)($document['sender'] ?? ''));
		if (($document['channel'] ?? '') === 'mail' && filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
			return $this->fromSendOutcome(outcome: $this->reach->mailAddress(address: $address, subject: $subject, body: $body), address: $address);
		}

		return $this->outcome(status: self::STATUS_QUEUED_AS_LETTER);
	}//end reachSender()

	/**
	 * The acknowledgement status a send unit outcome means.
	 *
	 * @param string      $outcome The send unit's outcome.
	 * @param string|null $address Where it went.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function fromSendOutcome(string $outcome, string|null $address): array {
		if (in_array($outcome, self::DISPATCHED, true) === true) {
			return $this->outcome(status: self::STATUS_SENT, details: ['address' => $address]);
		}

		if (in_array($outcome, self::REFUSALS, true) === true) {
			return $this->outcome(status: self::STATUS_REFUSED, details: ['reason' => $outcome]);
		}

		return $this->outcome(status: self::STATUS_FAILED, details: ['address' => $address, 'reason' => $outcome]);
	}//end fromSendOutcome()

	/**
	 * One acknowledgement record.
	 *
	 * @param string                                          $status  sent, queued-as-letter, refused or failed.
	 * @param array{address?: string|null, reason?: string|null} $details Where it went and why it did not go; absent is null.
	 *
	 * @return array<string, mixed> status, address, at, reason.
	 */
	private function outcome(string $status, array $details=[]): array {
		return [
			'status' => $status,
			'address' => ($details['address'] ?? null),
			'at' => $this->time->getDateTime('now', new \DateTimeZone('UTC'))->format('c'),
			'reason' => ($details['reason'] ?? null),
		];
	}//end outcome()
}//end class
