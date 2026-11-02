<?php

/**
 * Agent artefacts
 *
 * What an agent edit leaves behind: the PDF conversion of the result and the marker that labels it as agent work.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\PdfConversionService;

/**
 * What an agent edit leaves behind: the PDF conversion of the result and the marker that labels it as agent work.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */
final class AgentArtefacts {

	/**
	 * Constructor.
	 *
	 * @param PdfConversionService $pdfConversion Converts the edited document to PDF
	 * @param AgentArtefactMarker  $marker        Marks the result as agent work
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function __construct(
		public readonly PdfConversionService $pdfConversion,
		public readonly AgentArtefactMarker $marker,
	) {

	}//end __construct()
}//end class
