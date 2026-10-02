<?php

/**
 * Office source inspector
 *
 * Decides whether an uploaded office file may become a template and reads
 * its merge tags: the extension, the package signature, the macro check, the
 * size cap and the `${...}` tags PhpWord's TemplateProcessor finds.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCP\IAppConfig;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;
use ZipArchive;

/**
 * Checks an office upload and extracts its merge tags.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 */
class OfficeSourceInspector {

	/**
	 * Default upload cap: 20 MB.
	 *
	 * @var int
	 */
	public const DEFAULT_MAX_BYTES = 20971520;

	/**
	 * The app-config key of the upload cap.
	 *
	 * @var string
	 */
	public const MAX_BYTES_KEY = 'templates_max_upload_bytes';

	/**
	 * Extensions that carry macros by definition.
	 *
	 * @var string[]
	 */
	private const MACRO_EXTENSIONS = ['docm', 'dotm'];

	/**
	 * The main part each accepted extension must contain.
	 *
	 * @var array<string, string>
	 */
	private const MAIN_PARTS = [
		'docx' => 'word/document.xml',
		'odt' => 'content.xml',
	];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config Reads the upload cap.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
	) {

	}//end __construct()

	/**
	 * Refuse an upload that may not become a template.
	 *
	 * @param string $fileName The uploaded name.
	 * @param string $bytes    The content.
	 *
	 * @return string The accepted extension: docx or odt.
	 *
	 * @throws OfficeTemplateRefused 422 for a macro file, an oversized file, an unsupported
	 *                               extension or content that is not the package it claims.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
	 */
	public function assertAcceptable(string $fileName, string $bytes): string {
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if (in_array($extension, self::MACRO_EXTENSIONS, true) === true) {
			throw new OfficeTemplateRefused(message: 'Macro-enabled documents (.' . $extension . ') are not accepted as templates.', reason: 'macro');
		}

		if (isset(self::MAIN_PARTS[$extension]) === false) {
			throw new OfficeTemplateRefused(message: 'Only DOCX and ODT files can be uploaded as office templates.', reason: 'extension');
		}

		$cap = $this->maxBytes();
		if (strlen($bytes) > $cap) {
			throw new OfficeTemplateRefused(message: 'The file is larger than the upload limit of ' . $cap . ' bytes.', reason: 'size');
		}

		$entries = $this->packageEntries(bytes: $bytes);
		if ($entries === null || in_array(self::MAIN_PARTS[$extension], $entries, true) === false) {
			throw new OfficeTemplateRefused(message: 'The file content is not a ' . strtoupper($extension) . ' document.', reason: 'mime');
		}

		foreach ($entries as $entry) {
			if (strtolower(basename($entry)) === 'vbaproject.bin') {
				throw new OfficeTemplateRefused(message: 'The document contains macros (vbaProject.bin); macro-enabled documents are not accepted as templates.', reason: 'macro');
			}
		}

		return $extension;

	}//end assertAcceptable()

	/**
	 * The merge tags of a DOCX, in document order, without duplicates.
	 *
	 * @param string $docxBytes The DOCX.
	 *
	 * @return string[] The tag names, such as `aanvrager.naam` or `fragment:slug`.
	 *
	 * @throws OfficeTemplateRefused 422 when PhpWord cannot open the document.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
	 */
	public function extractTags(string $docxBytes): array {
		$path = $this->writeTemp(bytes: $docxBytes);
		try {
			$variables = (new TemplateProcessor($path))->getVariables();
		} catch (Throwable $e) {
			throw new OfficeTemplateRefused(message: 'The document could not be read as DOCX: ' . $e->getMessage(), reason: 'corrupt');
		} finally {
			unlink($path);
		}

		$tags = [];
		foreach ($variables as $variable) {
			$name = trim((string) $variable);
			if ($name === '' || str_starts_with($name, '/') === true) {
				continue;
			}

			$tags[$name] = true;
		}

		return array_keys($tags);

	}//end extractTags()

	/**
	 * The plain text of a DOCX, for search and diff (the binary stays in Files).
	 *
	 * @param string $docxBytes The DOCX.
	 *
	 * @return string The text, at most 20000 characters.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
	 */
	public function textProjection(string $docxBytes): string {
		$zip = new ZipArchive();
		$path = $this->writeTemp(bytes: $docxBytes);
		$text = '';
		if ($zip->open($path) === true) {
			$xml = (string) $zip->getFromName('word/document.xml');
			$zip->close();
			$xml = preg_replace('#</w:p>#', "\n", $xml) ?? '';
			$text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
		}

		unlink($path);

		return mb_substr(trim($text), 0, 20000);

	}//end textProjection()

	/**
	 * The entry names of a ZIP package, or null when it is not one.
	 *
	 * @param string $bytes The content.
	 *
	 * @return string[]|null The entries.
	 */
	private function packageEntries(string $bytes): ?array {
		if (str_starts_with($bytes, "PK\x03\x04") === false) {
			return null;
		}

		$path = $this->writeTemp(bytes: $bytes);
		$zip = new ZipArchive();
		$entries = null;
		if ($zip->open($path) === true) {
			$entries = [];
			for ($index = 0; $index < $zip->numFiles; $index++) {
				$entries[] = (string) $zip->getNameIndex($index);
			}

			$zip->close();
		}

		unlink($path);

		return $entries;

	}//end packageEntries()

	/**
	 * The configured upload cap in bytes.
	 *
	 * @return int The cap.
	 */
	private function maxBytes(): int {
		$value = (int) $this->config->getValueString('filinq', self::MAX_BYTES_KEY, (string) self::DEFAULT_MAX_BYTES);
		if ($value <= 0) {
			return self::DEFAULT_MAX_BYTES;
		}

		return $value;

	}//end maxBytes()

	/**
	 * Write bytes to a temporary file.
	 *
	 * @param string $bytes The content.
	 *
	 * @return string The path; the caller removes it.
	 */
	private function writeTemp(string $bytes): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq_office_');
		file_put_contents($path, $bytes);

		return $path;

	}//end writeTemp()
}//end class
