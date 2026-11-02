<?php

/**
 * Contract terms
 *
 * What a contract endpoint reads besides the contract: the term suggestions and the parties.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/contract-lifecycle-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\ContractPartiesInterface;
use OCA\Filinq\Kernel\Contracts\ContractTermSuggestionServiceInterface;

/**
 * What a contract endpoint reads besides the contract: the term suggestions and the parties.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/contract-lifecycle-management/spec.md
 */
final class ContractTerms {

	/**
	 * Constructor.
	 *
	 * @param ContractTermSuggestionServiceInterface $suggestions Suggests contract terms
	 * @param ContractPartiesInterface               $parties     The contract parties
	 *
	 * @return void
	 *
	 * @spec openspec/specs/contract-lifecycle-management/spec.md
	 */
	public function __construct(
		public readonly ContractTermSuggestionServiceInterface $suggestions,
		public readonly ContractPartiesInterface $parties,
	) {

	}//end __construct()
}//end class
