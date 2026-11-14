<?php

/**
 * Bulk Job Status
 *
 * The status a bulk document generation job is stored with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Builds the stored status of a bulk generation job from its job argument.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
 */
final class BulkJobStatus {

	/**
	 * The status a bulk job is stored with before it runs.
	 *
	 * @param array<string, mixed> $argument The job argument: jobId, templateId, objectIds and options.
	 *
	 * @return array<string, mixed> The queued status.
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
	 */
	public function queued(array $argument): array {
		return [
			'jobId' => $argument['jobId'],
			'status' => 'queued',
			'total' => count($argument['objectIds']),
			'completed' => 0,
			'errors' => 0,
			'results' => [],
			'options' => $argument['options'],
		];

	}//end queued()
}//end class
