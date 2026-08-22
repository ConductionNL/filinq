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

use Exception;
use Psr\Log\LoggerInterface;

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
	 * Constructor.
	 *
	 * @param TemplateRenderer $templateRenderer Service for Twig rendering
	 * @param PdfService $pdfService Service for PDF generation
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService
	 * @param LoggerInterface $logger Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateRenderer $templateRenderer,
		private readonly PdfService $pdfService,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Load the huisstijl configuration from OpenRegister.
	 *
	 * @param string|null $huisstijlId UUID of the huisstijl object, or null
	 *
	 * @return array|null The huisstijl configuration or null if not configured
	 */
	public function loadHuisstijl(?string $huisstijlId): ?array {
		if (empty($huisstijlId) === true) {
			return null;
		}

		try {
			$objectService = $this->objectResolver->resolve();
			$result = $objectService->find(
				id: $huisstijlId,
				register: 'document',
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
	 */
	public function buildPdfOptions(array $template, ?array $huisstijl, array $options): array {
		$pdfOptions = [
			'format' => $template['format'] ?? 'A4',
			'orientation' => $template['orientation'] ?? 'P',
		];

		if ($huisstijl !== null && isset($huisstijl['defaultMargins']) === true) {
			$pdfOptions['margin'] = $huisstijl['defaultMargins'];
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
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
	 */
	public function renderWithHuisstijl(
		string $templateContent,
		array $data,
		?array $huisstijl,
	): array {
		$fullContent = '';
		$warnings = [];

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
	 * Produce output in the requested format.
	 *
	 * @param string $htmlContent The rendered HTML content
	 * @param string $format The output format (pdf, odf, html)
	 * @param array $pdfOptions The PDF generation options
	 *
	 * @return string The generated content (binary for pdf/odf, string for html)
	 *
	 * @throws Exception If output generation fails
	 */
	public function produceOutput(string $htmlContent, string $format, array $pdfOptions): string {
		switch ($format) {
			case 'html':
				return $htmlContent;
			case 'odf':
				return $this->convertToOdf(htmlContent: $htmlContent);
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
	 * Convert HTML to ODF (.odt) using LibreOffice headless.
	 *
	 * @param string $htmlContent The HTML content to convert
	 *
	 * @return string The ODT binary content
	 *
	 * @throws Exception If LibreOffice is not available or conversion fails
	 *
	 * @psalm-suppress ForbiddenCode shell_exec is required to locate the LibreOffice binary
	 */
	private function convertToOdf(string $htmlContent): string {
		$soffice = trim((string)shell_exec('which soffice 2>/dev/null'));
		if (empty($soffice) === true) {
			throw new Exception(
				message: 'ODF conversion service unavailable: LibreOffice is not installed',
				code: 503
			);
		}

		$tempDir = '/tmp/filinq_odf_convert';
		if (file_exists($tempDir) === false) {
			mkdir($tempDir, 0700, true);
		}

		$tempFile = $tempDir . '/' . uniqid('odf_') . '.html';
		file_put_contents($tempFile, $htmlContent);

		try {
			$outDir = escapeshellarg($tempDir);
			$inFile = escapeshellarg($tempFile);
			$command = escapeshellcmd($soffice) . " --headless --convert-to odt --outdir {$outDir} {$inFile} 2>&1";

			$output = [];
			$returnCode = 0;
			exec($command, $output, $returnCode);

			if ($returnCode !== 0) {
				throw new Exception(
					message: 'ODF conversion failed: ' . implode("\n", $output),
					code: 500
				);
			}

			$odtFile = preg_replace('/\.html$/', '.odt', $tempFile);
			if (file_exists($odtFile) === false) {
				throw new Exception(
					message: 'ODF output file not found after conversion',
					code: 500
				);
			}

			$content = file_get_contents($odtFile);
			unlink($odtFile);

			return $content;
		} finally {
			if (file_exists($tempFile) === true) {
				unlink($tempFile);
			}
		}//end try

	}//end convertToOdf()
}//end class
