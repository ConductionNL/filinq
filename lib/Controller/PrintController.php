<?php

/**
 * Print Controller
 *
 * Controller for print preview and PDF/A download endpoints.
 * Supports both template ID references (via TemplateService) and
 * inline template content for rendering.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/print-preview/spec.md
 * @spec openspec/specs/print-preview/spec.md
 * @spec openspec/specs/print-preview/spec.md
 * @spec openspec/changes/print-functionality/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Service\TemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for print preview and PDF/A download endpoints
 *
 * Resolves templates from either a templateId (stored template) or
 * inline template content, then delegates to PdfService for rendering.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class PrintController extends Controller {
	/**
	 * Constructor for PrintController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param PdfService $pdfService Service for PDF generation
	 * @param TemplateService $templateService Service for template retrieval
	 * @param IUserSession $userSession User session for authentication
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly PdfService $pdfService,
		private readonly TemplateService $templateService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Get request options as an array, defaulting to empty array
	 *
	 * @return array The options array from the request
	 */
	private function getRequestOptions(): array {
		$options = $this->request->getParam('options', []);
		if (is_array($options) === true) {
			return $options;
		}

		return [];
	}//end getRequestOptions()

	/**
	 * Extract print configuration from request options
	 *
	 * Reads duplex, color, paperTray, and stapling from options array.
	 * These are returned alongside generated content for external print services.
	 *
	 * @param array $options Request options array
	 *
	 * @return array{duplex: bool, color: bool, paperTray: string, stapling: bool}
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-2
	 */
	private function extractPrintConfig(array $options): array {
		return [
			'duplex' => (bool)($options['duplex'] ?? false),
			'color' => (bool)($options['color'] ?? true),
			'paperTray' => (string)($options['paperTray'] ?? 'default'),
			'stapling' => (bool)($options['stapling'] ?? false),
		];

	}//end extractPrintConfig()

	/**
	 * Resolve template content and options from request parameters
	 *
	 * Supports two modes:
	 * - templateId: Loads a stored template via TemplateService
	 * - template: Uses inline template content directly
	 *
	 * @return array{content: string, title: string, data: array, options: array} Resolved template data
	 *
	 * @throws Exception If neither templateId nor template content is provided
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 * @spec openspec/changes/print-functionality/tasks.md#task-2
	 */
	private function resolveTemplate(): array {
		$templateId = $this->request->getParam('templateId');
		$template = $this->request->getParam('template');
		$data = $this->request->getParam('data', []);

		if (is_array($data) === false) {
			$data = [];
		}

		if (empty($templateId) === false) {
			$templateRecord = $this->templateService->getTemplate($templateId);
			return [
				'content' => $templateRecord['content'] ?? '',
				'title' => $templateRecord['name'] ?? 'document',
				'data' => $data,
				'options' => [
					'format' => $templateRecord['format'] ?? 'A4',
					'orientation' => $templateRecord['orientation'] ?? 'P',
					'duplex' => $templateRecord['duplex'] ?? false,
					'color' => $templateRecord['color'] ?? true,
					'paperTray' => $templateRecord['paperTray'] ?? 'default',
					'stapling' => $templateRecord['stapling'] ?? false,
				],
			];
		}

		if (empty($template) === false) {
			return [
				'content' => $template,
				'title' => 'document',
				'data' => $data,
				'options' => $this->getRequestOptions(),
			];
		}

		throw new Exception(
			message: 'Either templateId or template content is required',
			code: 400
		);

	}//end resolveTemplate()

	/**
	 * Generate a print preview with rendered HTML and print-optimized CSS
	 *
	 * Accepts JSON body with:
	 * - templateId (string, optional): UUID of a stored template
	 * - template (string, optional): Inline Twig/HTML template content
	 * - data (object, optional): Data context for template rendering
	 * - options (object, optional): PDF configuration (format, orientation)
	 *
	 * One of templateId or template is required.
	 *
	 * @return JSONResponse JSON with rendered HTML and title, or error
	 *
	 * @NoAdminRequired
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	public function preview(): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$resolved = $this->resolveTemplate();

			$options = $resolved['options'];

			$html = $this->pdfService->renderHtmlPreview(
				templateContent: $resolved['content'],
				data: $resolved['data'],
				options: $options
			);
			$printConfig = $this->extractPrintConfig(options: $options);

			return new JSONResponse(
				data: [
					'html' => $html,
					'title' => $resolved['title'],
					'printConfig' => $printConfig,
				]
			);
		} catch (Exception $e) {
			$statusCode = 500;
			if ($e->getCode() >= 400 && $e->getCode() < 600) {
				$statusCode = $e->getCode();
			}

			$this->logger->error(
				message: 'Print preview failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: $statusCode
			);
		}//end try

	}//end preview()

	/**
	 * Generate and download a PDF/A-3b compliant document
	 *
	 * Accepts JSON body with:
	 * - templateId (string, optional): UUID of a stored template
	 * - template (string, optional): Inline Twig/HTML template content
	 * - data (object, optional): Data context for template rendering
	 * - filename (string, optional): Suggested download filename (default: document.pdf)
	 *
	 * One of templateId or template is required. PDF/A mode is always enabled.
	 *
	 * @return DataDownloadResponse|JSONResponse PDF/A download or error
	 *
	 * @NoAdminRequired
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	public function downloadPdfA(): DataDownloadResponse|JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$resolved = $this->resolveTemplate();
			$filename = $this->request->getParam('filename', 'document.pdf');

			$options = $resolved['options'];

			$options['pdfa'] = true;
			$options['title'] = $resolved['title'];

			$pdfContent = $this->pdfService->renderPdf(
				templateContent: $resolved['content'],
				data: $resolved['data'],
				options: $options
			);

			return new DataDownloadResponse(
				data: $pdfContent,
				filename: $filename,
				contentType: 'application/pdf'
			);
		} catch (Exception $e) {
			$statusCode = 500;
			if ($e->getCode() >= 400 && $e->getCode() < 600) {
				$statusCode = $e->getCode();
			}

			$this->logger->error(
				message: 'PDF/A download failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);

			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: $statusCode
			);
		}//end try

	}//end downloadPdfA()
}//end class
