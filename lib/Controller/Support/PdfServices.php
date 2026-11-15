<?php

/**
 * PDF services
 *
 * The PDF generator and the archival PDF/A-3 generator a PDF endpoint answers from.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
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

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;

/**
 * The PDF generator and the archival PDF/A-3 generator a PDF endpoint answers from.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */
final class PdfServices {

	/**
	 * Constructor.
	 *
	 * @param PdfServiceInterface             $pdf   PDF generation from a template
	 * @param Pdfa3ConversionServiceInterface $pdfa3 Archival PDF/A-3 generation
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	public function __construct(
		public readonly PdfServiceInterface $pdf,
		public readonly Pdfa3ConversionServiceInterface $pdfa3,
	) {

	}//end __construct()
}//end class
