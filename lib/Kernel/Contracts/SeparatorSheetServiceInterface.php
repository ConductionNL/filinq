<?php

/**
 * Contract of SeparatorSheetService
 *
 * The public surface of the SeparatorSheetService service, so callers in other
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

use RuntimeException;

/**
 * Contract of SeparatorSheetService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SeparatorSheetServiceInterface {
	/**
	 * The payload every separator sheet carries.
	 *
	 * Versioned on purpose: a sheet printed today is still in a drawer next
	 * year, and a splitter that changed its mind about the format would cut
	 * batches in the wrong place rather than refuse to.
	 *
	 * @var string
	 */
	public const PAYLOAD_PREFIX = 'filinq:sep:v1:';

	/**
	 * The payload of a separator that names a paper original of a
	 * digitisation project: `filinq:sep:v2:<profileId>:po:<originalId>`.
	 * The v1 payload stays valid beside it.
	 *
	 * @var string
	 */
	public const PAYLOAD_PREFIX_V2 = 'filinq:sep:v2:';

	/**
	 * What readPayload() puts before the original's id for a v2 sheet, so
	 * the splitter can tell an original from a case number.
	 *
	 * @var string
	 */
	public const ORIGINAL_MARKER = 'po:';

	/**
	 * The payload one sheet carries.
	 *
	 * @param string $profileId The scan profile the sheet belongs to.
	 * @param string $caseNumber The case the documents after it belong to, or an empty string.
	 *
	 * @return string The payload.
	 *
	 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function payload(string $profileId, string $caseNumber = ''): string;

	/**
	 * The case number a payload names, if it names one.
	 *
	 * @param string $payload The payload read off a page.
	 *
	 * @return string|null The case number, `po:<originalId>` for a sheet naming a paper original,
	 *                     an empty string when the sheet named none, or null when this is not a separator.
	 *
	 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function readPayload(string $payload): ?string;

	/**
	 * The payload of a sheet naming one paper original.
	 *
	 * @param string $profileId  The scan profile the sheet belongs to.
	 * @param string $originalId The paperOriginal the pages after it are the scan of.
	 *
	 * @return string The payload.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function originalPayload(string $profileId, string $originalId): string;

	/**
	 * Render one separator sheet per paper original, with its shelf mark and
	 * description in print so a person can check the sheet before it goes
	 * into the scanner.
	 *
	 * @param string                           $profileId The scan profile the sheets belong to.
	 * @param array<int, array<string, mixed>> $originals The paperOriginal records, each with `id`, `shelfMark` and `description`.
	 *
	 * @return string The sheets as PDF bytes.
	 *
	 * @throws RuntimeException When there is no original to print or the sheets do not render.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function renderOriginals(string $profileId, array $originals): string;

	/**
	 * Render separator sheets, one page per case number.
	 *
	 * A print with no case numbers still produces ONE sheet: a separator that
	 * names no case is a perfectly ordinary thing to put between two documents
	 * that both still have to be sorted by hand.
	 *
	 * @param string $profileId The scan profile the sheets belong to.
	 * @param array<int, string> $caseNumbers The cases to print a sheet for.
	 *
	 * @return string The sheets as PDF bytes.
	 *
	 * @throws RuntimeException When the sheets do not render.
	 *
	 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function render(string $profileId, array $caseNumbers = []): string;
}//end interface
