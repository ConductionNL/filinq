<?php

/**
 * Reads the post register: what answered what, and where the gaps came from.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/post-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The post register's two derived answers.
 *
 * 🔴 THE DISCHARGE IS READ, NEVER WRITTEN. An inbound entry carries no
 * `answered` flag and never will: a flag can be set by anybody, at any time,
 * without an answer existing, and then the register reports a letter as dealt
 * with because somebody ticked a box. Reading it from the outbound entry that
 * NAMES the inbound one means the register can only claim a discharge that has
 * a document behind it.
 *
 * 🔑 THE SEARCH CONTRACT WAS CHECKED, NOT ASSUMED. OpenRegister's objects
 * search takes BARE property keys beside a `@self` block naming the register and
 * schema — `['@self' => [...], 'answers' => $uuid]` — and the sibling
 * aggregations endpoint spells the same filter the opposite way. A `filter[...]`
 * wrapper here would be read as the empty set and this method would report every
 * inbound entry as undischarged, confidently and silently.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/post-register/spec.md
 */
class PostRegisterReader {

	/**
	 * The register the entries live in.
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema the entries live in.
	 */
	public const SCHEMA = 'documentRegistration';

	/**
	 * The direction of a document that came in.
	 */
	public const DIRECTION_INCOMING = 'incoming';

