<?php

/**
 * Anonymised outputs
 *
 * What an anonymised document is turned into: the anonymised email, the anonymised PDF and the attached summary of legal bases.
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

use OCA\Filinq\Service\AnonymisedPdfOutputService;
use OCA\Filinq\Service\EmlAnonymizationService;
use OCA\Filinq\Service\GrondslagenSummaryAttacher;

/**
 * What an anonymised document is turned into: the anonymised email, the anonymised PDF and the attached summary of legal bases.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class AnonymisedOutputs {

	/**
	 * Constructor.
	 *
	 * @param EmlAnonymizationService    $eml     Anonymises email documents
	 * @param AnonymisedPdfOutputService $pdf     Writes the anonymised PDF
	 * @param GrondslagenSummaryAttacher $summary Attaches the summary of legal bases
	 *
	 * @return void
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function __construct(
		public readonly EmlAnonymizationService $eml,
		public readonly AnonymisedPdfOutputService $pdf,
		public readonly GrondslagenSummaryAttacher $summary,
	) {

	}//end __construct()
}//end class
