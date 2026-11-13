<?php

/**
 * Registry Enrichment Queue
 *
 * Queues the base registry lookup of a stored intake document, so the
 * arrival returns before any registry is asked.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCA\Filinq\Kernel\JobQueueInterface;

/**
 * Queues one registry enrichment job per stored intake document with a file.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
 */
class RegistryEnrichmentQueue {

	/**
	 * Constructor.
	 *
	 * @param JobQueueInterface $jobs The background job queue.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly JobQueueInterface $jobs,
	) {

	}//end __construct()

	/**
	 * Queue the lookup for a stored document; one without a uuid or a file is left alone.
	 *
	 * @param array<string, mixed> $document The stored intake document.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
	 */
	public function queue(array $document): void {
		$uuid = (string)($document['uuid'] ?? '');
		$fileId = (int)($document['file'] ?? 0);
		if ($uuid === '' || $fileId <= 0) {
			return;
		}

		$this->jobs->add(JobQueueInterface::INTAKE_REGISTRY, ['uuid' => $uuid]);

	}//end queue()
}//end class
