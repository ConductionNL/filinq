<?php

/**
 * Prohibition Skip Tier
 *
 * The pure tier rule behind every skip attempt on a prohibition-matched
 * entity. Kept as its own collaborator so the single implementation is shared
 * by the public helper on AnonymizationService and by the guard that enforces
 * it on the review UI's skip endpoint.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Classifies a skip attempt on a prohibition-matched entity.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
 */
class ProhibitionSkipTier {
	/**
	 * Classify a skip attempt on a prohibition-matched entity.
	 *
	 * Pure tier logic (callers have already established it is a skip AND a
	 * prohibition match): at or above the threshold the match is absolute and
	 * cannot be released; below the threshold it is releasable only with force.
	 *
	 * @param float $confidence Detection confidence for the occurrence.
	 * @param float $threshold High-confidence threshold in effect.
	 * @param bool $force Whether the request set force.
	 *
	 * @return string One of 'block_absolute', 'block_releasable', 'allow'.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
	 */
	public function classify(float $confidence, float $threshold, bool $force): string {
		if ($confidence >= $threshold) {
			return 'block_absolute';
		}

		if ($force === false) {
			return 'block_releasable';
		}

		return 'allow';
	}//end classify()
}//end class
