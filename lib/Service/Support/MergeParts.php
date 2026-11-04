<?php

/**
 * Merge parts
 *
 * The pieces a merged PDF is built from: the PDF document factory and the cover page renderer.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\MergeCoverRenderer;
use OCA\Filinq\Service\PdfDocumentFactory;

/**
 * The pieces a merged PDF is built from: the PDF document factory and the cover page renderer.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 */
final class MergeParts {

	/**
	 * Constructor.
	 *
	 * @param PdfDocumentFactory $documents Builds the merged PDF
	 * @param MergeCoverRenderer $cover     Renders the cover page
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function __construct(
		public readonly PdfDocumentFactory $documents,
		public readonly MergeCoverRenderer $cover,
	) {

	}//end __construct()
}//end class
