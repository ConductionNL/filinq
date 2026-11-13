<?php

/**
 * Filinq Merge Tag Reader
 *
 * Reads the `${field}` merge tags of a DOCX with PhpWord's TemplateProcessor,
 * and the plain text of the document for search and version comparison.
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
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;
use ZipArchive;

/**
 * The merge tags and the text of a DOCX.
 *
 * A block's closing tag (`${/block}`) is part of its opening tag's region,
 * not a field of its own, so it is not listed.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class MergeTagReader {

	/**
	 * The merge tags of a DOCX, each once, in document order.
	 *
	 * @param string $docx The DOCX content.
	 *
	 * @return array<int, string> The tags, without `${` and `}`.
	 *
	 * @throws OfficeSourceRefusedException When the file is not a readable DOCX.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function tags(string $docx): array {
		$path = $this->tempFile(bytes: $docx);
		try {
			$variables = (new TemplateProcessor($path))->getVariables();
		} catch (Throwable $e) {
			throw new OfficeSourceRefusedException(
				message: 'The file could not be read as a DOCX document.',
				reason: OfficeSourceRefusedException::REASON_TYPE
			);
		} finally {
			unlink($path);
		}

		$tags = [];
		foreach ($variables as $variable) {
			$tag = trim((string) $variable);
			if ($tag === '' || str_starts_with($tag, '/') === true || in_array($tag, $tags, true) === true) {
				continue;
			}

			$tags[] = $tag;
		}

		return $tags;
	}//end tags()

	/**
	 * The text of a DOCX's body, one line per paragraph.
	 *
	 * @param string $docx The DOCX content.
	 *
	 * @return string The text, or '' when the body cannot be read.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function text(string $docx): string {
		$path = $this->tempFile(bytes: $docx);
		$zip = new ZipArchive();
		try {
			if ($zip->open($path, ZipArchive::RDONLY) !== true) {
				return '';
			}

			$xml = (string) $zip->getFromName('word/document.xml');
			$zip->close();
		} finally {
			unlink($path);
		}

		$xml = (string) preg_replace('#</w:p>#', "\n", $xml);
		$xml = (string) preg_replace('#<w:tab/>#', "\t", $xml);
		$text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
		$lines = array_map('trim', explode("\n", $text));

		return implode("\n", array_values(array_filter($lines, static fn (string $line): bool => $line !== '')));
	}//end text()

	/**
	 * Write bytes to a temporary file.
	 *
	 * @param string $bytes The content.
	 *
	 * @return string The file's path.
	 */
	private function tempFile(string $bytes): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-docx-');
		file_put_contents($path, $bytes);

		return $path;
	}//end tempFile()
}//end class
