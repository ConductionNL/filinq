<?php

/**
 * Accessible (tagged, PDF/UA-1 target) PDF output for generated documents.
 *
 * The mPDF engine cannot write structure tags, so an `accessible: true`
 * request never goes there. The rendered HTML gets a document language and a title and
 * goes to LibreOffice's tagged export. What comes back is checked for tags,
 * language and title before it is returned: a PDF without them is refused,
 * never passed off as accessible. No LibreOffice means no PDF, not an
 * untagged one.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Validation\AccessibilityChecks;
use OCP\IConfig;

/**
 * Renders HTML to a tagged PDF with language and title.
 */
class AccessiblePdfRenderer {

	/**
	 * A BCP 47 language tag, loosely: nl, nl-NL, sr-Latn-RS.
	 */
	private const LANGUAGE_TAG = '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/';

	/**
	 * The same presence checks document validation runs.
	 *
	 * @var AccessibilityChecks
	 */
	private readonly AccessibilityChecks $checks;

	/**
	 * Constructor.
	 *
	 * @param LibreOfficeHeadlessBackend $libreOffice The tagged export.
	 * @param IConfig                    $config      System config (default_language).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LibreOfficeHeadlessBackend $libreOffice,
		private readonly IConfig $config,
	) {
		$this->checks = new AccessibilityChecks();

	}//end __construct()

	/**
	 * Render HTML to an accessible PDF.
	 *
	 * @param string               $html    The rendered HTML (fragment or document).
	 * @param array<string, mixed> $options PDF options: lang, templateLanguage, title, templateName, pdfa.
	 *
	 * @return string The PDF bytes.
	 *
	 * @throws ConversionFailedException Without a language or title, without LibreOffice,
	 *                                   or when the PDF lacks tags, language or title.
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.3
	 */
	public function render(string $html, array $options): string {
		$lang = $this->language(options: $options);
		$title = $this->title(options: $options);

		$pdf = $this->libreOffice->convertTagged(
			bytes: $this->document(html: $html, lang: $lang, title: $title),
			extension: 'html',
			pdfa: (($options['pdfa'] ?? false) === true)
		);

		$missing = [];
		if ($this->checks->isTagged(bytes: $pdf) === false) {
			$missing[] = 'tags';
		}

		if ($this->checks->hasLanguage(bytes: $pdf) === false) {
			$missing[] = 'language';
		}

		if ($this->checks->hasTitle(bytes: $pdf) === false) {
			$missing[] = 'title';
		}

		if ($missing !== []) {
			throw new ConversionFailedException(
				message: 'LibreOffice returned a PDF without ' . implode(', ', $missing) . '; it is not returned as accessible output.',
				attempts: [
					['name' => $this->libreOffice->name(), 'available' => true, 'supports' => true, 'reason' => 'output lacks ' . implode(', ', $missing)],
				],
				code: 502
			);
		}

		return $pdf;

	}//end render()

	/**
	 * The document language: the option, the template's, the instance's.
	 *
	 * @param array<string, mixed> $options The PDF options.
	 *
	 * @return string The language tag.
	 *
	 * @throws ConversionFailedException When none is set, or one is not a language tag.
	 */
	private function language(array $options): string {
		$candidates = [
			(string) ($options['lang'] ?? ''),
			(string) ($options['templateLanguage'] ?? ''),
			$this->config->getSystemValueString('default_language', ''),
		];
		foreach ($candidates as $candidate) {
			$candidate = str_replace('_', '-', trim($candidate));
			if ($candidate === '') {
				continue;
			}

			if (preg_match(self::LANGUAGE_TAG, $candidate) !== 1) {
				throw new ConversionFailedException(message: sprintf('"%s" is not a language tag such as nl or nl-NL.', $candidate), code: 422);
			}

			return $candidate;
		}

		throw new ConversionFailedException(
			message: 'Accessible PDF output needs a document language: pass pdfOptions.lang, '
				. 'give the template a language, or set default_language for this Nextcloud.',
			code: 422
		);

	}//end language()

	/**
	 * The document title: the option, else the template name.
	 *
	 * @param array<string, mixed> $options The PDF options.
	 *
	 * @return string The title.
	 *
	 * @throws ConversionFailedException When there is neither.
	 */
	private function title(array $options): string {
		foreach ([$options['title'] ?? '', $options['templateName'] ?? ''] as $candidate) {
			if (is_string($candidate) === true && trim($candidate) !== '') {
				return trim($candidate);
			}
		}

		throw new ConversionFailedException(
			message: 'Accessible PDF output needs a document title: pass pdfOptions.title or use a named template.',
			code: 422
		);

	}//end title()

	/**
	 * A full HTML document with the language on <html> and the title in <head>.
	 *
	 * @param string $html  The rendered HTML.
	 * @param string $lang  The language tag.
	 * @param string $title The title.
	 *
	 * @return string The document.
	 */
	private function document(string $html, string $lang, string $title): string {
		$titleTag = '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</title>';
		if (preg_match('/<html\b[^>]*>/i', $html) !== 1) {
			return '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="utf-8">' . $titleTag . '</head><body>' . $html . '</body></html>';
		}

		$html = (string) preg_replace('/<html\b[^>]*>/i', '<html lang="' . $lang . '">', $html, 1);
		$html = (string) preg_replace('#<title\b[^>]*>.*?</title>#is', '', $html);
		// Callbacks, not replacement strings: a title may hold "$1".
		if (preg_match('/<head\b[^>]*>/i', $html) === 1) {
			return (string) preg_replace_callback('/<head\b[^>]*>/i', static fn (array $m): string => $m[0] . '<meta charset="utf-8">' . $titleTag, $html, 1);
		}

		$head = '<head><meta charset="utf-8">' . $titleTag . '</head>';
		return (string) preg_replace_callback('/<html\b[^>]*>/i', static fn (array $m): string => $m[0] . $head, $html, 1);

	}//end document()
}//end class
