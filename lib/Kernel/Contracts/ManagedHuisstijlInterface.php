<?php

/**
 * Contract of HuisstijlProfileMapper's managed-house-style test
 *
 * The part of the house style mapping that callers in other components need,
 * so they depend on this contract instead of the implementation.
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
 * Contract of HuisstijlProfileMapper's managed-house-style test.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ManagedHuisstijlInterface {
	/**
	 * The slug of the house style schema.
	 */
	public const SCHEMA = 'huisstijl';

	/**
	 * The `managedBy` value of a house style that follows thematiq.
	 */
	public const MANAGED_BY = 'thematiq';

	/**
	 * Whether a house style is managed by thematiq.
	 *
	 * @param array<string, mixed> $huisstijl The house style.
	 *
	 * @return bool Whether it is managed.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
	 */
	public function isManaged(array $huisstijl): bool;
}//end interface
