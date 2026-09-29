<?php

/**
 * Consent clearance
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use DateTimeImmutable;
use Throwable;

/**
 * Whether a document's consent requests allow publication. Reads only.
 *
 * A record permits publication when the third party agreed
 * (`consent_given`), their data was anonymised (`anonymized`), they did not
 * react before the objection window closed (`no_response` with the deadline
 * passed), or they objected and the decision is to anonymise them. A
 * `reject` decision always blocks, and a record still `pending` blocks.
 * Standing consents (`scope: entity`) are rules, not requests, and are
 * skipped. The window itself is ObjectionDeadlineChecker's; this only reads
 * the deadline it wrote.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/publication-consent/spec.md
 */
class ConsentClearance {

	/**
	 * Decisions that resolve an objection by anonymising the objector.
	 */
	private const RESOLVING_DECISIONS = ['anonymize', 'publish_anonymized'];

	/**
	 * The verdict over one document's consent records.
	 *
	 * @param array<int, array<string, mixed>> $consents The records
	 * @param DateTimeImmutable                $now      The moment to judge deadlines at
	 *
	 * @return array{clear: bool, reasons: list<string>} Clear, or why not. Reasons name record ids only.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.1
	 */
	public function evaluate(array $consents, DateTimeImmutable $now): array {
		$reasons = [];
		foreach ($consents as $consent) {
			if (($consent['scope'] ?? 'document') === 'entity') {
				continue;
			}

			$reason = $this->blocker(consent: $consent, now: $now);
			if ($reason !== null) {
				$reasons[] = 'Consent request ' . (string) ($consent['id'] ?? $consent['uuid'] ?? '?') . ': ' . $reason;
			}
		}

		return ['clear' => $reasons === [], 'reasons' => $reasons];

	}//end evaluate()

	/**
	 * Why one record blocks publication, or null when it does not.
	 *
	 * @param array<string, mixed> $consent The record
	 * @param DateTimeImmutable    $now     The moment
	 *
	 * @return string|null The reason.
	 */
	private function blocker(array $consent, DateTimeImmutable $now): ?string {
		$decision = (string) ($consent['publicationDecision'] ?? 'pending');
		if ($decision === 'reject') {
			return 'publication was rejected';
		}

		$status = (string) ($consent['consentStatus'] ?? 'pending');
		if ($status === 'consent_given' || $status === 'anonymized') {
			return null;
		}

		if ($status === 'objection_received') {
			if (in_array($decision, self::RESOLVING_DECISIONS, true) === true) {
				return null;
			}

			return 'an objection was received and no decision to anonymise was taken';
		}

		$deadline = $this->deadline(consent: $consent);
		if ($status === 'no_response' && $deadline !== null && $deadline <= $now) {
			return null;
		}

		if ($deadline !== null && $deadline > $now) {
			return 'waiting for a reaction until ' . $deadline->format('Y-m-d');
		}

		return 'no reaction recorded yet';

	}//end blocker()

	/**
	 * The objection deadline of a record, or null when it has none or it cannot be read.
	 *
	 * @param array<string, mixed> $consent The record
	 *
	 * @return DateTimeImmutable|null The deadline.
	 */
	private function deadline(array $consent): ?DateTimeImmutable {
		$value = (string) ($consent['objectionDeadline'] ?? '');
		if ($value === '') {
			return null;
		}

		try {
			return new DateTimeImmutable($value);
		} catch (Throwable) {
			return null;
		}

	}//end deadline()
}//end class
