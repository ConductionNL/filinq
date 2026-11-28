<?php

/**
 * Contract of HuisstijlWriteGate
 *
 * The public surface of the HuisstijlWriteGate service, so callers in other
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
 * Contract of HuisstijlWriteGate.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface HuisstijlWriteGateInterface {
	/**
	 * Run a write with the gate open, and shut it again whatever happens.
	 *
	 * @param callable $write The write.
	 *
	 * @return mixed What the write returns.
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/tasks.md#task-2.2
	 */
	public function during(callable $write): mixed;

	/**
	 * Whether a sync write is in progress.
	 *
	 * @return bool Whether the gate is open.
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/tasks.md#task-2.2
	 */
	public function isOpen(): bool;
}//end interface
