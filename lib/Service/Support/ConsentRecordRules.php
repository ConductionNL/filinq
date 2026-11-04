<?php

/**
 * Consent record rules
 *
 * The rules a publication consent record is built and checked by: the
 * objection deadline, the notes on additional publication bases, the scope
 * contract and the policy referent check.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\ConsentNotesHelper;
use OCA\Filinq\Service\ConsentPolicyReferentValidator;
use OCA\Filinq\Service\ConsentScopeValidator;
use OCA\Filinq\Service\ObjectionDeadlineChecker;

/**
 * The rules a publication consent record is built and checked by.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 */
final class ConsentRecordRules {

	/**
	 * Constructor.
	 *
	 * @param ObjectionDeadlineChecker       $deadlineChecker   Calculates the objection deadline of a new record
	 * @param ConsentNotesHelper             $notesHelper       Writes the additional publication bases into the notes
	 * @param ConsentScopeValidator          $scopeValidator    Checks a new record against the scope contract
	 * @param ConsentPolicyReferentValidator $referentValidator Checks the scope and policy referent of submitted data
	 *
	 * @return void
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function __construct(
		public readonly ObjectionDeadlineChecker $deadlineChecker,
		public readonly ConsentNotesHelper $notesHelper,
		public readonly ConsentScopeValidator $scopeValidator,
		public readonly ConsentPolicyReferentValidator $referentValidator,
	) {

	}//end __construct()
}//end class