	/**
	 * Collaborators.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Structured logger.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The outbound registrations that answer an inbound one.
	 *
	 * 🔑 IT RETURNS THE ANSWERS, NOT A BOOLEAN. "Discharged: yes" loses which
	 * document did it, and that is the thing somebody reading the register a
	 * year later actually wants. A caller that only needs the boolean can ask
	 * whether the list is empty; a caller given a boolean cannot get the list
	 * back.
	 *
	 * @param string $inboundUuid The inbound registration.
	 *
	 * @return array<int, array<string, mixed>> The outbound entries naming it, possibly empty.
	 */
	public function answersFor(string $inboundUuid): array {
		$uuid = trim($inboundUuid);
		if ($uuid === '') {
			return [];
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => self::REGISTER,
						'schema' => self::SCHEMA,
					],
					'answers' => $uuid,
				]
			);
		} catch (Throwable $e) {
			// 🔴 A FAILED READ IS NOT "NO ANSWERS". Reporting an empty list here
			// would tell the reader the letter is still open when it may have
			// been answered a month ago, which is the reading that gets a
			// second reply sent. The failure is raised so the caller can say it
			// could not tell, rather than say something false.
			$this->logger->warning(
				'filinq.post-register.discharge-read-failed',
				['inbound' => $uuid, 'error' => $e->getMessage()]
			);

			throw $e;
		}

		return (is_array($results) === true ? array_values($results) : []);
	}//end answersFor()

	/**
	 * The undischarged inbound entries of a unit, oldest first.
	 *
	 * 🔴 IT IS BUILT FROM THE SAME READ AS THE DISCHARGE, NOT FROM A FLAG.
	 * REQ-DIO-02 says the discharge is read from the link and never written as a
	 * status, so "open" cannot be a stored field either: an entry is open when
	 * nothing names it, which is a question asked of the outbound entries. A
	 * cached `open` column would be the same lie as an `answered` flag, one step
	 * further away from where anybody would look for it.
	 *
	 * 🔴 AN ENTRY WHOSE DISCHARGE COULD NOT BE READ IS RAISED, NOT LISTED AS
	 * OPEN. Listing it would put a letter somebody answered a month ago at the
	 * top of the work list, oldest first, and the handler would answer it again.
	 * Leaving it out silently is worse still: a letter nobody answered would
	 * vanish from the only list that would have caught it.
	 *
	 * 🔑 OLDEST FIRST IS THE POINT OF THE LIST. It is a work list, and the order
	 * is what makes it one. An entry with no registration date sorts LAST rather
	 * than first: an unknown date is not evidence of age, and sorting it first
	 * would put it above letters that really have been waiting.
	 *
	 * @param string $unit The organisational unit.
	 *
	 * @return array<int, array<string, mixed>> The undischarged inbound entries, oldest first.
	 *
	 * @throws Throwable When the register could not be read.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function openPostFor(string $unit): array {
		$unitId = trim($unit);
		if ($unitId === '') {
			return [];
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => self::REGISTER,
						'schema' => self::SCHEMA,
					],
					// BARE keys, beside the `@self` block. The sibling
					// aggregations endpoint spells the same filter as
					// `filter[unit]`, and the objects endpoint reads that
					// wrapper as the EMPTY SET: written that way this method
					// would report a unit with no post at all, confidently and
					// with nothing in the log.
					'unit' => $unitId,
					'direction' => self::DIRECTION_INCOMING,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'filinq.post-register.open-post-read-failed',
				['unit' => $unitId, 'error' => $e->getMessage()]
			);

			throw $e;
		}

		$open = [];
		foreach ((array)$results as $entry) {
			$entry = $this->plain(row: $entry);
			if ($entry === null) {
				continue;
			}

			$uuid = trim((string)($entry['id'] ?? $entry['uuid'] ?? ''));
			if ($uuid === '') {
				continue;
			}

			// answersFor() raises rather than reporting "no answers" on a failed
			// read, and that raise is deliberately not caught here.
			if ($this->answersFor(inboundUuid: $uuid) !== []) {
				continue;
			}

			$open[] = $entry;
		}

		usort($open, [$this, 'oldestFirst']);

		return $open;
	}//end openPostFor()

	/**
	 * Order two entries oldest first, with an undated entry last.
	 *
	 * @param array<string, mixed> $left  One entry.
	 * @param array<string, mixed> $right The other.
	 *
	 * @return int The comparison.
	 *
	 * @spec exclude Comparison helper; the ordering rule it implements is documented on openPostFor().
	 */
	private function oldestFirst(array $left, array $right): int {
		$a = trim((string)($left['registeredAt'] ?? ''));
		$b = trim((string)($right['registeredAt'] ?? ''));

		if ($a === '' && $b === '') {
			return 0;
		}

		if ($a === '') {
			return 1;
		}

		if ($b === '') {
			return -1;
		}

		return strcmp($a, $b);
	}//end oldestFirst()

	/**
	 * One search result as a plain array.
	 *
	 * @param mixed $row The row as the object service returned it.
	 *
	 * @return array<string, mixed>|null The entry.
	 *
	 * @spec exclude Shape adapter over a search result; no behaviour of its own.
	 */
	private function plain(mixed $row): ?array {
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$identifier = trim((string)($row['id'] ?? $row['uuid'] ?? ''));

		if (isset($row['object']) === true && is_array($row['object']) === true) {
			$object = $row['object'];
			if ($identifier === '') {
				$identifier = trim((string)($object['id'] ?? $object['uuid'] ?? ''));
			}

			$row = $object;
		}

		if ($identifier !== '') {
			$row['id'] = $identifier;
		}

		return $row;
	}//end plain()

	/**
	 * Whether a number in the series was withdrawn, and why.
	 *
	 * 🔑 A GAP WITH A REASON IS A DECISION; A GAP WITHOUT ONE READS AS A LOST
	 * DOCUMENT. That is the whole point of recording the withdrawal, so a
	 * withdrawal carrying a reason and no moment, or a moment and no reason, is
	 * reported as INCOMPLETE rather than quietly treated as either.
	 *
	 * @param array<string, mixed> $registration The registration.
	 *
	 * @return array{withdrawn: bool, complete: bool, reason: string, at: string} The withdrawal state.
	 */
	public function withdrawalOf(array $registration): array {
		$reason = trim((string)($registration['withdrawnReason'] ?? ''));
		$at = trim((string)($registration['withdrawnAt'] ?? ''));

		if ($reason === '' && $at === '') {
			return ['withdrawn' => false, 'complete' => true, 'reason' => '', 'at' => ''];
		}

		return [
			'withdrawn' => true,
			'complete' => ($reason !== '' && $at !== ''),
			'reason' => $reason,
			'at' => $at,
		];
	}//end withdrawalOf()

	/**
	 * The series, in number order, with every gap accounted for.
	 *
	 * 🔴 THE POINT IS THAT IT READS END TO END. A numbered series with an
	 * unexplained hole is the failure this whole requirement exists to prevent:
	 * an auditor cannot tell a withdrawn allocation from a lost document, and
	 * the register cannot tell them either. So every entry is returned in order
	 * with its withdrawal state attached, and an entry whose withdrawal is
	 * half-recorded is marked rather than smoothed over.
	 *
	 * @param array<int, array<string, mixed>> $registrations The entries.
	 *
	 * @return array<int, array<string, mixed>> The series, ordered by number.
	 */
	public function series(array $registrations): array {
		$rows = [];

		foreach ($registrations as $registration) {
			if (is_array($registration) === false) {
				continue;
			}

			$number = trim((string)($registration['registrationNumber'] ?? ''));
			if ($number === '') {
				// An entry with no number is not in the series yet. Including it
				// would put a row with no position among rows ordered by
				// position.
				continue;
			}

			$rows[] = [
				'registrationNumber' => $number,
				'direction' => (string)($registration['direction'] ?? ''),
				'withdrawal' => $this->withdrawalOf(registration: $registration),
			];
		}

		usort(
			$rows,
			static fn (array $a, array $b): int => strcmp($a['registrationNumber'], $b['registrationNumber'])
		);

		return $rows;
	}//end series()
}//end class
