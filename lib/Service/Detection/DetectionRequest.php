<?php

/**
 * Detection Request
 *
 * One detection run asked for: the file, the entity types enabled for it, and whether it is a re-analysis.
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
 * The file a detection runs over, the entity types it looks for, and whether
 * it is a re-analysis that may replace earlier LLM relations.
 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.1
 */
final class DetectionRequest {

	/**
	 * Constructor.
	 *
	 * @param int                     $fileId      The Nextcloud file.
	 * @param array<int, string>|null $entityTypes The enabled entity types, or null for all.
	 * @param bool                    $force       A re-analysis. Without it, a file that already has
	 *                                             LLM relations keeps them, with the operator's
	 *                                             decisions on them.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.1
	 */
	public function __construct(
		public readonly int $fileId,
		public readonly ?array $entityTypes=null,
		public readonly bool $force=false,
	) {
	}//end __construct()
}//end class
