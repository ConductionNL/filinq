<?php

/**
 * What an erasure would touch, before anything is written.
 *
 * 🔴 THE BLAST RADIUS IS THE POINT. An erasure walks documents the requester
 * never chose and the operator has never read. "Fatima El-Amrani" is one
 * person; "de Vries" is four thousand occurrences across a municipality, and a
 * run started on the second without looking is a mass edit of records belonging
 * to people who asked for nothing.
 *
 * So nothing is written until somebody has read this, every occurrence is
 * reviewable, and any of them can be excluded with a reason first.
 *
 * 🔴 THE CAP STATES ITSELF, AND STATES THE FULL COUNT. A list silently cut at
 * two hundred reads as the whole answer: the operator approves what they can
 * see and the job runs over everything they cannot. Both numbers are always
 * present, and `truncated` is true rather than implied by a length somebody has
 * to notice.
 *
 * 🔴 AND A FILE THAT CANNOT BE READ IS LISTED, NOT DROPPED. An encrypted
 * archive, a corrupt scan, a format with no text layer: each is a place the
 * person may still be. Leaving them out of the preview makes the erasure look
 * complete, which is the difference between a request answered and a request
 * appearing to have been answered.
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
 * Builds the preview an operator reads before an erasure runs.
 */
class SubjectErasurePreview {

	/**
	 * How many documents are listed before the preview is capped.
	 *
	 * @var int
	 */
	public const CAP = 200;

	/**
	 * Wire the preview.
	 *
	 * @param SubjectErasureRules $rules The obligations that refuse.
	 */
	public function __construct(private readonly SubjectErasureRules $rules = new SubjectErasureRules()) {
	}//end __construct()

	/**
	 * Build the preview.
	 *
	 * @param array<int, array<string, mixed>> $documents Candidate documents, each with
	 *                                                    `id`, `occurrences`, `finalVersion`,
	 *                                                    `unreadable` and any obligations.
	 * @param int                              $cap       How many to list.
	 *
	 * @return array<string, mixed> The preview.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
	 */
	public function build(array $documents, int $cap = self::CAP): array {
		$listed = [];
		$unprocessable = [];
		$occurrences = 0;
		$erasable = 0;
		$refused = 0;

		foreach ($documents as $index => $document) {
			$id = (string)($document['id'] ?? '');
			$count = (int)($document['occurrences'] ?? 0);
			$occurrences += $count;

			$reason = trim((string)($document['unreadable'] ?? ''));
			if ($reason !== '') {
				// Counted and named. A file nobody can read is a place the
				// person may still be, and dropping it makes the erasure look
				// complete.
				$unprocessable[] = ['document' => $id, 'reason' => $reason];
				continue;
			}

			$obligations = $this->rules->refusals(document: $document);
			if ($obligations === []) {
				$erasable++;
			}

			if ($obligations !== []) {
				$refused++;
			}

			if (count($listed) < $cap) {
				$listed[] = [
					'document' => $id,
					'occurrences' => $count,
					'finalVersion' => (($document['finalVersion'] ?? false) === true),
					'obligations' => $obligations,
				];
			}

			unset($index);
		}//end foreach

		$total = count($documents);

		return [
			'documents' => $listed,
			// 🔴 BOTH NUMBERS, ALWAYS. A cap that reports only what it listed
			// reads as the whole answer.
			'listed' => count($listed),
			'documentsTotal' => $total,
			'truncated' => (count($listed) < ($total - count($unprocessable))),
			'cap' => $cap,
			'occurrencesTotal' => $occurrences,
			'erasableDocuments' => $erasable,
			'refusedDocuments' => $refused,
			'unprocessable' => $unprocessable,
			'unprocessableCount' => count($unprocessable),
		];
	}//end build()

	/**
	 * Apply an operator's exclusions to a preview.
	 *
	 * An exclusion without a reason is refused rather than silently kept: a
	 * voorkomen left standing is something the requester can ask about, and
	 * "somebody unticked it" is not an answer.
	 *
	 * @param array<int, array<string, mixed>> $exclusions The exclusions asked for.
	 *
	 * @return string[] The refusals, empty when every exclusion carries a reason.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/specs/anonymization-link/spec.md
	 */
	public function refuseExclusions(array $exclusions): array {
		$refusals = [];

		foreach ($exclusions as $exclusion) {
			if (trim((string)($exclusion['reason'] ?? '')) !== '') {
				continue;
			}

			$refusals[] = sprintf(
				'Leaving "%s" in place needs a reason. The person who asked to be removed can ask why it '
				.'is still there, and "somebody unticked it" is not an answer.',
				(string)($exclusion['occurrence'] ?? '(unnamed)')
			);
		}

		return $refusals;
	}//end refuseExclusions()
}//end class
