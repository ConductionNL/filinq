<?php

/**
 * PDF rendering
 *
 * Turning a template into a PDF: the template renderer and the PDF generator.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;

/**
 * Turning a template into a PDF: the template renderer and the PDF generator.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */
final class PdfRendering {

	/**
	 * Constructor.
	 *
	 * @param TemplateRenderer $renderer Renders a template to HTML
	 * @param PdfService       $pdf      Renders HTML to PDF
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	public function __construct(
		public readonly TemplateRenderer $renderer,
		public readonly PdfService $pdf,
	) {

	}//end __construct()
}//end class
