<?php

/**
 * Contract not found
 *
 * Thrown when a contract does not exist or the caller cannot read it; the
 * two are answered the same, with a 404.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use RuntimeException;

/**
 * A contract the caller cannot reach.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
 */
class ContractNotFoundException extends RuntimeException {
}//end class
