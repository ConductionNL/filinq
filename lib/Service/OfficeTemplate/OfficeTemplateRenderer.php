<?php

/**
 * Filinq Office Template Renderer
 *
 * Renders an office template: reads its stored DOCX, fills it with the
 * resolved data and the text fragments it uses, and produces the requested
 * output through the existing conversion cascade. Also resolves fragment
 * references in Twig templates before they render.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use Throwable;

/**
 * Renders office templates and resolves fragments for Twig templates.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
 */
class OfficeTemplateRenderer {

	/**
	 * The template type of an office template.
	 */
	public const TYPE_OFFICE = 'office';

	/**
	 * A fragment reference in template text.
	 */
	private const FRAGMENT_REFERENCE = '/\$\{\s*fragment:([^{}\s]+)\s*\}/';

	/**
	 * Constructor.
	 *
	 * @param OfficeSourceStore          $store       The stored sources.
	 * @param OfficeTemplateFiller       $filler      Fills the merge tags.
	 * @param TextFragmentService        $fragments   The text fragments.
	 * @param MergeTagReader             $reader      Reads tags and text.
	 * @param PdfConversionService       $pdf         The conversion cascade.
	 * @param LibreOfficeHeadlessBackend $libreOffice Converts to ODT and HTML.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
	 */
	public function __construct(
		private readonly OfficeSourceStore $store,
		private readonly OfficeTemplateFiller $filler,
		private readonly TextFragmentService $fragments,
		private readonly MergeTagReader $reader,
		private readonly PdfConversionService $pdf,
		private readonly LibreOfficeHeadlessBackend $libreOffice,
	) {
	}//end __construct()

	/**
	 * Whether a template is an office template; absent type reads as Twig.
	 *
	 * @param array<string, mixed> $template The template.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-files-are-accepted-as-first-class-templates-req-ddota-001
	 */
	public function isOffice(array $template): bool {
		return ($template['templateType'] ?? 'twig') === self::TYPE_OFFICE;
	}//end isOffice()

	/**
	 * Render an office template to a format.
	 *
	 * @param array<string, mixed> $template The template (sourceFileId, fieldMap, namespace).
	 * @param array<string, mixed> $data     The resolved data.
	 * @param string               $format   docx, pdf, odf or html.
	 * @param array<string, mixed> $options  `pdfOptions.accessible` / `pdfOptions.pdfa` for the PDF.
	 *
	 * @return array{html: string, content: string, warnings: array<int, string>, fragments: array<int, string>}
	 *         `html` is the filled document's text, for display; `content` the output bytes.
	 *
	 * @throws Exception When the source is missing (404) or unreadable (422), or conversion fails.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
	 */
	public function render(array $template, array $data, string $format, array $options=[]): array {
		$filled = $this->fill(template: $template, data: $data);

		return [
			'html' => $filled['html'],
			'content' => $this->convert(docx: $filled['docx'], format: $format, options: $options),
			'warnings' => $filled['warnings'],
			'fragments' => $filled['fragments'],
		];
	}//end render()

	/**
	 * Turn a filled DOCX into an output format.
	 *
	 * @param string               $docx    The filled DOCX.
	 * @param string               $format  docx, pdf, odf or html.
	 * @param array<string, mixed> $options `pdfOptions.accessible` / `pdfOptions.pdfa` for the PDF.
	 *
	 * @return string The output bytes.
	 *
	 * @throws Exception With code 400 for another format; the conversion's own failure (503 without LibreOffice).
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/document-creatie-sjablonen/spec.md
	 */
	public function convert(string $docx, string $format, array $options=[]): string {
		return match ($format) {
			'docx' => $docx,
			'pdf' => $this->toPdf(docx: $docx, options: (array) ($options['pdfOptions'] ?? [])),
			'odf' => $this->libreOffice->convertDocument(bytes: $docx, fromExtension: 'docx', toExtension: 'odt'),
			'html' => $this->libreOffice->convertDocument(bytes: $docx, fromExtension: 'docx', toExtension: 'html'),
			default => throw new Exception(message: 'Office templates render to docx, pdf, odf or html, not '.$format, code: 400),
		};
	}//end convert()

	/**
	 * Fill an office template without converting it.
	 *
	 * @param array<string, mixed> $template The template.
	 * @param array<string, mixed> $data     The resolved data.
	 *
	 * @return array{docx: string, html: string, warnings: array<int, string>, fragments: array<int, string>}
	 *
	 * @throws Exception When the source is missing (404) or unreadable (422).
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
	 */
	public function fill(array $template, array $data): array {
		$source = $this->source(template: $template);
		$slugs = $this->filler->fragmentSlugs(tags: $this->reader->tags(docx: $source));
		$texts = [];
		if ($slugs !== []) {
			$texts = $this->fragments->texts(slugs: $slugs, namespace: (string) ($template['namespace'] ?? ''));
		}

		$filled = $this->filler->fill(
			docx: $source,
			data: $data,
			fragments: $texts,
			fieldMap: $this->fieldMap(template: $template)
		);
		$filled['html'] = $this->textHtml(docx: $filled['docx']);

		return $filled;
	}//end fill()

