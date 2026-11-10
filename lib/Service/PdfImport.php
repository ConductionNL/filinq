<?php

/**
 * PDF Import
 *
 * An existing PDF a PDF/A-3 conversion imports page by page: its bytes and
 * the deadline the import must finish within.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The bytes and deadline of one PDF page import.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
final class PdfImport {

	/**
	 * Constructor.
	 *
	 * @param string $raw      The source PDF bytes.
	 * @param float  $deadline The absolute microtime(true) deadline.
	 */
	public function __construct(
		public readonly string $raw,
		public readonly float $deadline,
	) {

	}//end __construct()
}//end class
