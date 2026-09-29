<?php

/**
 * HTML to editable office formats
 *
 * The one place rendered HTML becomes an editable DOCX or ODT. Document
 * generation and correspondence both call it; each used to carry its own
 * `soffice --convert-to` call outside the conversion lock.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-format-output/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;

/**
 * Converts rendered HTML to DOCX or ODT through LibreOffice headless.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class HtmlToOfficeConverter {
	/**
	 * Constructor.
	 *
	 * @param LibreOfficeHeadlessBackend $libreOffice The cascade's soffice backend (lock, temp dirs, timeout).
	 */
	public function __construct(
		private readonly LibreOfficeHeadlessBackend $libreOffice,
	) {

	}//end __construct()

	/**
	 * Whether DOCX and ODT can be made right now.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/multi-format-output/tasks.md#task-2.3
	 */
	public function isAvailable(): bool {
		return $this->libreOffice->isAvailable();

	}//end isAvailable()

	/**
	 * Rendered HTML as an editable Word document.
	 *
	 * @param string $html The rendered HTML.
	 *
	 * @return string The DOCX bytes.
	 *
	 * @throws ConversionFailedException When LibreOffice is unavailable (503) or fails.
	 *
	 * @spec openspec/changes/multi-format-output/tasks.md#task-2.1
	 */
	public function toDocx(string $html): string {
		return $this->libreOffice->convertBytes(bytes: $html, fromExtension: 'html', toExtension: 'docx');

	}//end toDocx()

	/**
	 * Rendered HTML as an OpenDocument text file.
	 *
	 * @param string $html The rendered HTML.
	 *
	 * @return string The ODT bytes.
	 *
	 * @throws ConversionFailedException When LibreOffice is unavailable (503) or fails.
	 *
	 * @spec openspec/changes/multi-format-output/tasks.md#task-2.5
	 */
	public function toOdt(string $html): string {
		return $this->libreOffice->convertBytes(bytes: $html, fromExtension: 'html', toExtension: 'odt');

	}//end toOdt()
}//end class
