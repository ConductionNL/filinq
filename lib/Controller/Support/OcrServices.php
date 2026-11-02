<?php

/**
 * OCR services
 *
 * The OCR runner and the store of OCR results an OCR endpoint answers from.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/ocr-trigger-surface/spec.md#requirement-ocr-api-route-req-ddocr-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\OcrResultRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\OcrRunServiceInterface;

/**
 * The OCR runner and the store of OCR results an OCR endpoint answers from.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-trigger-surface/spec.md#requirement-ocr-api-route-req-ddocr-001
 */
final class OcrServices {

	/**
	 * Constructor.
	 *
	 * @param OcrRunServiceInterface       $runs    Runs OCR on a file
	 * @param OcrResultRepositoryInterface $results Stored OCR results
	 *
	 * @return void
	 *
	 * @spec openspec/specs/ocr-trigger-surface/spec.md#requirement-ocr-api-route-req-ddocr-001
	 */
	public function __construct(
		public readonly OcrRunServiceInterface $runs,
		public readonly OcrResultRepositoryInterface $results,
	) {

	}//end __construct()
}//end class
