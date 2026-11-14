<?php

/**
 * OCR Options
 *
 * The Tesseract settings one OCR pass runs with: the language string and
 * the resolution pages are rendered and read at.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The language string and DPI of one OCR pass.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */
final class OcrOptions {

	/**
	 * Constructor.
	 *
	 * @param string $languages Tesseract language string, e.g. "nld+eng".
	 * @param int    $dpi       Resolution pages are rendered and read at.
	 */
	public function __construct(
		public readonly string $languages,
		public readonly int $dpi,
	) {

	}//end __construct()
}//end class
