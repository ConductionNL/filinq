<?php

/**
 * PDF conversions
 *
 * The two PDF conversions a merge uses: to PDF and to archival PDF/A-3.
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

use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\PdfConversionService;

/**
 * The two PDF conversions a merge uses: to PDF and to archival PDF/A-3.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 */
final class PdfConversions {

	/**
	 * Constructor.
	 *
	 * @param PdfConversionService   $conversion Converts office documents to PDF
	 * @param Pdfa3ConversionService $archival   Converts to archival PDF/A-3
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function __construct(
		public readonly PdfConversionService $conversion,
		public readonly Pdfa3ConversionService $archival,
	) {

	}//end __construct()
}//end class
