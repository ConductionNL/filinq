<?php

/**
 * Anonymisation records
 *
 * How an anonymisation run is checked and recorded: the replacement check, the persistence and the run records.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\AnonymisationRunRecords;
use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\ReplacementVerificationService;

/**
 * How an anonymisation run is checked and recorded: the replacement check, the persistence and the run records.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class AnonymisationRecords {

	/**
	 * Constructor.
	 *
	 * @param ReplacementVerificationService  $verifier    Checks every replacement landed
	 * @param AnonymizationPersistenceService $persistence Stores the anonymisation result
	 * @param AnonymisationRunRecords         $runs        Records each run
	 *
	 * @return void
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function __construct(
		public readonly ReplacementVerificationService $verifier,
		public readonly AnonymizationPersistenceService $persistence,
		public readonly AnonymisationRunRecords $runs,
	) {

	}//end __construct()
}//end class
