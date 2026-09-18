<?php

/**
 * What the inbox says about a document it is still reading, and about one it could not.
 *
 * 🔴 THE INBOX MUST SHOW WHAT IT IS STILL READING RATHER THAN HIDING IT. A
 * document whose classification has not finished is not absent, and listing
 * only the finished ones is how an inbox reports itself empty while work sits
 * in a queue.
 *
 * 🔴 AND A READING THAT FAILED IS NOT A READING THAT IS STILL GOING. This is
 * the same failure the folder watch had: a background job has no response to
 * return, so a job that throws leaves the record saying `reading` forever. On
 * screen that is indistinguishable from a slow OCR, and the longer it sits the
 * more it looks like patience rather than breakage.
 *
 * So `failed` is a state of its own, it carries what went wrong, and the inbox
 * counts it apart. A document that could not be read is still IN the inbox and
 * still assignable by hand: the text is what failed, not the document.
 *
 * ⚠️ WHAT THIS CANNOT DO, STATED RATHER THAN IMPLIED. Nothing here notifies
 * anybody. The failure is recorded on the intake record and surfaced in the
 * inbox's own counts, which reaches a person who OPENS the inbox and nobody who
 * does not. That is a real limit: a queue that fails at 02:00 on a Saturday is
 * seen on Monday. Making it reach somebody who is not looking needs the
 * notification dialect (ADR-031) on `intakeDocument`, which is a separate
 * change, named rather than quietly assumed.
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
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

/**
 * The reading states an intake document moves through, and what the inbox shows.
 */
class IntakeReadingProgress {

	/**
	 * Nothing has been attempted yet.
	 *
	 * @var string
	 */
	public const QUEUED = 'queued';

	/**
	 * A job is reading it now.
	 *
	 * @var string
	 */
	public const READING = 'reading';

	/**
	 * The text was recognised and written to the searchable content.
	 *
	 * @var string
	 */
	public const READ = 'read';

	/**
	 * The reading failed, and says why.
	 *
	 * @var string
	 */
	public const FAILED = 'failed';

	/**
	 * Every state, so an inbox can account for each document exactly once.
	 *
	 * @var string[]
	 */
	public const STATES = [self::QUEUED, self::READING, self::READ, self::FAILED];

	/**
	 * The progress to record when a reading step ends.
	 *
	 * @param string $state  The state reached.
	 * @param string $error  What went wrong, when it did.
	 * @param string $moment When, as an ATOM timestamp.
	 *
	 * @return array<string, mixed> The progress to store on the intake record.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function progressFor(string $state, string $error = '', string $moment = ''): array {
		$reached = $state;
		$why = $error;

		if (in_array($reached, self::STATES, true) === false) {
			// An unknown state is not `read`. Treating it as finished is how a
			// document with no text becomes searchable by nothing while the
			// inbox reports it done.
			$reached = self::FAILED;
			if ($why === '') {
				$why = 'the reading reported a state nobody recognises';
			}
		}

		$progress = ['readingState' => $reached, 'readingUpdatedAt' => $moment];

		if ($reached !== self::FAILED) {
			$progress['readingError'] = null;

			return $progress;
		}

		// 🔴 A FAILURE WITH NO WORDS IS A FAILURE NOBODY CAN ACT ON, and the
		// person who meets it is a registrar, not the developer who wrote the
		// job.
		if ($why === '') {
			$why = 'the reading failed and recorded no reason, which itself needs looking at';
		}

		$progress['readingError'] = $why;

		return $progress;
	}//end progressFor()

	/**
	 * Whether this document should still appear in the inbox.
	 *
	 * Always. A document being read, and one that could not be read, both stay:
	 * the text is what failed, not the document, and it remains assignable by
	 * hand. The method exists so the rule is assertable rather than implicit in
	 * whatever a query happens to filter on.
	 *
	 * @param array<string, mixed> $document The intake document.
	 *
	 * @return bool True, always.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function showsInInbox(array $document): bool {
		unset($document);

		return true;
	}//end showsInInbox()

	/**
	 * What the inbox says it is doing, counted by state.
	 *
	 * 🔴 STILL-READING AND COULD-NOT-BE-READ ARE COUNTED APART. Folding them
	 * together produces "3 being read" over one queue and two breakages, and
	 * the two are the ones somebody has to do something about.
	 *
	 * @param array<int, array<string, mixed>> $documents The inbox contents.
	 *
	 * @return array<string, mixed> The counts and what they mean.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function summarise(array $documents): array {
		$counts = array_fill_keys(self::STATES, 0);
		$failures = [];

		foreach ($documents as $document) {
			$state = (string)($document['readingState'] ?? self::QUEUED);
			if (isset($counts[$state]) === false) {
				$state = self::FAILED;
			}

			$counts[$state]++;

			if ($state !== self::FAILED) {
				continue;
			}

			$failures[] = [
				'document' => (string)($document['id'] ?? ''),
				'error' => (string)($document['readingError'] ?? 'no reason recorded'),
			];
		}

		return [
			'counts' => $counts,
			'stillReading' => ($counts[self::QUEUED] + $counts[self::READING]),
			'couldNotBeRead' => $counts[self::FAILED],
			'failures' => $failures,
			'needsAPerson' => ($failures !== []),
			// Every document lands in exactly one state, so the counts cannot
			// quietly omit one.
			'accountedFor' => array_sum($counts),
		];
	}//end summarise()
}//end class
