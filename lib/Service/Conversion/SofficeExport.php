<?php

/**
 * Soffice Export
 *
 * What one soffice export works on: the source and the output it asks for.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

/**
 * The source bytes, their extension, the --convert-to argument and the
 * extension soffice gives its output.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
final class SofficeExport {

	/**
	 * Constructor.
	 *
	 * @param string $bytes           The source bytes.
	 * @param string $extension       The source extension, '' for none.
	 * @param string $convertTo       The --convert-to argument.
	 * @param string $outputExtension The extension soffice gives its output (pdf, docx, odt).
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function __construct(
		public readonly string $bytes,
		public readonly string $extension,
		public readonly string $convertTo,
		public readonly string $outputExtension = 'pdf',
	) {

	}//end __construct()
}//end class
