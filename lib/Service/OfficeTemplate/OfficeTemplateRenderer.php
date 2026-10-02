<?php

/**
 * Office template renderer
 *
 * The one place the render path asks which kind of template it holds. An
 * office template is read from its stored DOCX, its fragments resolved, its
 * tags filled and the result converted to the requested format. A Twig
 * template only gets its `${fragment:slug}` references here: each becomes a
 * plain token before Twig runs and the fragment's escaped text after, so the
 * Twig sandbox never sees fragment content and its whitelist stays as it is.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

/**
 * Renders office templates and resolves fragments for Twig templates.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
 */
class OfficeTemplateRenderer {

	/**
	 * Constructor.
	 *
	 * @param OfficeSourceStore    $store     The stored sources.
	 * @param OfficeTemplateFiller $filler    Fills a DOCX.
	 * @param OfficeConverter      $converter Converts the filled DOCX.
	 * @param FragmentResolver     $fragments Resolves text fragments.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OfficeSourceStore $store,
		private readonly OfficeTemplateFiller $filler,
		private readonly OfficeConverter $converter,
		private readonly FragmentResolver $fragments,
	) {

	}//end __construct()

	/**
	 * Whether a template is an office template. Absent templateType reads as twig.
	 *
	 * @param array $template The template.
	 *
	 * @return bool True for office.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function isOffice(array $template): bool {
		return ($template['templateType'] ?? 'twig') === 'office';

	}//end isOffice()

	/**
	 * Render an office template to a format.
	 *
	 * @param array                $template The office template.
	 * @param array<string, mixed> $data     The resolved data.
	 * @param string               $format   pdf, docx, odf or html.
	 * @param array                $options  The generation options (huisstijlId is ignored here).
	 *
	 * @return array{content: string, html: string, warnings: string[], fragments: array}
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function render(array $template, array $data, string $format, array $options = []): array {
		$filled = $this->fill(template: $template, data: $data);
		$warnings = $filled['warnings'];
		if (empty($options['huisstijlId']) === false) {
			$warnings[] = 'huisstijlId is ignored for an office template: the document carries its own house style.';
		}

		$content = $this->converter->output(docxBytes: $filled['bytes'], format: $format);
		$html = '';
		if ($format === 'html') {
			$html = $content;
		}

		return ['content' => $content, 'html' => $html, 'warnings' => $warnings, 'fragments' => $filled['fragments']];

	}//end render()

	/**
	 * The HTML preview of an office template with data.
	 *
	 * @param array                $template The office template.
	 * @param array<string, mixed> $data     The resolved data.
	 *
	 * @return array{html: string, warnings: string[]}
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function preview(array $template, array $data): array {
		$filled = $this->fill(template: $template, data: $data);

		return ['html' => $this->converter->previewHtml(docxBytes: $filled['bytes']), 'warnings' => $filled['warnings']];

	}//end preview()

	/**
	 * Replace the fragment references of a Twig template by tokens.
	 *
	 * @param array                $template The Twig template.
	 * @param array<string, mixed> $data     The resolved data.
	 *
	 * @return array{content: string, tokens: array<string, string>, warnings: string[]}
	 *               content with tokens; tokens to the escaped HTML of each fragment.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function prepareTwig(array $template, array $data): array {
		$content = (string) ($template['content'] ?? '');
		$slugs = $this->fragments->referencedSlugs(text: $content);
		if ($slugs === []) {
			return ['content' => $content, 'tokens' => [], 'warnings' => []];
		}

		$resolved = $this->fragments->resolve(slugs: $slugs, namespace: (string) ($template['namespace'] ?? ''), data: $data);
		$tokens = [];
		foreach ($resolved['texts'] as $slug => $text) {
			$token = 'FILINQFRAGMENT' . count($tokens) . 'X' . bin2hex(random_bytes(4));
			$tokens[$token] = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			$content = str_replace('${fragment:' . $slug . '}', $token, $content);
		}

		return ['content' => $content, 'tokens' => $tokens, 'warnings' => $resolved['warnings']];

	}//end prepareTwig()

	/**
	 * Put the fragments back in place of their tokens.
	 *
	 * @param string                $html   The rendered HTML.
	 * @param array<string, string> $tokens From {@see prepareTwig()}.
	 *
	 * @return string The HTML with the fragments.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function finishTwig(string $html, array $tokens): string {
		return strtr($html, $tokens);

	}//end finishTwig()

	/**
	 * Read the source, resolve its fragments and fill it.
	 *
	 * @param array                $template The office template.
	 * @param array<string, mixed> $data     The data.
	 *
	 * @return array{bytes: string, warnings: string[], fragments: array}
	 */
	private function fill(array $template, array $data): array {
		$source = $this->store->read(fileId: (int) ($template['sourceFileId'] ?? 0));
		$tags = array_map(static fn (mixed $tag): string => '${' . $tag . '}', (array) ($template['mergeFields'] ?? []));
		$resolved = $this->fragments->resolve(
			slugs: $this->fragments->referencedSlugs(text: implode(' ', $tags)),
			namespace: (string) ($template['namespace'] ?? ''),
			data: $data
		);
		$filled = $this->filler->fill(
			docxBytes: $source,
			data: $data,
			fieldMap: (array) ($template['fieldMap'] ?? []),
			fragments: $resolved['texts']
		);

		return [
			'bytes' => $filled['bytes'],
			'warnings' => array_merge($resolved['warnings'], $filled['warnings']),
			'fragments' => $resolved['used'],
		];

	}//end fill()
}//end class
