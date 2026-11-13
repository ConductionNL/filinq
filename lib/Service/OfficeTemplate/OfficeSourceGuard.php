<?php

/**
 * Filinq Office Source Guard
 *
 * Decides whether an uploaded file may become an office template: a DOCX or
 * ODT package under the size cap, whose bytes are the kind its extension
 * says, and that carries no macros.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCA\Filinq\Exception\OfficeSourceRefusedException;
use ZipArchive;

/**
 * Inspects an uploaded office file before anything is stored.
 *
 * A template store must never hand out macros, so a macro-enabled extension,
 * a `vbaProject.bin` part or a macro-enabled content type refuses the file.
 * The kind of file is read from the package itself: a DOCX has
 * `word/document.xml`, an ODT has the OpenDocument text `mimetype` entry.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class OfficeSourceGuard {

	/**
	 * The size cap when none is configured: 20 MB.
	 */
	public const DEFAULT_MAX_BYTES = 20971520;

	/**
	 * The extensions of macro-enabled Word files.
	 */
	private const MACRO_EXTENSIONS = ['docm', 'dotm'];

	/**
	 * The OpenDocument text media type, as an ODT names it in its `mimetype` entry.
	 */
	private const ODT_MIMETYPE = 'application/vnd.oasis.opendocument.text';

	/**
	 * Check an upload and say which kind of office file it is.
	 *
	 * @param string $fileName The uploaded file's name.
	 * @param string $bytes    The file's content.
	 * @param int    $maxBytes The size cap in bytes.
	 *
	 * @return string `docx` or `odt`.
	 *
	 * @throws OfficeSourceRefusedException When the file may not become a template.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function inspect(string $fileName, string $bytes, int $maxBytes): string {
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if (in_array($extension, self::MACRO_EXTENSIONS, true) === true) {
			throw new OfficeSourceRefusedException(
				message: 'Macro-enabled files cannot be templates.',
				reason: OfficeSourceRefusedException::REASON_MACRO
			);
		}

		if (strlen($bytes) > $maxBytes) {
			throw new OfficeSourceRefusedException(
				message: 'The file is larger than the '.$maxBytes.' bytes a template may be.',
				reason: OfficeSourceRefusedException::REASON_SIZE
			);
		}

		if (in_array($extension, ['docx', 'odt'], true) === false) {
			throw new OfficeSourceRefusedException(
				message: 'Only DOCX and ODT files can be office templates.',
				reason: OfficeSourceRefusedException::REASON_TYPE
			);
		}

		$detected = $this->detectedKind(bytes: $bytes);
		if ($detected !== $extension) {
			throw new OfficeSourceRefusedException(
				message: 'The content of the file is not a '.strtoupper($extension).' document.',
				reason: OfficeSourceRefusedException::REASON_TYPE
			);
		}

		return $detected;
	}//end inspect()

	/**
	 * Which kind of office file the bytes are.
	 *
	 * @param string $bytes The file's content.
	 *
	 * @return string `docx`, `odt`, or '' when it is neither.
	 *
	 * @throws OfficeSourceRefusedException When the package carries macros.
	 */
	private function detectedKind(string $bytes): string {
		if (str_starts_with($bytes, "PK\x03\x04") === false) {
			return '';
		}

		$path = tempnam(sys_get_temp_dir(), 'filinq-office-');
		file_put_contents($path, $bytes);
		$zip = new ZipArchive();
		try {
			if ($zip->open($path, ZipArchive::RDONLY) !== true) {
				return '';
			}

			$kind = $this->packageKind(zip: $zip);
			$zip->close();
			return $kind;
		} finally {
			unlink($path);
		}
	}//end detectedKind()

	/**
	 * Which kind of office package an open archive is.
	 *
	 * @param ZipArchive $zip The archive.
	 *
	 * @return string `docx`, `odt`, or ''.
	 *
	 * @throws OfficeSourceRefusedException When the package carries macros.
	 */
	private function packageKind(ZipArchive $zip): string {
		for ($index = 0; $index < $zip->numFiles; $index++) {
			$name = (string) $zip->getNameIndex($index);
			if (strcasecmp(basename($name), 'vbaProject.bin') === 0) {
				throw new OfficeSourceRefusedException(
					message: 'The file contains macros, which a template may not carry.',
					reason: OfficeSourceRefusedException::REASON_MACRO
				);
			}
		}

		$contentTypes = $zip->getFromName('[Content_Types].xml');
		if (is_string($contentTypes) === true && stripos($contentTypes, 'macroEnabled') !== false) {
			throw new OfficeSourceRefusedException(
				message: 'The file contains macros, which a template may not carry.',
				reason: OfficeSourceRefusedException::REASON_MACRO
			);
		}

		if (is_string($contentTypes) === true && $zip->locateName('word/document.xml') !== false) {
			return 'docx';
		}

		if (trim((string) $zip->getFromName('mimetype')) === self::ODT_MIMETYPE) {
			return 'odt';
		}

		return '';
	}//end packageKind()
}//end class
