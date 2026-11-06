<?php

/**
 * Contract of FinalDocumentUnfreezeService
 *
 * The public surface of the FinalDocumentUnfreezeService service, so callers in other
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
 * Contract of FinalDocumentUnfreezeService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FinalDocumentUnfreezeServiceInterface {
	/**
	 * The named right that unfreezing needs.
	 *
	 * One of the `filinq-*` role groups (`filinq-policy-admins`,
	 * `filinq-signing-admins`); RenameRoleGroups carries the members of the
	 * old `docudesk-*` group over.
	 *
	 * @var string
	 */
	public const UNFREEZE_GROUP = 'filinq-final-document-admins';

	/**
	 * Unfreeze a final version, with a reason, leaving a permanent mark.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $reason Why the version is being unfrozen.
	 *
	 * @return array<string, mixed> The unfrozen record.
	 *
	 * @throws RuntimeException When the caller lacks the right, gives no reason, or the version is not final.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function unfreeze(int $fileId, string $reason): array;

	/**
	 * Refuse an attempt to clear the mark.
	 *
	 * The mark is what makes the exception honest. A product whose scar can be
	 * rubbed out has the silent exception this one was written to avoid, so
	 * clearing it is refused rather than quietly ignored.
	 *
	 * @param array<string, mixed> $incoming The record a caller wants to write.
	 * @param array<string, mixed> $stored The record as it stands.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the incoming record would clear the mark.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function refuseClearingTheMark(array $incoming, array $stored): void;

	/**
	 * Whether the acting user holds the named right.
	 *
	 * @return bool True when the acting user may unfreeze.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function mayUnfreeze(): bool;
}//end interface
