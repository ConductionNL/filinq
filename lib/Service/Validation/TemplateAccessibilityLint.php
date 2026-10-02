<?php

/**
 * Accessibility lint for a template preview: what an author can fix in the
 * template before any document is made from it.
 *
 * It reads the rendered preview HTML and reports images without
 * alternative text, heading levels that skip (h1 then h3), tables without
 * header cells, and a document with no language anywhere. Each finding
 * says where: the nth image with its file name, the heading's own text,
 * the nth table with its first cell. It is advice: it never stops a save
 * or a preview. Document validation stays the enforcement point.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Validation;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Lints rendered template HTML for accessibility.
 */
class TemplateAccessibilityLint {

	/**
	 * How much text a finding quotes to locate an element.
	 */
	private const QUOTE_LENGTH = 60;

	/**
	 * The lint findings for rendered HTML.
	 *
	 * @param string $html             The rendered preview HTML.
	 * @param string $instanceLanguage The instance default language, '' for none.
	 *
	 * @return array<int, array<string, mixed>> Findings: rule, position (1-based, 0 for the document), text, and for a jump from/to.
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
	 */
	public function lint(string $html, string $instanceLanguage): array {
		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$xpath = new DOMXPath($dom);

		$findings = array_merge(
			$this->images(xpath: $xpath),
			$this->headings(xpath: $xpath),
			$this->tables(xpath: $xpath)
		);

		if (trim($instanceLanguage) === '' && $xpath->query('//*[@lang and string-length(normalize-space(@lang)) > 0]')->length === 0) {
			$findings[] = ['rule' => 'language-unresolved', 'position' => 0, 'text' => ''];
		}

		return $findings;

	}//end lint()

	/**
	 * Images whose alt text is missing or empty.
	 *
	 * @param DOMXPath $xpath The document.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function images(DOMXPath $xpath): array {
		$findings = [];
		$position = 0;
		foreach ($xpath->query('//img') as $img) {
			$position++;
			if ($img instanceof DOMElement === true && trim($img->getAttribute('alt')) === '') {
				$src = (string) parse_url($img->getAttribute('src'), PHP_URL_PATH);
				$findings[] = ['rule' => 'image-missing-alt', 'position' => $position, 'text' => $this->quote(text: basename($src))];
			}
		}

		return $findings;

	}//end images()

	/**
	 * Headings that go down more than one level at a time.
	 *
	 * @param DOMXPath $xpath The document.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function headings(DOMXPath $xpath): array {
		$findings = [];
		$position = 0;
		$level = 0;
		foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
			$position++;
			$current = (int) substr($heading->nodeName, 1);
			if ($level > 0 && $current > ($level + 1)) {
				$findings[] = [
					'rule' => 'heading-order-jump',
					'position' => $position,
					'text' => $this->quote(text: $heading->textContent),
					'from' => 'h' . $level,
					'to' => 'h' . $current,
				];
			}

			$level = $current;
		}

		return $findings;

	}//end headings()

	/**
	 * Tables without any header cell.
	 *
	 * @param DOMXPath $xpath The document.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function tables(DOMXPath $xpath): array {
		$findings = [];
		$position = 0;
		foreach ($xpath->query('//table') as $table) {
			$position++;
			if ($xpath->query('.//th', $table)->length > 0) {
				continue;
			}

			$first = $xpath->query('.//td', $table)->item(0);
			$findings[] = ['rule' => 'table-without-headers', 'position' => $position, 'text' => $this->quote(text: ($first->textContent ?? ''))];
		}

		return $findings;

	}//end tables()

	/**
	 * A short, single-line quote to locate an element.
	 *
	 * @param string $text The text.
	 *
	 * @return string
	 */
	private function quote(string $text): string {
		$text = trim((string) preg_replace('/\s+/u', ' ', $text));
		if (mb_strlen($text) > self::QUOTE_LENGTH) {
			return mb_substr($text, 0, self::QUOTE_LENGTH) . '…';
		}

		return $text;

	}//end quote()
}//end class
