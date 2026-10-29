<?php

/**
 * Contract of PostRegisterReader
 *
 * The public surface of the PostRegisterReader service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use Throwable;

/**
 * Contract of PostRegisterReader.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PostRegisterReaderInterface {
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
	 *
	 * 🔴 THE VALUE IS THE SCHEMA'S, NOT THE PROSE'S. `documentRegistration`
	 * declares `enum: [inbound, outbound]`, and OpenRegister refuses anything
	 * else on save, so no row can ever carry `incoming`. Filtering on the word
	 * the proposal uses returned an empty open-post list on every unit, with
	 * no error and nothing in the log to say the filter matched nothing.
	 */
	public const DIRECTION_INBOUND = 'inbound';

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
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function answersFor(string $inboundUuid): array;

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
	public function openPostFor(string $unit): array;

	/**
	 * A unit's series, read from the register and accounted for end to end.
	 *
	 * 🔑 THE FETCH LIVES BESIDE `series()` RATHER THAN IN THE CALLER. A
	 * controller that built this query itself would be a second place the
	 * objects endpoint's bare-key spelling has to be got right, and the wrong
	 * spelling there answers the EMPTY SET rather than an error: the series
	 * would read as a unit with no post at all.
	 *
	 * @param string $unit The organisational unit.
	 *
	 * @return array<int, array<string, mixed>> The series, ordered by number.
	 *
	 * @throws Throwable When the register could not be read.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function seriesFor(string $unit): array;

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
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function withdrawalOf(array $registration): array;

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
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function series(array $registrations): array;
}//end interface
