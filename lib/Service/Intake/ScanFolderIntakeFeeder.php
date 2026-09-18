<?php

/**
 * Turning a file in a watched folder into an intake, and refusing to lie when it fails.
 *
 * 🔴 A REFUSAL THAT ONLY LANDS WHERE THERE IS A RESPONSE TO RETURN IS NOT A
 * RULE IN A BACKGROUND PATH. `IntakeService::receive()` genuinely refuses an
 * unknown channel and de-duplicates on `sourceRef`, and until now
 * `IntakeRefusedException` was caught in exactly one place: the HTTP
 * controller, where a refusal becomes a status code somebody sees.
 *
 * A folder watch has no response to return. The obvious listener catches, logs
 * and returns — and then a scanned page sits in a folder believed processed,
 * the inbox shows nothing, the paper original has already been filed, and
 * nobody is looking. That is worse than a crash: a crash gets noticed.
 *
 * 🔴 SO THIS NEVER SAYS "HANDLED" FOR SOMETHING IT DID NOT RECEIVE. It returns
 * an outcome the caller MUST act on, naming where the file must be left and who
 * has to see it. It deliberately does not move the file itself: the watch is
 * owned by `scan-intake-with-separator-sheets`, and a feeder that quietly moved
 * files would be a second thing deciding where paper lives.
 *
 * 🔴 AND "ALREADY SEEN" IS NOT "RECEIVED". A duplicate `sourceRef` means the
 * channel delivered this page before. The intake exists, so nothing is wrong —
 * but reporting it as a fresh receipt inflates every count an operator uses to
 * decide whether the scanner is working.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

/**
 * What became of one watched file, and what must happen to it next.
 */
final class ScanFolderIntakeFeeder {

	/**
	 * The channel a watched folder delivers on.
	 *
	 * @var string
	 */
	public const CHANNEL = 'scan';

	/**
	 * An intake document was created for this file.
	 *
	 * @var string
	 */
	public const RECEIVED = 'received';

	/**
	 * The channel had delivered this file before.
	 *
	 * @var string
	 */
	public const ALREADY_SEEN = 'already_seen';

	/**
	 * The intake was refused. Nothing was created.
	 *
	 * @var string
	 */
	public const REFUSED = 'refused';

	/**
	 * Where a file must be left so a person finds it.
	 *
	 * @var string
	 */
	public const LEAVE_FOR_A_PERSON = 'leave_in_place_for_a_person';

	/**
	 * Where a file may be moved once it is safely an intake.
	 *
	 * @var string
	 */
	public const MOVE_TO_PROCESSED = 'move_to_processed';

	/**
	 * The outcome for one watched file.
	 *
	 * The caller cannot read this as a boolean, on purpose. `handled` alone
	 * would collapse the three states that matter into one, and the collapse is
	 * how a refused page ends up in the processed folder.
	 *
	 * @param string $outcome    received / already_seen / refused.
	 * @param string $fileName   The file, so the refusal names it.
	 * @param string $reason     Why, in words, when it was refused.
	 *
	 * @return array<string, mixed> What happened and what must happen next.
	 */
	public function outcomeFor(string $outcome, string $fileName, string $reason = ''): array {
		if ($outcome === self::RECEIVED) {
			return [
				'outcome' => self::RECEIVED,
				'file' => $fileName,
				'disposition' => self::MOVE_TO_PROCESSED,
				'countsAsNewIntake' => true,
				'needsAPerson' => false,
				'message' => '',
			];
		}

		if ($outcome === self::ALREADY_SEEN) {
			return [
				'outcome' => self::ALREADY_SEEN,
				'file' => $fileName,
				// Safe to move: the intake exists, this page is accounted for.
				'disposition' => self::MOVE_TO_PROCESSED,
				// 🔴 BUT IT IS NOT A NEW RECEIPT. Counting it inflates the only
				// number an operator has for "is the scanner working".
				'countsAsNewIntake' => false,
				'needsAPerson' => false,
				'message' => sprintf('"%s" had already been delivered on this channel, so it was not taken in twice.', $fileName),
			];
		}

		return [
			'outcome' => self::REFUSED,
			'file' => $fileName,
			// 🔴 NOT MOVED. A refused page in a processed folder is a page
			// nobody will ever look at again, and the paper original has
			// already been filed.
			'disposition' => self::LEAVE_FOR_A_PERSON,
			'countsAsNewIntake' => false,
			'needsAPerson' => true,
			'message' => sprintf(
				'"%s" was NOT taken into the inbox: %s The file has been left where it is, because moving '
				.'it would put a page nobody has into a folder everybody treats as done.',
				$fileName,
				$this->sentence(reason: $reason)
			),
		];
	}//end outcomeFor()

	/**
	 * Whether this outcome permits the watch to move the file on.
	 *
	 * A separate method because a caller otherwise tests the outcome string,
	 * and the one state that must NOT move is the one an `!== RECEIVED` test
	 * gets wrong in the safe direction and an `=== REFUSED` test gets wrong in
	 * the dangerous one.
	 *
	 * @param array<string, mixed> $outcome The outcome.
	 *
	 * @return bool True when the file may leave the watched folder.
	 */
	public function mayMoveOn(array $outcome): bool {
		return (($outcome['disposition'] ?? self::LEAVE_FOR_A_PERSON) === self::MOVE_TO_PROCESSED);
	}//end mayMoveOn()

	/**
	 * A summary of one sweep, with the refusals countable.
	 *
	 * The three counts are separate. "40 files processed" over 37 receipts, one
	 * duplicate and two refusals is a sentence that hides the only two a person
	 * has to act on.
	 *
	 * @param array<int, array<string, mixed>> $outcomes Every file's outcome.
	 *
	 * @return array<string, mixed> The summary.
	 */
	public function summarise(array $outcomes): array {
		$received = 0;
		$alreadySeen = 0;
		$refused = [];

		foreach ($outcomes as $outcome) {
			$state = (string)($outcome['outcome'] ?? '');
			if ($state === self::RECEIVED) {
				$received++;
				continue;
			}

			if ($state === self::ALREADY_SEEN) {
				$alreadySeen++;
				continue;
			}

			$refused[] = ['file' => (string)($outcome['file'] ?? ''), 'message' => (string)($outcome['message'] ?? '')];
		}

		return [
			'received' => $received,
			'alreadySeen' => $alreadySeen,
			'refused' => $refused,
			'refusedCount' => count($refused),
			'needsAPerson' => ($refused !== []),
		];
	}//end summarise()

	/**
	 * A refusal reason as a sentence, never empty.
	 *
	 * @param string $reason The reason.
	 *
	 * @return string The sentence.
	 */
	private function sentence(string $reason): string {
		$text = trim($reason);
		if ($text === '') {
			return 'the intake was refused and no reason was recorded, which itself needs looking at.';
		}

		return rtrim($text, '.').'.';
	}//end sentence()
}//end class
