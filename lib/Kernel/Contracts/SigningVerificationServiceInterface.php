<?php

/**
 * Contract of SigningVerificationService
 *
 * The public surface of the SigningVerificationService service, so callers in other
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
 * Contract of SigningVerificationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningVerificationServiceInterface {
	/**
	 * Verify all signatures in a document
	 *
	 * @param int $fileId The Nextcloud file ID
	 * @param string $userId The user ID requesting verification
	 *
	 * @return array<string, mixed> Verification result
	 *
	 * @throws RuntimeException If file cannot be accessed
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#5-1
	 */
	public function verifyDocument(int $fileId, string $userId): array;
}//end interface
