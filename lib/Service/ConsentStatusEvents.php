<?php

/**
 * Consent Status Events
 *
 * A consent record's status changes only by a declared move, and every move is
 * recorded on the record as an event: who, when, from, to and the evidence.
 * Recording that consent was given, or that an objection came in, needs
 * evidence.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/consent-management/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Validates consent status moves and records them as events.
 */
class ConsentStatusEvents {

	/**
	 * The moves a person may make, per field: from => allowed targets.
	 *
	 * @var array<string, array<string, list<string>>>
	 */
	public const MOVES = [
		'consentStatus' => [
			'pending' => ['consent_given', 'objection_received', 'no_response'],
			'no_response' => ['consent_given', 'objection_received'],
		],
		'notificationStatus' => [
			'pending' => ['sent', 'skipped', 'failed'],
			'sent' => ['delivered', 'failed'],
			'failed' => ['sent'],
		],
	];

	/**
	 * Targets that need evidence.
	 *
	 * @var list<string>
	 */
	public const NEEDS_EVIDENCE = ['consent_given', 'objection_received'];

	/**
	 * Check every status change and append it to `statusEvents`.
	 *
	 * @param array<string, mixed> $existing The stored record.
	 * @param array<string, mixed> $changes  The whitelisted changes.
	 * @param string               $actor    Who makes them.
	 * @param string               $evidence How the outcome reached the organisation.
	 * @param DateTimeImmutable    $now      When.
	 *
	 * @return array<string, mixed> The changes, with `statusEvents` when a status moved.
	 *
	 * @throws InvalidArgumentException With code 400 for an undeclared move or missing evidence.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function apply(array $existing, array $changes, string $actor, string $evidence, DateTimeImmutable $now): array {
		$events = [];
		foreach (array_keys(self::MOVES) as $field) {
			$event = $this->eventFor(field: $field, existing: $existing, changes: $changes, evidence: $evidence);
			if ($event !== null) {
				$events[] = $event + ['by' => $actor, 'at' => $now->format(DateTimeInterface::ATOM)];
			}
		}

		if ($events !== []) {
			$changes['statusEvents'] = array_merge((array) ($existing['statusEvents'] ?? []), $events);
		}

		return $changes;

	}//end apply()

	/**
	 * The event for one field, or null when it does not change.
	 *
	 * @param string               $field    The status field.
	 * @param array<string, mixed> $existing The stored record.
	 * @param array<string, mixed> $changes  The changes.
	 * @param string               $evidence The evidence given.
	 *
	 * @return array<string, string>|null The event without who and when.
	 *
	 * @throws InvalidArgumentException With code 400 for an undeclared move or missing evidence.
	 */
	private function eventFor(string $field, array $existing, array $changes, string $evidence): ?array {
		$from = (string) ($existing[$field] ?? 'pending');
		$to = (string) ($changes[$field] ?? $from);
		if ($to === $from) {
			return null;
		}

		if (in_array($to, (self::MOVES[$field][$from] ?? []), true) === false) {
			throw new InvalidArgumentException(sprintf('%s cannot move from "%s" to "%s".', $field, $from, $to), 400);
		}

		if (in_array($to, self::NEEDS_EVIDENCE, true) === true && trim($evidence) === '') {
			throw new InvalidArgumentException(sprintf('Recording %s needs evidence.', $to), 400);
		}

		return ['field' => $field, 'from' => $from, 'to' => $to, 'evidence' => trim($evidence)];

	}//end eventFor()
}//end class
