<?php

/**
 * Agentflow Target
 *
 * Where and how long the LLM provider's agentflow is called.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

/**
 * The agentflow-run endpoint, the agentflow on it, and the seconds a call may take.
 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
 */
final class AgentflowTarget {

	/**
	 * Constructor.
	 *
	 * @param string $endpoint  The agentflow-run URL.
	 * @param string $agentflow The agentflow reference.
	 * @param int    $timeout   Seconds before the call is abandoned.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function __construct(
		public readonly string $endpoint,
		public readonly string $agentflow,
		public readonly int $timeout,
	) {
	}//end __construct()
}//end class
