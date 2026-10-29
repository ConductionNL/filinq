<?php

/**
 * Contract of IntakeReadingProgress
 *
 * The public surface of the IntakeReadingProgress service, so callers in other
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

/**
 * Contract of IntakeReadingProgress.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakeReadingProgressInterface {
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
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function progressFor(string $state, string $error = '', string $moment = ''): array;

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
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function showsInInbox(array $document): bool;

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
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function summarise(array $documents): array;
}//end interface
