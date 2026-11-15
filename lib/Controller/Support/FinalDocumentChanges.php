<?php

/**
 * Final document changes
 *
 * The two ways a frozen document changes: a correction and an unfreeze.
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

use OCA\Filinq\Kernel\Contracts\FinalDocumentCorrectionServiceInterface;
use OCA\Filinq\Kernel\Contracts\FinalDocumentUnfreezeServiceInterface;

/**
 * The two ways a frozen document changes: a correction and an unfreeze.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
final class FinalDocumentChanges {

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentCorrectionServiceInterface $corrections Corrections of a final document
	 * @param FinalDocumentUnfreezeServiceInterface   $unfreeze    Unfreezing a final document
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function __construct(
		public readonly FinalDocumentCorrectionServiceInterface $corrections,
		public readonly FinalDocumentUnfreezeServiceInterface $unfreeze,
	) {

	}//end __construct()
}//end class
