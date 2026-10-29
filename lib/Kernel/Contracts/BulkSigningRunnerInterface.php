<?php

/**
 * Contract of BulkSigningRunner
 *
 * The public surface of the BulkSigningRunner service, so callers in other
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
 * Contract of BulkSigningRunner.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BulkSigningRunnerInterface {
	/**
	 * Create the requests of a confirmed batch.
	 *
	 * @param string $batchId The batch uuid
	 * @param string $userId  The initiator
	 *
	 * @return array The batch as it ended, or [] when it could not be read
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function run(string $batchId, string $userId): array;
}//end interface
