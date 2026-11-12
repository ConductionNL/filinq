<?php

/**
 * Prohibition Match Run
 *
 * What one pass of {@see ProhibitionGateService} over the detected entities
 * holds for every entity: the policy service it asks and the fail mode read
 * once before the pass.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The policy service and fail mode of one pass over the detected entities.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 */
final class ProhibitionMatchRun {

	/**
	 * The resolved PolicyMatchService.
	 *
	 * @var mixed
	 */
	public mixed $policyService = null;

	/**
	 * Whether a failing policy lookup refuses the run instead of skipping the entity.
	 *
	 * @var bool
	 */
	public bool $failClosed = false;
}//end class
