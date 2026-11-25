<?php

/**
 * Contract of WaitingWorkService
 *
 * The public surface of the WaitingWorkService service, so callers in other
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
 * Contract of WaitingWorkService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface WaitingWorkServiceInterface {
	/**
	 * Everything that waits for this person, signatures first, then intake, then reviews.
	 *
	 * A source that cannot be read is left out and logged, so the other two
	 * still show.
	 *
	 * @param string $userId The person.
	 *
	 * @return list<array{kind: string, id: string, name: string, reason: string, since: string}> The waiting items.
	 *
	 * @throws RuntimeException When there is no person.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
	 */
	public function listFor(string $userId): array;
}//end interface
