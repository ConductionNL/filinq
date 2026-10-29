<?php

/**
 * Contract of FinalDocumentCorrectionService
 *
 * The public surface of the FinalDocumentCorrectionService service, so callers in other
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
 * Contract of FinalDocumentCorrectionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FinalDocumentCorrectionServiceInterface {
	/**
	 * Correct a final document by superseding it.
	 *
	 * The frozen file is copied to a sibling, the copy gets a draft record
	 * naming the record it supersedes, and the frozen record is pointed back at
	 * the copy. A reader of either end sees the chain.
	 *
	 * The frozen file is never opened for writing, which is why this is safe to
	 * offer on a document the guard refuses: the correction is a different file.
	 *
	 * @param int $fileId The Nextcloud file id of the final document.
	 * @param string $reason Why the correction was issued.
	 *
	 * @return array<string, mixed> `{fileId, name, path, version, supersedes}` of the correction.
	 *
	 * @throws RuntimeException When the document is not final, or the copy cannot be written.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function correct(int $fileId, string $reason): array;

	/**
	 * The chain a version belongs to, read from either end.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> `{version, supersedes, supersededBy}`, each null when absent.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function chainFor(int $fileId): array;
}//end interface
