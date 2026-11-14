<?php

/**
 * Sanitises the HTML of a document draft before it is produced.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Draft
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Draft;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Strips what can run or reach out from an edited document, keeping layout.
 *
 * The editor sanitises too; this is the server's own pass, so a request that
 * skipped the editor cannot put active content into a filed document. It
 * removes script-like elements, event-handler attributes and `javascript:`
 * or `data:` links, and keeps the markup and inline `<style>` the huisstijl
 * uses. Text is never interpreted, so braces stay characters.
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.3
 */
final class DraftHtmlSanitizer {

	/**
	 * Elements removed with their content.
	 */
	private const REMOVED_ELEMENTS = [
		'script',
		'iframe',
		'frame',
		'frameset',
		'object',
		'embed',
		'applet',
		'base',
		'link',
		'meta',
		'form',
		'input',
		'button',
		'textarea',
		'select',
		'template',
		'noscript',
	];

	/**
	 * Attributes that hold a URL.
	 */
	private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'xlink:href', 'background', 'poster'];

	/**
	 * Sanitise one draft's HTML.
	 *
	 * @param string $html The HTML as saved.
	 *
	 * @return string The sanitised HTML; a fragment stays a fragment, a document a document.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.3
	 */
	public function sanitize(string $html): string {
		if (trim($html) === '') {
			return '';
		}

		$isDocument = preg_match('/<html[\s>]/i', $html) === 1;
		$dom        = new DOMDocument();
		$previous   = libxml_use_internal_errors(true);
		$wrapped    = $html;
		if ($isDocument === false) {
			$wrapped = '<div id="filinq-draft-root">' . $html . '</div>';
		}

		$dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_NONET | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$this->strip(dom: $dom);

		if ($isDocument === true) {
			return $this->serialiseDocument(dom: $dom);
		}

		return $this->serialiseFragment(dom: $dom);
	}//end sanitize()

	/**
	 * Remove the active elements and attributes from a parsed document.
	 *
	 * @param DOMDocument $dom The parsed document.
	 *
	 * @return void
	 */
	private function strip(DOMDocument $dom): void {
		$xpath = new DOMXPath($dom);
		foreach (self::REMOVED_ELEMENTS as $name) {
			$nodes = iterator_to_array($xpath->query('//' . $name));
			foreach ($nodes as $node) {
				$node->parentNode?->removeChild($node);
			}
		}

		$elements = iterator_to_array($xpath->query('//*'));
		foreach ($elements as $element) {
			if ($element instanceof DOMElement === true) {
				$this->stripAttributes(element: $element);
			}
		}
	}//end strip()

	/**
	 * Remove event handlers and unsafe URLs from one element.
	 *
	 * @param DOMElement $element The element.
	 *
	 * @return void
	 */
	private function stripAttributes(DOMElement $element): void {
		$names = [];
		foreach ($element->attributes as $attribute) {
			$names[] = $attribute->nodeName;
		}

		foreach ($names as $name) {
			$lower = strtolower($name);
			if (str_starts_with($lower, 'on') === true || $lower === 'srcdoc') {
				$element->removeAttribute($name);
				continue;
			}

			if (in_array($lower, self::URL_ATTRIBUTES, true) === true
				&& $this->isUnsafeUrl(url: $element->getAttribute($name)) === true
			) {
				$element->removeAttribute($name);
				continue;
			}

			if ($lower === 'style' && preg_match('/expression\s*\(|url\s*\(\s*[\'"]?\s*javascript:/i', $element->getAttribute($name)) === 1) {
				$element->removeAttribute($name);
			}
		}
	}//end stripAttributes()

	/**
	 * Whether a URL would run script or embed unchecked content.
	 *
	 * Images may keep a `data:image/` URL, which is how a huisstijl logo is inlined.
	 *
	 * @param string $url The attribute value.
	 *
	 * @return bool True when the URL must go.
	 */
	private function isUnsafeUrl(string $url): bool {
		$compact = strtolower((string) preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5)));
		if (str_starts_with($compact, 'javascript:') === true || str_starts_with($compact, 'vbscript:') === true) {
			return true;
		}

		return str_starts_with($compact, 'data:') === true && str_starts_with($compact, 'data:image/') === false;
	}//end isUnsafeUrl()

	/**
	 * Serialise a whole document without the encoding hint.
	 *
	 * @param DOMDocument $dom The document.
	 *
	 * @return string The HTML.
	 */
	private function serialiseDocument(DOMDocument $dom): string {
		$html = '';
		foreach ($dom->childNodes as $child) {
			if ($child->nodeType === XML_PI_NODE) {
				continue;
			}

			$html .= $dom->saveHTML($child);
		}

		return $html;
	}//end serialiseDocument()

	/**
	 * Serialise the children of the fragment wrapper.
	 *
	 * @param DOMDocument $dom The document holding the wrapper.
	 *
	 * @return string The HTML of the fragment.
	 */
	private function serialiseFragment(DOMDocument $dom): string {
		$root = $dom->getElementById('filinq-draft-root');
		if ($root === null) {
			$root = (new DOMXPath($dom))->query('//div[@id="filinq-draft-root"]')->item(0);
		}

		if ($root instanceof DOMNode === false) {
			return '';
		}

		$html = '';
		foreach ($root->childNodes as $child) {
			$html .= $dom->saveHTML($child);
		}

		return $html;
	}//end serialiseFragment()
}//end class
