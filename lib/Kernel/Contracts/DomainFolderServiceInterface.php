<?php

/**
 * Contract of DomainFolderService
 *
 * The public surface of the DomainFolderService service, so callers in other
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
 * Contract of DomainFolderService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DomainFolderServiceInterface {
	/**
	 * Where a domain's folder lives.
	 */
	public const ROOT = 'Filinq';

	/**
	 * The key a domain carries to opt out of reconciliation.
	 */
	public const PIN_KEY = 'reconciliationPinnedReason';

	/**
	 * The folder matched what the domain declares.
	 */
	public const STATE_IN_STEP = 'inStep';

	/**
	 * The folder differed and was corrected.
	 */
	public const STATE_CORRECTED = 'corrected';

	/**
	 * The folder differed and could not be corrected.
	 */
	public const STATE_REFUSED = 'refused';

	/**
	 * The folder was left alone because somebody pinned it, with a reason.
	 */
	public const STATE_PINNED = 'pinned';

	/**
	 * The folder path a domain owns.
	 *
	 * @param array<string, mixed> $domain The domain.
	 *
	 * @return string The path.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function pathFor(array $domain): string;

	/**
	 * Create the domain's folder if it is not there yet.
	 *
	 * @param array<string, mixed> $domain The domain.
	 * @param string               $owner  The user whose storage holds it.
	 *
	 * @return array{created: bool, path: string, error: ?string} What happened.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function ensureFolder(array $domain, string $owner): array;

	/**
	 * Bring the folder's group access back to what the domain declares.
	 *
	 * @param array<string, mixed> $domain The domain, carrying `groups`.
	 * @param string               $owner  The user whose storage holds it.
	 *
	 * @return array{state: string, path: string, granted: array<int, string>, revoked: array<int, string>,
	 *               refused: array<int, array{group: string, action: string, reason: string}>,
	 *               pinnedReason: ?string} The outcome.
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity) Each branch here is one of the
	 * outcomes REQ-CDF-02 names: pinned, unreadable, granted, revoked, refused,
	 * already in step. Collapsing any two of them is exactly the "partly
	 * reconciled reported as reconciled" this method exists to refuse.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity) Same reason: the paths are the
	 * outcome matrix, not nesting that could be flattened.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function reconcile(array $domain, string $owner): array;
}//end interface
