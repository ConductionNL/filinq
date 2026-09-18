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
