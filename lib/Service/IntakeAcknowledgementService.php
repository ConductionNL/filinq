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
 * the outgoing post. The outcome is answered, never thrown: an
 * acknowledgement that cannot be sent never refuses an arrival (D2).
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
	 */
	public function __construct(
		private readonly DocumentServiceInterface $documents,
		private readonly SenderReach $reach,
		private readonly IL10N $l10n,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Acknowledge one saved intake document under its rule.
	 *
	 * @param array<string, mixed> $document The saved intake document, with its uuid.
	 * @param array<string, mixed> $rule     The intake default rule that matched it.
	 *
	 * @return array<string, mixed>|null The acknowledgement outcome (status, address, at, reason), or null when
	 *                                   the rule asks for none or the document is no longer waiting.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-sender-who-refused-contact-is-not-written-to-req-ias-002
	 */
	public function acknowledge(array $document, array $rule): ?array {
		$template = trim((string)($rule['acknowledgeWith'] ?? ''));
		if ($template === '' || ($document['status'] ?? '') !== IntakeRepository::STATUS_RECEIVED) {
			return null;
		}

		try {
			$body = $this->render(template: $template, document: $document);
			$subject = $this->l10n->t('Acknowledgement of receipt: %s', [(string)($document['subject'] ?? '')]);

			return $this->reachSender(document: $document, subject: $subject, body: $body);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeAcknowledgementService] the acknowledgement could not be sent',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => ($document['uuid'] ?? ''), 'error' => $e->getMessage()]
			);

			return $this->outcome(status: self::STATUS_FAILED, address: null, reason: $e->getMessage());
		}
	}//end acknowledge()

	/**
	 * The acknowledgement body, rendered from the rule's template.
	 *
	 * @param string               $template The template id.
	 * @param array<string, mixed> $document The intake document.
	 *
	 * @return string The rendered HTML.
	 */
	private function render(string $template, array $document): string {
		$rendered = $this->documents->generateDocument(
			templateId: $template,
			dataRefs: [],
			options: [
				'format' => 'html',
				'adHocData' => [
					'subject' => (string)($document['subject'] ?? ''),
					'sender' => (string)($document['sender'] ?? ''),
					'receivedAt' => (string)($document['receivedAt'] ?? ''),
					'channel' => (string)($document['channel'] ?? ''),
				],
			]
		);

		return (string)($rendered['html'] ?? $rendered['content'] ?? '');
	}//end render()

	/**
	 * Reach the sender in the order of design D3.
	 *
	 * @param array<string, mixed> $document The intake document.
	 * @param string               $subject  The subject line.
	 * @param string               $body     The body.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function reachSender(array $document, string $subject, string $body): array {
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

		return $this->outcome(status: self::STATUS_QUEUED_AS_LETTER, address: null, reason: null);
	}//end reachSender()

	/**
	 * The acknowledgement status a send unit outcome means.
	 *
	 * @param string      $outcome The send unit's outcome.
	 * @param string|null $address Where it went.
	 *
	 * @return array<string, mixed> The outcome.
	 */
	private function fromSendOutcome(string $outcome, ?string $address): array {
		if (in_array($outcome, self::DISPATCHED, true) === true) {
			return $this->outcome(status: self::STATUS_SENT, address: $address, reason: null);
		}

		if (in_array($outcome, self::REFUSALS, true) === true) {
			return $this->outcome(status: self::STATUS_REFUSED, address: null, reason: $outcome);
		}

		return $this->outcome(status: self::STATUS_FAILED, address: $address, reason: $outcome);
	}//end fromSendOutcome()

	/**
	 * One acknowledgement record.
	 *
	 * @param string      $status  sent, queued-as-letter, refused or failed.
	 * @param string|null $address Where it went.
	 * @param string|null $reason  Why it did not go.
	 *
	 * @return array<string, mixed> status, address, at, reason.
	 */
	private function outcome(string $status, ?string $address, ?string $reason): array {
		return [
			'status' => $status,
			'address' => $address,
			'at' => $this->time->getDateTime('now', new \DateTimeZone('UTC'))->format('c'),
			'reason' => $reason,
		];
	}//end outcome()
}//end class
