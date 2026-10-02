<?php

/**
 * Document Render Pipeline
 *
 * Owns the "template content in, output bytes out" half of document generation:
 * loading the huisstijl, assembling the page options, rendering the Twig
 * template with optional header/footer, and producing the final PDF / ODF / HTML
 * bytes. Extracted from `DocumentService`.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use Exception;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Conversion\HtmlToOfficeConverter;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\Fpdi;

/**
 * Renders template content and produces the requested output format.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentRenderPipeline {
	/**
	 * Warnings raised by the most recent {@see produceOutput()} call, such as
	 * a chart that could not be carried into an ODF file. Reset on every call.
	 *
	 * @var string[]
	 */
	private array $lastOutputWarnings = [];

	/**
	 * Constructor.
	 *
	 * @param TemplateRenderer $templateRenderer Service for Twig rendering
	 * @param PdfService $pdfService Service for PDF generation
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param SvgRasterizer $svgRasterizer Turns chart SVG into PNG before an ODF conversion
	 * @param ObjectionTermCalculator|null $objectionTerm Adds the legal basis and the objection deadline of a decision letter
	 * @param HtmlToOfficeConverter|null $officeConverter Makes DOCX and ODT; without it both answer 503
	 * @param OfficeTemplateRenderer|null $officeRenderer Renders office templates and resolves text fragments
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateRenderer $templateRenderer,
		private readonly PdfService $pdfService,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
		private readonly SvgRasterizer $svgRasterizer,
		private readonly ?ObjectionTermCalculator $objectionTerm = null,
		private readonly ?HtmlToOfficeConverter $officeConverter = null,
		private readonly ?OfficeTemplateRenderer $officeRenderer = null,
	) {

	}//end __construct()

	/**
	 * Load the huisstijl configuration from OpenRegister.
	 *
	 * @param string|null $huisstijlId UUID of the huisstijl object, or null
	 *
	 * @return array|null The huisstijl configuration or null if not configured
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function loadHuisstijl(?string $huisstijlId): ?array {
		if (empty($huisstijlId) === true) {
			return null;
		}

		try {
			$objectService = $this->objectResolver->resolve();
			$result = $objectService->find(
				id: $huisstijlId,
				register: 'filinq',
				schema: 'huisstijl'
			);

			if (empty($result) === true) {
				return null;
			}

			if (is_object($result) === true
				&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
			) {
				return $result->jsonSerialize();
			}

			return $result;
		} catch (Exception $e) {
			$this->logger->warning(
				message: 'Failed to load huisstijl: ' . $e->getMessage(),
				context: ['huisstijlId' => $huisstijlId]
			);
			return null;
		}//end try

	}//end loadHuisstijl()

	/**
	 * Build PDF generation options from template and huisstijl config.
	 *
	 * @param array $template The template object
	 * @param array|null $huisstijl The huisstijl configuration
	 * @param array $options The request options
	 *
	 * @return array The merged PDF options
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.3
	 */
	public function buildPdfOptions(array $template, ?array $huisstijl, array $options): array {
		$pdfOptions = [
			'format' => $template['format'] ?? 'A4',
			'orientation' => $template['orientation'] ?? 'P',
		];

		if ($huisstijl !== null && isset($huisstijl['defaultMargins']) === true) {
			$pdfOptions['margin'] = $huisstijl['defaultMargins'];
		}

		// What accessible output needs from the template: its name as the
		// fallback title, its language when it has one. The caller's own
		// title and lang (below) win.
		if (is_string($template['name'] ?? null) === true && $template['name'] !== '') {
			$pdfOptions['templateName'] = $template['name'];
		}

		if (is_string($template['language'] ?? null) === true && $template['language'] !== '') {
			$pdfOptions['templateLanguage'] = $template['language'];
		}

		if (isset($options['pdfOptions']) === true) {
			$pdfOptions = array_merge($pdfOptions, $options['pdfOptions']);
		}

		return $pdfOptions;
	}//end buildPdfOptions()

	/**
	 * Render template content with optional huisstijl header and footer.
	 *
	 * @param string $templateContent The Twig template content
	 * @param array $data The data context
	 * @param array|null $huisstijl The huisstijl configuration
	 *
	 * @return array{html: string, warnings: string[]} The rendered HTML plus
	 *                                                 any generation warnings raised by chart()/data_table() calls
	 *
	 * @throws Exception If rendering fails
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-002
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function renderWithHuisstijl(
		string $templateContent,
		array $data,
		?array $huisstijl,
	): array {
		$fullContent = '';
		$warnings = [];

		if ($this->objectionTerm !== null) {
			$decision = $this->objectionTerm->addToContext(data: $data, templateContent: $templateContent);
			$data = $decision['data'];
			$warnings = $decision['warnings'];
		}

		if ($huisstijl !== null && empty($huisstijl['headerHtml']) === false) {
			$headerData = array_merge($data, ['huisstijl' => $huisstijl]);
			$fullContent .= $this->templateRenderer->renderTemplate(
				templateContent: $huisstijl['headerHtml'],
				data: $headerData,
				huisstijl: $huisstijl
			);
			$warnings = array_merge($warnings, $this->templateRenderer->getLastRenderWarnings());
		}

		$fullContent .= $this->templateRenderer->renderTemplate(
			templateContent: $templateContent,
			data: $data,
			huisstijl: $huisstijl
		);
		$warnings = array_merge($warnings, $this->templateRenderer->getLastRenderWarnings());

		if ($huisstijl !== null && empty($huisstijl['footerHtml']) === false) {
			$footerData = array_merge($data, ['huisstijl' => $huisstijl]);
			$fullContent .= $this->templateRenderer->renderTemplate(
				templateContent: $huisstijl['footerHtml'],
				data: $footerData,
				huisstijl: $huisstijl
			);
			$warnings = array_merge($warnings, $this->templateRenderer->getLastRenderWarnings());
		}

		return [
			'html' => $fullContent,
			'warnings' => $warnings,
		];

	}//end renderWithHuisstijl()

	/**
	 * Render a template's body and produce the requested format: an office
	 * template through its DOCX, a Twig template through the huisstijl and
	 * the HTML conversions.
	 *
	 * @param array      $template  The template.
	 * @param array      $data      The resolved data.
	 * @param array|null $huisstijl The loaded huisstijl (Twig only).
	 * @param array      $output    format, pdfOptions and the generation options.
	 *
	 * @return array{content: string, html: string, warnings: string[], templateType: string}
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function renderBody(array $template, array $data, ?array $huisstijl, array $output): array {
		if ($this->officeRenderer?->isOffice(template: $template) === true) {
			$office = $this->officeRenderer->render(template: $template, data: $data, format: $output['format'], options: $output['options']);

			return ['content' => $office['content'], 'html' => $office['html'], 'warnings' => $office['warnings'], 'templateType' => 'office'];
		}

		$rendered = $this->renderTwig(template: $template, data: $data, huisstijl: $huisstijl);
		$content = $this->produceOutput(
			htmlContent: $rendered['html'],
			format: $output['format'],
			pdfOptions: $output['pdfOptions']
		);

		return [
			'content' => $content,
			'html' => $rendered['html'],
			'warnings' => array_merge($rendered['warnings'], $this->getLastOutputWarnings()),
			'templateType' => 'twig',
		];

	}//end renderBody()

	/**
	 * Render a Twig template with its huisstijl, its `${fragment:slug}`
	 * references resolved around the Twig run (the sandbox never sees them).
	 *
	 * @param array      $template  The Twig template.
	 * @param array      $data      The resolved data.
	 * @param array|null $huisstijl The loaded huisstijl.
	 *
	 * @return array{html: string, warnings: string[]}
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function renderTwig(array $template, array $data, ?array $huisstijl): array {
		$prepared = ['content' => (string) $template['content'], 'tokens' => [], 'warnings' => []];
		if ($this->officeRenderer !== null) {
			$prepared = $this->officeRenderer->prepareTwig(template: $template, data: $data);
		}

		$rendered = $this->renderWithHuisstijl(
			templateContent: $prepared['content'],
			data: $data,
			huisstijl: $huisstijl
		);
		if ($prepared['tokens'] !== []) {
			$rendered['html'] = $this->officeRenderer->finishTwig(html: $rendered['html'], tokens: $prepared['tokens']);
		}

		return ['html' => $rendered['html'], 'warnings' => array_merge($prepared['warnings'], $rendered['warnings'])];

	}//end renderTwig()

	/**
	 * The HTML preview of a template: an office template's filled DOCX as
	 * HTML, a Twig template rendered with its huisstijl.
	 *
	 * @param array      $template  The template.
	 * @param array      $data      The resolved data.
	 * @param array|null $huisstijl The loaded huisstijl (Twig only).
	 *
	 * @return array{html: string, warnings: string[]}
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function renderPreview(array $template, array $data, ?array $huisstijl): array {
		if ($this->officeRenderer?->isOffice(template: $template) === true) {
			return $this->officeRenderer->preview(template: $template, data: $data);
		}

		return $this->renderTwig(template: $template, data: $data, huisstijl: $huisstijl);

	}//end renderPreview()

	/**
	 * Produce output in the requested format.
	 *
	 * @param string $htmlContent The rendered HTML content
	 * @param string $format The output format (pdf, odf, docx, html)
	 * @param array $pdfOptions The PDF generation options
	 *
	 * @return string The generated content (binary for pdf/odf/docx, string for html)
	 *
	 * @throws Exception If output generation fails
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.5
	 */
	public function produceOutput(string $htmlContent, string $format, array $pdfOptions): string {
		$this->lastOutputWarnings = [];

		switch ($format) {
			case 'html':
				return $htmlContent;
			case 'odf':
			case 'docx':
				return $this->convertToOffice(htmlContent: $htmlContent, format: $format);
			case 'pdf':
			default:
				return $this->pdfService->renderPdf(
					templateContent: $htmlContent,
					data: [],
					options: $pdfOptions
				);
		}//end switch

	}//end produceOutput()

	/**
	 * What a caller needs to know about produced bytes without reading them again.
	 *
	 * A SHA-256 over the bytes always, and the page count for a PDF. DOCX, ODT
	 * and HTML have no page structure filinq can count, so they carry no
	 * `pageCount` at all rather than a zero that reads as an empty document. A
	 * PDF the parser cannot read (a compressed cross-reference table) carries
	 * none either, for the same reason.
	 *
	 * @param string $content The produced bytes
	 * @param string $format  The format they were produced in
	 *
	 * @return array{sha256: string, pageCount?: int}
	 *
	 * @spec openspec/changes/generated-document-names-its-template-version/specs/template-version-provenance/spec.md#requirement-the-generation-result-reports-the-bytes-filinq-produced-req-ddtvp-005
	 */
	public function describeOutput(string $content, string $format): array {
		$described = ['sha256' => hash('sha256', $content)];
		if ($format !== 'pdf') {
			return $described;
		}

		$stream = fopen('php://memory', 'r+');
		try {
			fwrite($stream, $content);
			rewind($stream);
			$described['pageCount'] = (new Fpdi())->setSourceFile($stream);
		} catch (\Throwable $e) {
			$this->logger->warning(message: 'Could not count the pages of a generated PDF: ' . $e->getMessage());
		} finally {
			fclose($stream);
		}

		return $described;

	}//end describeOutput()

	/**
	 * Warnings raised by the most recent {@see produceOutput()} call.
	 *
	 * @return string[]
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
	 */
	public function getLastOutputWarnings(): array {
		return $this->lastOutputWarnings;

	}//end getLastOutputWarnings()

	/**
	 * Convert HTML to DOCX or ODT through the shared LibreOffice converter.
	 *
	 * Charts arrive as inline SVG, which an office file cannot carry, so they
	 * are rasterised first; the warnings name what could not be.
	 *
	 * @param string $htmlContent The rendered HTML.
	 * @param string $format      odf or docx.
	 *
	 * @return string The file's bytes.
	 *
	 * @throws Exception 503 with the matrix's reason when LibreOffice is unavailable.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.5
	 */
	private function convertToOffice(string $htmlContent, string $format): string {
		if ($this->officeConverter === null || $this->officeConverter->isAvailable() === false) {
			throw new Exception(message: LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, code: 503);
		}

		$rasterized = $this->svgRasterizer->rasterizeInlineSvg(html: $htmlContent, format: $format);
		$this->lastOutputWarnings = $rasterized['warnings'];

		if ($format === 'docx') {
			return $this->officeConverter->toDocx(html: $rasterized['html']);
		}

		return $this->officeConverter->toOdt(html: $rasterized['html']);

	}//end convertToOffice()
}//end class
