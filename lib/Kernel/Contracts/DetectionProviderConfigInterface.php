<?php

/**
 * Contract of DetectionProviderConfig
 *
 * The public surface of DetectionProviderConfig that other components call, so they depend
 * on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction b.v. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://github.com/ConductionNL/filinq
 *
 * @spec openspec/specs/llm-entity-detection-provider/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use InvalidArgumentException;

/**
 * Contract of DetectionProviderConfig.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction b.v. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/filinq
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DetectionProviderConfigInterface {

	/**
	 * The settings as the admin view reads them.
	 *
	 * @return array<string, mixed> providers, endpoint, agentflow, requireLocal, defaultConfidence,
	 *                              timeout and the available provider ids.
	 *
	 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function toArray(): array;

	/**
	 * Apply an update from the admin view. Only the keys present are changed.
	 *
	 * `organisation` + `provider` set one organisation's choice; choosing
	 * `default` removes the organisation from the map, so the map only holds
	 * opt-ins. Everything is validated before anything is written.
	 *
	 * @param array<string, mixed> $data The submitted fields.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a field is invalid; nothing is written then.
	 *
	 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function update(array $data): void;
}//end interface
