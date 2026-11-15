<?php

/**
 * Print Controller
 *
 * Controller for print preview and PDF/A download endpoints.
 * Supports both template ID references (via TemplateService) and
 * inline template content for rendering.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
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

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
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
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PrintController extends Controller {
	use AnswersNotAuthenticated;
	/**
	 * Constructor for PrintController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param PdfServiceInterface $pdfService Service for PDF generation
	 * @param TemplateServiceInterface $templateService Service for template retrieval
	 * @param IUserSession $userSession User session for authentication
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly PdfServiceInterface $pdfService,
		private readonly TemplateServiceInterface $templateService,
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
			return $this->fromStoredTemplate(record: $this->templateService->getTemplate($templateId), data: $data);
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
	 * The resolved template for a stored template record, with its print options and defaults.
	 *
	 * @param array<string, mixed> $record The stored template.
	 * @param array<mixed> $data Data context for template rendering.
	 *
	 * @return array{content: string, title: string, data: array, options: array} Resolved template data
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	private function fromStoredTemplate(array $record, array $data): array {
		return [
			'content' => $record['content'] ?? '',
			'title' => $record['name'] ?? 'document',
			'data' => $data,
			'options' => [
				'format' => $record['format'] ?? 'A4',
				'orientation' => $record['orientation'] ?? 'P',
				'duplex' => $record['duplex'] ?? false,
				'color' => $record['color'] ?? true,
				'paperTray' => $record['paperTray'] ?? 'default',
				'stapling' => $record['stapling'] ?? false,
			],
		];
	}//end fromStoredTemplate()

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
	 * @spec openspec/specs/print-preview/spec.md
	 */
	public function preview(): JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return $this->notAuthenticated(message: 'Not authenticated');
			}

			return $this->previewOf(resolved: $this->resolveTemplate());
		} catch (Exception $e) {
			return $this->failed(what: 'Print preview failed: ', exception: $e);
		}
	}//end preview()

	/**
	 * The HTML preview, title and print configuration of a resolved template.
	 *
	 * @param array{content: string, title: string, data: array, options: array} $resolved The resolved template.
	 *
	 * @return JSONResponse The preview.
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	private function previewOf(array $resolved): JSONResponse {
		$html = $this->pdfService->renderHtmlPreview(
			templateContent: $resolved['content'],
			data: $resolved['data'],
			options: $resolved['options']
		);

		return new JSONResponse(
			data: [
				'html' => $html,
				'title' => $resolved['title'],
				'printConfig' => $this->extractPrintConfig(options: $resolved['options']),
			]
		);
	}//end previewOf()

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
	 * @spec openspec/specs/print-preview/spec.md
	 *
	 * @no-admin-idor-exempt a templateId is read through TemplateService::getTemplate() with OpenRegister
	 * RBAC on (template read: authenticated); otherwise the caller's own posted template is rendered.
	 */
	public function downloadPdfA(): DataDownloadResponse|JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return $this->notAuthenticated(message: 'Not authenticated');
			}

			return $this->pdfAOf(resolved: $this->resolveTemplate());
		} catch (Exception $e) {
			return $this->failed(what: 'PDF/A download failed: ', exception: $e);
		}
	}//end downloadPdfA()

	/**
	 * The PDF/A download of a resolved template, titled after it.
	 *
	 * @param array{content: string, title: string, data: array, options: array} $resolved The resolved template.
	 *
	 * @return DataDownloadResponse The PDF/A download.
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	private function pdfAOf(array $resolved): DataDownloadResponse {
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
			filename: $this->request->getParam('filename', 'document.pdf'),
			contentType: 'application/pdf'
		);
	}//end pdfAOf()

	/**
	 * Log a failed request and answer with its HTTP-range code, else 500.
	 *
	 * @param string $what The log message prefix naming the endpoint.
	 * @param Exception $exception The failure.
	 *
	 * @return JSONResponse The error response.
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	private function failed(string $what, Exception $exception): JSONResponse {
		$statusCode = 500;
		if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
			$statusCode = $exception->getCode();
		}

		$this->logger->error(
			message: $what . $exception->getMessage(),
			context: ['exception' => $exception]
		);

		return new JSONResponse(
			data: ['error' => $exception->getMessage()],
			statusCode: $statusCode
		);
	}//end failed()
}//end class
