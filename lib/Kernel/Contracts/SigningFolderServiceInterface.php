<?php

/**
 * Contract of SigningFolderService
 *
 * The public surface of the SigningFolderService service, so callers in other
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
 * Contract of SigningFolderService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningFolderServiceInterface {
	/**
	 * Everything pending for one signer, in one folder.
	 *
	 * Ordered by deadline first and age second. A document with no deadline
	 * sorts after every document that has one: an undated request is not
	 * urgent, and putting it first would push the dated ones off the screen.
	 *
	 * @param string $userId The signer asking.
	 * @param int $limit Page size (1..200).
	 * @param int $offset Page offset.
	 *
	 * @return array{total: int, limit: int, offset: int, entries: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When there is no authenticated user.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function folder(string $userId, int $limit = 50, int $offset = 0): array;

	/**
	 * Sign a selection from the folder, one pass, one record per document.
	 *
	 * Every document goes through SigningService::sign(), so each gets its own
	 * signature, artifact and audit entry, and meets the same level floors and
	 * honest-completion gates it would meet as a single request. A refusal is
	 * reported against its own document and the pass carries on.
	 *
	 * The pass is resumable because it holds no state of its own: a document
	 * already signed no longer has a pending signer record, so a second pass
	 * over the same selection reports it as settled and signs the rest. No
	 * document is ever left half signed, because a document is one sign()
	 * call and that call either completes or throws.
	 *
	 * @param array<int, string> $requestIds The selected signing requests.
	 * @param string $userId The signer asking.
	 *
	 * @return array{signed: int, refused: int, results: list<array<string, mixed>>}
	 *
	 * @throws RuntimeException When there is no authenticated user.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function signSelection(array $requestIds, string $userId): array;
}//end interface
