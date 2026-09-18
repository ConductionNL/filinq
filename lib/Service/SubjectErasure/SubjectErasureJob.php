<?php

/**
 * Running an erasure, and what a half-finished one leaves behind.
 *
 * 🔴 THIS RUN CANNOT BE ALL-OR-NOTHING, AND THAT IS THE DIFFERENCE FROM THE
 * PLATFORM. OpenRegister's anonymisation prepares every target before applying
 * any, so a record is never half done. It can do that because the unit is ONE
 * record and a handful of stores.
 *
 * Here the unit is a person across eighty documents, each a separate file, each
 * with its own versions. There is no transaction across them and pretending
 * otherwise would mean holding eighty files open and rolling back content that
 * has already been rewritten. So a run CAN stop half way, and the honest design
 * is to make the half-way state finishable and visible rather than to deny it.
 *
 * WHAT A STOPPED RUN LEAVES BEHIND, precisely:
 *
 *  - Documents already done are done. Their occurrences are erased and each
 *    one is counted on the request. Nothing is rolled back, because rolling an
 *    erasure back means putting the person's name back into a record they asked
 *    to be removed from.
 *  - The document being written when it stopped is the only ambiguous one. It
 *    is recorded as `inFlight` and re-erased on resume, which is safe because
 *    erasing an already-erased occurrence finds nothing and changes nothing.
 *  - Documents not yet reached are untouched.
 *  - The request reads `partially_completed`, not `completed` and not
 *    `running`. A stopped run that still says `running` is indistinguishable
 *    from a slow one, and nobody looks at it until the deadline passes.
 *
 * 🔴 AND THE DEADLINE IS WHY THIS MATTERS MORE THAN FOR AN ARCHIVAL SWEEP. A
 * retention sweep that stops can run again next night. A subject erasure has a
 * statutory clock attached, so a stopped run that nobody notices is not a
 * delayed job, it is a missed legal deadline with a person on the other end of
 * it. `resumeFrom()` exists so the answer to "what now" is a resume rather than
 * a restart over documents already done.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

/**
 * Decides the order, the resume point and the state a stopped run leaves.
 */
class SubjectErasureJob {

	/**
	 * The run is going.
	 *
	 * @var string
	 */
	public const RUNNING = 'running';

	/**
	 * The run stopped with work left. Not finished, not never-started.
	 *
	 * @var string
	 */
	public const PARTIALLY_COMPLETED = 'partially_completed';

	/**
	 * Every document in scope was reached.
	 *
	 * @var string
	 */
	public const COMPLETED = 'completed';

	/**
	 * The documents still to do, given what a previous run recorded.
	 *
	 * Resumes AT the last recorded document rather than after it, because that
	 * one may have been mid-write when the run stopped. Erasing an occurrence
	 * that is already gone finds nothing and changes nothing, so re-doing one
	 * document is free and skipping one is not.
	 *
	 * @param array<int, string>   $documents The documents in scope, in order.
	 * @param array<string, mixed> $progress  What the last run recorded.
	 *
	 * @return array<int, string> The documents still to do.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
	 */
	public function resumeFrom(array $documents, array $progress): array {
		$last = trim((string)($progress['lastDocument'] ?? ''));
		if ($last === '') {
			return $documents;
		}

		$position = array_search($last, $documents, true);
		if ($position === false) {
			// The resume point is not in scope any more: the scope changed
			// between runs. Starting over is the safe reading, because the
			// alternative silently skips whatever moved.
			return $documents;
		}

		return array_values(array_slice($documents, (int)$position));
	}//end resumeFrom()

	/**
	 * The state a run is left in after processing some of its scope.
	 *
	 * @param int $total The documents in scope.
	 * @param int $done  How many were completed.
	 *
	 * @return string The status.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
	 */
	public function statusFor(int $total, int $done): string {
		if ($total <= 0 || $done >= $total) {
			return self::COMPLETED;
		}

		// 🔴 NOT `running`. A stopped run that still says running is
		// indistinguishable from a slow one, and nobody looks at it until the
		// deadline has passed.
		return self::PARTIALLY_COMPLETED;
	}//end statusFor()

	/**
	 * What a stopped run leaves behind, in words, for the request and the
	 * operator reading it.
	 *
	 * @param array<string, mixed> $progress What was recorded.
	 * @param string               $dueAt    When the request is due.
	 *
	 * @return array<string, mixed> The account of the stopped run.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
	 */
	public function accountOf(array $progress, string $dueAt = ''): array {
		$total = (int)($progress['documentsTotal'] ?? 0);
		$done = (int)($progress['documentsDone'] ?? 0);
		$status = $this->statusFor(total: $total, done: $done);

		$account = [
			'status' => $status,
			'documentsDone' => $done,
			'documentsTotal' => $total,
			'documentsRemaining' => max(0, ($total - $done)),
			'resumePoint' => trim((string)($progress['lastDocument'] ?? '')),
			'occurrencesErased' => (int)($progress['occurrencesErased'] ?? 0),
			'rolledBack' => false,
		];

		if ($status === self::COMPLETED) {
			$account['message'] = 'Every document in scope was reached.';

			return $account;
		}

		$due = '';
		if ($dueAt !== '') {
			$due = ' and is due '.$dueAt;
		}

		$account['message'] = sprintf(
			'%d of %d documents were erased and stay erased; nothing is rolled back, because rolling an '
			.'erasure back puts the name back into a record somebody asked to be removed from. The '
			.'remaining %d are untouched and the run resumes at "%s". This request is NOT answered yet%s.',
			$done,
			$total,
			max(0, ($total - $done)),
			$account['resumePoint'],
			$due
		);

		return $account;
	}//end accountOf()
}//end class
