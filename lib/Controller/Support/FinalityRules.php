<?php

/**
 * Finality rules
 *
 * The rules that make a document final, and how a refusal is reported.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\DocumentFinalityRuleServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentFailureMapperInterface;

/**
 * The rules that make a document final, and how a refusal is reported.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
final class FinalityRules {

	/**
	 * Constructor.
	 *
	 * @param DocumentFinalityRuleServiceInterface $rules    Document finality rules
	 * @param FinalDocumentFailureMapperInterface  $failures Maps refusals to responses
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function __construct(
		public readonly DocumentFinalityRuleServiceInterface $rules,
		public readonly FinalDocumentFailureMapperInterface $failures,
	) {

	}//end __construct()
}//end class
