<?php

/**
 * Nothing is written until a person has checked it.
 *
 * 🔴 DETECTION IS A MACHINE'S OPINION ABOUT WHERE THE NAMES ARE. It is a good
 * opinion and it is not a decision. The decision — "this copy may leave the
 * building" — is a person's, and before this gate there was nothing in the
 * service that required one to have been made. The screen had a review step;
 * the API did not, and the batch path ran fifty-five thousand documents past it
 * in an afternoon.
 *
 * 🔴 SO THE GATE IS IN THE SERVICE, NOT ON THE SCREEN. A rule enforced in a
 * component is a rule that applies to whoever uses that component. The API, the
 * batch path and the leaf all reach the same refusal here, with one message, so
 * there is no path that is merely less convenient rather than closed.
 *
 * 🔴 AND RE-DETECTING CLEARS THE MARK. This is the half that is easy to leave
 * out and impossible to notice: somebody checks a document, detection is re-run
 * with a new model or a new prohibition list, and the mark from the old run
 * still authorises output for entities nobody has looked at. The approval was
 * real; it was about a different set of findings. A mark that outlives its run
 * is a stale name telling an administrator the binding works.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCP\IL10N;

/**
 * Decides whether a redacted copy may be written yet.
 */
class RedactionReviewGate {

	/**
	 * Constructor.
	 *
	 * 🔴 THE REFUSAL IS THE ONLY THING A USER EVER SEES OF THIS CLASS, so it
	 * is translated. `IL10N` is nullable and defaults to null, the pattern
	 * `LegalBasesSummaryService` already uses here: dependency injection always
	 * supplies it, and where it is absent the English source string is
	 * returned, which is a correct message rather than a placeholder.
	 *
	 * @param IL10N|null $l10n The acting user's language, when there is one.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ?IL10N $l10n = null,
	) {

	}//end __construct()

	/**
	 * Nobody has checked this document at all.
	 *
	 * @var string
	 */
	public const NEVER_CHECKED = 'never_checked';

	/**
	 * It was checked, and then detection was re-run.
	 *
	 * @var string
	 */
	public const CHECKED_AN_OLDER_RUN = 'checked_an_older_run';

	/**
	 * A mark exists but does not say who checked it, so nobody is accountable.
	 *
	 * @var string
	 */
	public const UNATTRIBUTED = 'unattributed';

	/**
	 * Why this document may not be written yet, if it may not.
	 *
	 * @param array<string, mixed>|null $mark            The review mark, or null.
	 * @param string                    $detectionRunId  The detection run being published.
	 *
	 * @return array<string, mixed>|null The refusal, or null when output may proceed.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function refuse(?array $mark, string $detectionRunId): ?array {
		if ($detectionRunId === '') {
			// Without a run to compare against, every mark looks current. That
			// is the shape that lets a stale approval through, so it refuses.
			return $this->refusal(
				reason: self::NEVER_CHECKED,
				message: $this->say(
					message: 'This document has no detection run to publish. Run the detection, check the '
						.'result, and try again.'
				)
			);
		}

		if ($mark === null || $mark === []) {
			return $this->refusal(
				reason: self::NEVER_CHECKED,
				message: $this->say(
					message: 'Nobody has checked this document yet. Open it, look at what the detection '
						.'marked, and confirm it before the redacted copy is written.'
				)
			);
		}

		$checkedRun = trim((string)($mark['detectionRun'] ?? ''));
		if ($checkedRun !== $detectionRunId) {
			// 🔴 THE APPROVAL WAS REAL AND IT WAS ABOUT DIFFERENT FINDINGS.
			return $this->refusal(
				reason: self::CHECKED_AN_OLDER_RUN,
				message: sprintf(
					$this->say(
						message: 'This document was checked, but the detection has been run again since. '
							.'The earlier check was about a different set of findings, so it does not cover '
							.'this one. Look at the new result and confirm it again.%s'
					),
					$this->checkedByClause(mark: $mark)
				)
			);
		}

		if (trim((string)($mark['checkedBy'] ?? '')) === '') {
			// A check nobody signed is a check nobody can be asked about, which
			// is the thing an accountability record exists to prevent.
			return $this->refusal(
				reason: self::UNATTRIBUTED,
				message: $this->say(
					message: 'This document is marked as checked, but the mark does not say who checked it. '
						.'Confirm it again so the record names a person.'
				)
			);
		}

		return null;
	}//end refuse()

	/**
	 * Whether a redacted copy may be written for this document.
	 *
	 * @param array<string, mixed>|null $mark           The review mark, or null.
	 * @param string                    $detectionRunId The detection run.
	 *
	 * @return bool True only when a person has checked this run.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function mayWrite(?array $mark, string $detectionRunId): bool {
		return ($this->refuse(mark: $mark, detectionRunId: $detectionRunId) === null);
	}//end mayWrite()

	/**
	 * The mark a re-run of detection leaves behind.
	 *
	 * Null, always. Carrying the old mark forward with a note, or keeping it
	 * "for reference", is how it ends up read as an approval: every surface
	 * that asks "is there a mark" gets yes.
	 *
	 * @return array<string, mixed>|null Nothing.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function markAfterRedetection(): ?array {
		return null;
	}//end markAfterRedetection()

	/**
	 * Which documents in a batch are refused, and why each one.
	 *
	 * 🔴 THE BATCH REPORTS ITS REFUSALS RATHER THAN DROPPING THEM. A run over
	 * fifty-five thousand documents that quietly writes the reviewed ones and
	 * says nothing about the rest reads as a complete run, and the gap is
	 * invisible precisely because it is large.
	 *
	 * @param array<int, array<string, mixed>> $documents Each with `id`, `mark` and `detectionRun`.
	 *
	 * @return array{write: array<int, string>, refused: array<int, array<string, mixed>>}
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function screenBatch(array $documents): array {
		$write = [];
		$refused = [];

		foreach ($documents as $document) {
			$id = (string)($document['id'] ?? '');
			$refusal = $this->refuse(
				mark: ($document['mark'] ?? null),
				detectionRunId: (string)($document['detectionRun'] ?? '')
			);

			if ($refusal === null) {
				$write[] = $id;
				continue;
			}

			$refused[] = array_merge(['document' => $id], $refusal);
		}

		return ['write' => $write, 'refused' => $refused];
	}//end screenBatch()

	/**
	 * Who checked it and when, appended to a refusal that needs it.
	 *
	 * @param array<string, mixed> $mark The mark.
	 *
	 * @return string The clause, or an empty string.
	 */
	private function checkedByClause(array $mark): string {
		$who = trim((string)($mark['checkedBy'] ?? ''));
		$when = trim((string)($mark['checkedAt'] ?? ''));
		if ($who === '' || $when === '') {
			return '';
		}

		return sprintf($this->say(message: ' The earlier check was by %s on %s.'), $who, $when);
	}//end checkedByClause()

	/**
	 * One refusal.
	 *
	 * @param string $reason  The machine-readable reason.
	 * @param string $message What the operator reads.
	 *
	 * @return array<string, mixed> The refusal.
	 */
	private function refusal(string $reason, string $message): array {
		return ['reason' => $reason, 'message' => $message];
	}//end refusal()

	/**
	 * One message in the reader's language, or in English when there is none.
	 *
	 * @param string $message The English source string.
	 *
	 * @return string The message.
	 */
	private function say(string $message): string {
		if ($this->l10n === null) {
			return $message;
		}

		return $this->l10n->t($message);
	}//end say()
}//end class
