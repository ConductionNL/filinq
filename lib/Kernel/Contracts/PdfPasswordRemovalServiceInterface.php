<?php

/**
 * Contract of PdfPasswordRemovalService
 *
 * The public surface of the PdfPasswordRemovalService service, so callers in other
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

use OCP\Files\File;

/**
 * Contract of PdfPasswordRemovalService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PdfPasswordRemovalServiceInterface {
	/**
	 * The copy was written.
	 */
	public const UNLOCKED = 'unlocked';

	/**
	 * The password is not correct (422).
	 */
	public const WRONG_PASSWORD = 'wrong-password';

	/**
	 * The document is final (409).
	 */
	public const FINAL = 'final';

	/**
	 * qpdf is not installed (503).
	 */
	public const NO_TOOL = 'no-tool';

	/**
	 * The file is not a PDF, or could not be read or written (400 / 500).
	 */
	public const FAILED = 'failed';

	/**
	 * Unlock a PDF the caller may read into a copy beside it, and check the copy.
	 *
	 * @param File   $file     The locked PDF, already proven readable by the caller.
	 * @param string $password The password the clerk typed.
	 *
	 * @return array<string, mixed> `outcome` (one of the constants), with `message` on a refusal, or the
	 *                              copy's `fileId`, `name`, `validationStatus` and `validationFindings`.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public function unlock(File $file, string $password): array;
}//end interface
