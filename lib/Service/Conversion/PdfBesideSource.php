<?php

/**
 * PDF Beside Source
 *
 * Writes a converted PDF next to its source as `<name>.pdf`, replacing a
 * previous conversion of the same name: a conversion is fresh, never an
 * incremental update.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Service\StripsFileExtension;
use OCP\Files\File;

/**
 * Writes the converted PDF beside its source.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
final class PdfBesideSource {
	use StripsFileExtension;

	/**
	 * Write the PDF beside the source, deleting an earlier file of that name first.
	 *
	 * @param File   $source   The source file.
	 * @param string $pdfBytes The PDF.
	 *
	 * @return File The written PDF.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function write(File $source, string $pdfBytes): File {
		$parent = $source->getParent();
		$outputName = $this->stripExtension(name: $source->getName()) . '.pdf';
		if ($parent->nodeExists($outputName) === true) {
			$parent->get($outputName)->delete();
		}

		return $parent->newFile($outputName, $pdfBytes);

	}//end write()
}//end class
