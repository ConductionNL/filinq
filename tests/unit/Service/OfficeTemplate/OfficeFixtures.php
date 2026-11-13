<?php

/**
 * Office files built in memory for the office template tests: a DOCX made
 * with PhpWord, a bare DOCX or ODT package, and packages carrying macros.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use ZipArchive;

/**
 * Builds office files for tests.
 */
final class OfficeFixtures {

	/**
	 * A real DOCX, one paragraph per line, written by PhpWord.
	 *
	 * @param array<int, string> $paragraphs The paragraphs.
	 *
	 * @return string The DOCX bytes.
	 */
	public static function docx(array $paragraphs): string {
		$word = new PhpWord();
		$section = $word->addSection();
		foreach ($paragraphs as $paragraph) {
			$section->addText($paragraph);
		}

		$path = tempnam(sys_get_temp_dir(), 'filinq-test-');
		IOFactory::createWriter($word, 'Word2007')->save($path);
		$bytes = (string) file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end docx()

	/**
	 * A zip package with the given entries.
	 *
	 * @param array<string, string> $entries The entries by name.
	 *
	 * @return string The package bytes.
	 */
	public static function package(array $entries): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-test-');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		foreach ($entries as $name => $content) {
			$zip->addFromString($name, $content);
		}

		$zip->close();
		$bytes = (string) file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end package()

	/**
	 * A minimal DOCX package.
	 *
	 * @param array<string, string> $extra Extra entries.
	 * @param string                $types The content types document.
	 *
	 * @return string
	 */
	public static function bareDocx(array $extra=[], string $types='<Types/>'): string {
		return self::package(array_merge(['[Content_Types].xml' => $types, 'word/document.xml' => '<w:document/>'], $extra));
	}//end bareDocx()

	/**
	 * A minimal ODT package.
	 *
	 * @return string
	 */
	public static function bareOdt(): string {
		return self::package(['mimetype' => 'application/vnd.oasis.opendocument.text', 'content.xml' => '<office:document-content/>']);
	}//end bareOdt()
}//end class
