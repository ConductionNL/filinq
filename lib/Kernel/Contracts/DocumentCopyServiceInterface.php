<?php

/**
 * Contract of DocumentCopyService
 *
 * The public surface of the DocumentCopyService service, so callers in other
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

use OCA\Filinq\Exception\DomainLinkRefusedException;
use RuntimeException;

/**
 * Contract of DocumentCopyService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentCopyServiceInterface {
	/**
	 * Copy one file of a record to another record.
	 *
	 * @param int                  $fileId The file on the source record.
	 * @param array<string, mixed> $source The source record, as register, schema and id.
	 * @param array<string, mixed> $target The target record, as register, schema and id.
	 *
	 * @return array<string, mixed> The copy's file id and name, and its document record.
	 *
	 * @throws DomainLinkRefusedException When the caller may not read the source or write the target.
	 * @throws RuntimeException When the request is incomplete or the target has no folder to write to.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
	 */
	public function copy(int $fileId, array $source, array $target): array;
}//end interface
