<?php

/**
 * PDF Controller
 *
 * Controller for the PDF generation endpoint.
 * Accepts a Twig template and data context, returns a generated PDF.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/specs/print-preview/spec.md
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for PDF generation endpoints
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * @psalm-type PdfAParams = array{data:array<mixed>,options:array<mixed>,metadata:array<mixed>,attachments:array<mixed>,filename:string}
 */
class PdfController extends Controller {
	use AnswersNotAuthenticated;
	/**
	 * Constructor for PdfController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param PdfServiceInterface $pdfService Service for PDF generation
	 * @param Pdfa3ConversionServiceInterface $pdfa3Service Service for archival PDF/A-3
	 *                                                      generation (attachments +
	 *                                                      MDTO metadata)
	 * @param IL10N $l10n The localization service
	 * @param IUserSession $userSession User session for authentication
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly PdfServiceInterface $pdfService,
		private readonly Pdfa3ConversionServiceInterface $pdfa3Service,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Generate a PDF from a Twig template and data context
	 *
	 * Accepts JSON body with:
	 * - template (string, required): Twig/HTML template content
	 * - data (object, optional): Data context for template rendering
	 * - options (object, optional): PDF configuration (format, orientation, margin, title)
	 * - filename (string, optional): Suggested download filename (default: document.pdf)
	 *
	 * @return DataDownloadResponse|JSONResponse PDF download or error response
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 *
	 * @no-admin-idor-exempt no stored object is addressed. The template and data come from the request
	 * body and the PDF goes back to the caller, so there is no id to steer.
	 */
	public function render(): DataDownloadResponse|JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
			}

			return $this->renderRequested();
		} catch (Exception $e) {
			return $this->renderFailed(exception: $e);
		}

	}//end render()

	/**
	 * Render the request's template, data and options to a PDF download.
	 *
	 * @return DataDownloadResponse|JSONResponse The PDF, or 400 without a template.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	private function renderRequested(): DataDownloadResponse|JSONResponse {
		$template = $this->request->getParam('template');
		$filename = $this->request->getParam('filename', 'document.pdf');

		if (empty($template) === true) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Template content is required')],
				statusCode: 400
			);
		}

		$pdfContent = $this->pdfService->renderPdf(
			templateContent: $template,
			data: $this->arrayParam(name: 'data'),
			options: $this->arrayParam(name: 'options')
		);

		return new DataDownloadResponse(
			data: $pdfContent,
			filename: $filename,
			contentType: 'application/pdf'
		);

	}//end renderRequested()

	/**
	 * A request parameter that must be an array; anything else reads as empty.
	 *
	 * @param string $name The parameter name.
	 *
	 * @return array<mixed> The parameter, or [] when absent or not an array.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	private function arrayParam(string $name): array {
		$value = $this->request->getParam($name, []);
		if (is_array($value) === false) {
			return [];
		}

		return $value;
	}//end arrayParam()

	/**
	 * Log a failed render and answer with its HTTP-range code, else 500.
	 *
	 * @param Exception $exception The failure.
	 *
	 * @return JSONResponse The error response.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	private function renderFailed(Exception $exception): JSONResponse {
		$this->logger->error(
			message: 'PDF generation failed: ' . $exception->getMessage(),
			context: ['exception' => $exception]
		);

		return new JSONResponse(
			data: ['error' => $exception->getMessage()],
			statusCode: $this->clampStatusCode(code: (int)$exception->getCode())
		);

	}//end renderFailed()

	/**
	 * Generate a PDF/A-3b compliant document from a Twig template
	 *
	 * Behaves identically to render() but forces PDF/A-3b compliance.
	 * The pdfa option in the request body is ignored; PDF/A is always enabled.
	 *
	 * Accepts JSON body with:
	 * - template (string, required): Twig/HTML template content
	 * - data (object, optional): Data context for template rendering
	 * - options (object, optional): PDF configuration (format, orientation, margin, title)
	 * - filename (string, optional): Suggested download filename (default: document.pdf)
	 * - metadata (object, optional): MDTO/archival metadata. When present (or when
	 *   `attachments` is present), generation is delegated to Pdfa3ConversionService
	 *   so the document is emitted as a full archival PDF/A-3 with the metadata folded
	 *   into its XMP packet — see openspec/changes/pdfa3-conversion.
	 * - attachments (array, optional): [{name, mime, content, description?,
	 *   AFRelationship?}, ...] to embed alongside the generated document (PDF/A-3's
	 *   defining feature over PDF/A-1/A-2).
	 *
	 * @return DataDownloadResponse|JSONResponse PDF/A download or error response
	 *
	 * @NoAdminRequired
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 *
	 * @no-admin-idor-exempt no stored object is addressed. The template, data, metadata and attachments
	 * come from the request body as content and the PDF goes back to the caller, so there is no id to
	 * steer.
	 */
	public function renderPdfA(): DataDownloadResponse|JSONResponse {
		try {
			if ($this->userSession->getUser() === null) {
				return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
			}

			return $this->renderPdfARequested();
		} catch (Pdfa3ConversionException $e) {
			return $this->mapPdfa3ExceptionToResponse(exception: $e);
		} catch (Exception $e) {
			return $this->mapGenericExceptionToResponse(exception: $e);
		}

	}//end renderPdfA()

	/**
	 * Render the request's template as PDF/A: archival PDF/A-3 when metadata or
	 * attachments are present, else the plain service with pdfa forced on.
	 *
	 * @return DataDownloadResponse|JSONResponse The PDF, or 400 without a template.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function renderPdfARequested(): DataDownloadResponse|JSONResponse {
		$template = $this->request->getParam('template');
		if (empty($template) === true) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Template content is required')],
				statusCode: 400
			);
		}

		$params = $this->collectRenderPdfAParams();
		if (empty($params['metadata']) === false || empty($params['attachments']) === false) {
			return $this->renderArchivalPdfA(template: $template, params: $params);
		}

		return $this->renderPlainPdfA(template: $template, params: $params);
	}//end renderPdfARequested()

	/**
	 * Render the template through the plain PDF service with pdfa forced on.
	 *
	 * @param string $template Twig template content.
	 * @param PdfAParams $params The collected request parameters.
	 *
	 * @return DataDownloadResponse The PDF download.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function renderPlainPdfA(string $template, array $params): DataDownloadResponse {
		$options = $params['options'];
		$options['pdfa'] = true;

		$pdfContent = $this->pdfService->renderPdf(
			templateContent: $template,
			data: $params['data'],
			options: $options
		);

		return new DataDownloadResponse(
			data: $pdfContent,
			filename: $params['filename'],
			contentType: 'application/pdf'
		);

	}//end renderPlainPdfA()

	/**
	 * Collect and normalise renderPdfA()'s request parameters.
	 *
	 * @return PdfAParams
	 */
	private function collectRenderPdfAParams(): array {
		return [
			'data' => $this->arrayParam(name: 'data'),
			'options' => $this->arrayParam(name: 'options'),
			'metadata' => $this->arrayParam(name: 'metadata'),
			'attachments' => $this->arrayParam(name: 'attachments'),
			'filename' => (string)$this->request->getParam('filename', 'document.pdf'),
		];

	}//end collectRenderPdfAParams()

	/**
	 * Map a Pdfa3ConversionException to its typed JSON error response.
	 *
	 * @param Pdfa3ConversionException $exception The caught exception.
	 *
	 * @return JSONResponse
	 */
	private function mapPdfa3ExceptionToResponse(Pdfa3ConversionException $exception): JSONResponse {
		$this->logger->warning(
			message: 'PDF/A-3 generation failed: ' . $exception->getMessage(),
			context: ['reason' => $exception->getReason()]
		);

		return new JSONResponse(
			data: [
				'error' => $exception->getMessage(),
				'reason' => $exception->getReason(),
				'adminHint' => $exception->getAdminHint(),
			],
			statusCode: $this->clampStatusCode(code: $exception->getCode())
		);

	}//end mapPdfa3ExceptionToResponse()

	/**
	 * Map a generic Exception to a JSON error response.
	 *
	 * @param Exception $exception The caught exception.
	 *
	 * @return JSONResponse
	 */
	private function mapGenericExceptionToResponse(Exception $exception): JSONResponse {
		$this->logger->error(
			message: 'PDF/A generation failed: ' . $exception->getMessage(),
			context: ['exception' => $exception]
		);

		return new JSONResponse(
			data: ['error' => $exception->getMessage()],
			statusCode: $this->clampStatusCode(code: $exception->getCode())
		);

	}//end mapGenericExceptionToResponse()

	/**
	 * Clamp an exception code to a valid HTTP status range, defaulting
	 * to 500 for anything outside [400, 599].
	 *
	 * @param int $code Candidate status code.
	 *
	 * @return int Valid HTTP status code.
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 */
	private function clampStatusCode(int $code): int {
		if ($code >= 400 && $code < 600) {
			return $code;
		}

		return Http::STATUS_INTERNAL_SERVER_ERROR;
	}//end clampStatusCode()

	/**
	 * Render the archival PDF/A-3 path: render the Twig template to
	 * HTML, delegate to Pdfa3ConversionService::convertHtml() for the
	 * full PDF/A-3 assembly (metadata XMP + attachments), and surface
	 * the composition metadata as response headers.
	 *
	 * @param string $template Twig template content.
	 * @param PdfAParams $params The collected request parameters.
	 *
	 * @return DataDownloadResponse
	 *
	 * @throws \OCA\Filinq\Exception\Pdfa3ConversionException Propagated to renderPdfA()'s catch block.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	private function renderArchivalPdfA(string $template, array $params): DataDownloadResponse {
		$html = $this->pdfService->renderTemplateToHtml(templateContent: $template, data: $params['data']);
		$result = $this->pdfa3Service->convertHtml(
			html: $html,
			metadata: $params['metadata'],
			attachments: $params['attachments'],
			options: $params['options']
		);

		$response = new DataDownloadResponse(
			data: $result['content'],
			filename: $params['filename'],
			contentType: 'application/pdf'
		);
		$response->addHeader('X-Docudesk-Pdfa3-Checksum-Sha256', $result['checksumSha256']);
		$response->addHeader('X-Docudesk-Pdfa3-Pages', (string)$result['pages']);
		$response->addHeader('X-Docudesk-Pdfa3-Conformance', $result['conformance']);
		$response->addHeader('X-Docudesk-Pdfa3-Verified', (string)($result['verified'] ?? 'skipped'));

		return $response;
	}//end renderArchivalPdfA()
}//end class
