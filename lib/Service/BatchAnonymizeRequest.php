<?php

/**
 * Batch Anonymize Request
 *
 * The entities and per-file options every file of a batch is anonymised with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * What every file of one batch is anonymised with.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */
final class BatchAnonymizeRequest {

	/**
	 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
	 * @param array<string, mixed> $callOptions Per-batch options (appendBasisSummary,
	 *                                          unredactedEntities, outputFormat, scope)
	 *                                          forwarded to each per-file call.
	 */
	public function __construct(
		public readonly array $entities,
		public readonly array $callOptions,
	) {
	}//end __construct()
}//end class