	/**
	 * Replace the fragment references of a Twig template by their text.
	 *
	 * Runs before the Twig render, so the sandbox's whitelist is untouched.
	 * A fragment's `${field}` tags become Twig prints of the same path; any
	 * other Twig syntax in a fragment is removed, so a fragment cannot add
	 * logic. A missing fragment becomes a visible marker and a warning.
	 *
	 * @param string $content   The Twig template text.
	 * @param string $namespace The template's namespace.
	 *
	 * @return array{content: string, warnings: array<int, string>, fragments: array<int, string>}
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function resolveTwigFragments(string $content, string $namespace): array {
		if (preg_match_all(self::FRAGMENT_REFERENCE, $content, $matches) < 1) {
			return ['content' => $content, 'warnings' => [], 'fragments' => []];
		}

		$texts = $this->fragments->texts(slugs: array_values(array_unique($matches[1])), namespace: $namespace);
		$warnings = [];
		$used = [];
		$resolved = (string) preg_replace_callback(
			self::FRAGMENT_REFERENCE,
			function (array $match) use ($texts, &$warnings, &$used): string {
				$slug = $match[1];
				if (isset($texts[$slug]) === false) {
					$warnings[] = 'Text fragment "'.$slug.'" does not exist; a marker is shown in its place.';
					return htmlspecialchars(sprintf(OfficeTemplateFiller::MISSING_FRAGMENT, $slug), ENT_QUOTES, 'UTF-8');
				}

				$used[] = $slug;
				$converted = $this->twigFragmentText(text: $texts[$slug]);
				$warnings = array_merge($warnings, $converted['warnings']);

				return $converted['text'];
			},
			$content
		);

		return ['content' => $resolved, 'warnings' => array_values(array_unique($warnings)), 'fragments' => array_values(array_unique($used))];
	}//end resolveTwigFragments()

	/**
	 * A fragment's text as Twig: plain text escaped, tags as prints.
	 *
	 * @param string $text The fragment text.
	 *
	 * @return array{text: string, warnings: array<int, string>}
	 */
	private function twigFragmentText(string $text): array {
		$warnings = [];
		// No Twig syntax of the fragment's own survives: only its ${...} tags print.
		$text = (string) preg_replace('/\{[{%#]|[}%#]\}/', '', $text);
		$parts = preg_split('/(\$\{[^{}]+\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$out = '';
		foreach ((array) $parts as $part) {
			if (preg_match('/^\$\{\s*([^{}]+?)\s*\}$/', (string) $part, $tag) !== 1) {
				$out .= nl2br(htmlspecialchars((string) $part, ENT_QUOTES, 'UTF-8'), false);
				continue;
			}

			if (str_starts_with($tag[1], OfficeTemplateFiller::FRAGMENT_PREFIX) === true) {
				$warnings[] = 'A text fragment cannot contain another fragment ("'.$tag[1].'"); it is left out.';
				continue;
			}

			if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z0-9_]+)*$/', $tag[1]) !== 1) {
				$warnings[] = 'Merge tag "'.$tag[1].'" in a text fragment is not a field path; it is left out.';
				continue;
			}

			$out .= '{{ '.$tag[1].' }}';
		}

		return ['text' => $out, 'warnings' => $warnings];
	}//end twigFragmentText()

	/**
	 * The stored source of an office template.
	 *
	 * @param array<string, mixed> $template The template.
	 *
	 * @return string The DOCX bytes.
	 *
	 * @throws Exception With code 404 when the source is not stored.
	 */
	private function source(array $template): string {
		$fileId = (int) ($template['sourceFileId'] ?? 0);
		$bytes = null;
		if ($fileId > 0) {
			$bytes = $this->store->read(fileId: $fileId);
		}

		if ($bytes === null || $bytes === '') {
			throw new Exception(message: 'The source file of this office template is not stored.', code: 404);
		}

		return $bytes;
	}//end source()

	/**
	 * The template's field mapping, tag => data path.
	 *
	 * @param array<string, mixed> $template The template.
	 *
	 * @return array<string, string>
	 */
	private function fieldMap(array $template): array {
		$map = [];
		foreach ((array) ($template['fieldMap'] ?? []) as $tag => $path) {
			if (is_string($tag) === true && is_string($path) === true && trim($path) !== '') {
				$map[$tag] = trim($path);
			}
		}

		return $map;
	}//end fieldMap()

	/**
	 * Convert a filled DOCX to PDF through the cascade.
	 *
	 * @param string               $docx    The filled DOCX.
	 * @param array<string, mixed> $options `accessible`, `pdfa`.
	 *
	 * @return string The PDF bytes.
	 */
	private function toPdf(string $docx, array $options): string {
		$opts = array_intersect_key($options, ['accessible' => true, 'pdfa' => true]);
		$source = $this->store->scratch(bytes: $docx, extension: 'docx');
		$pdf = null;
		try {
			$pdf = $this->pdf->convertToPdf(source: $source, opts: $opts);
			return $pdf->getContent();
		} finally {
			$this->discard(file: $source);
			if ($pdf instanceof File) {
				$this->discard(file: $pdf);
			}
		}
	}//end toPdf()

	/**
	 * Delete a working file, ignoring a file already gone.
	 *
	 * @param File $file The file.
	 *
	 * @return void
	 */
	private function discard(File $file): void {
		try {
			$file->delete();
		} catch (Throwable) {
			// Already gone.
		}
	}//end discard()

	/**
	 * A filled DOCX's text as HTML paragraphs.
	 *
	 * @param string $docx The DOCX.
	 *
	 * @return string
	 */
	private function textHtml(string $docx): string {
		$lines = array_filter(explode("\n", $this->reader->text(docx: $docx)), static fn (string $line): bool => $line !== '');

		return implode('', array_map(static fn (string $line): string => '<p>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</p>', $lines));
	}//end textHtml()
}//end class
