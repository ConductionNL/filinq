<?php

/**
 * Report Render Controller
 *
 * The external, slug-addressed render contract other Conduction apps call
 * (learniq's `ReportCardPdfDelegationService` today) to turn ad-hoc data
 * into a stored, school-styled PDF. Server-to-server: authenticated via a
 * static shared-secret bearer token (`filinq.report_render_api_token`),
 * never an interactive Nextcloud session — mirroring
 * {@see PortalSigningReceiverController}'s server-to-server shape, with a
 * static secret instead of a signed per-caller assertion because there is
 * exactly one caller identity to check here.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\ReportRenderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for the external documents/render and documents/render/batch
 * endpoints.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 */
class ReportRenderController extends Controller {

	/**
	 * App-config key holding the shared-secret bearer token external
	 * callers must present.
	 *
	 * @var string
	 */
	private const CFG_API_TOKEN = 'report_render_api_token';

	/**
	 * Constructor for ReportRenderController.
	 *
	 * @param string $appName The application name.
	 * @param IRequest $request The request object.
	 * @param ReportRenderService $renderService Slug resolution + render + store.
	 * @param IAppConfig $appConfig App-config reader for the shared-secret token.
	 * @param LoggerInterface $logger Logger for rejected/failed calls.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ReportRenderService $renderService,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * POST /api/v1/documents/render
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-single-document-render-by-template-slug-req-rra-01
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 60, period: 60)]
	public function render(): JSONResponse {
		$unauthorised = $this->requireValidToken();
		if ($unauthorised instanceof JSONResponse) {
			return $unauthorised;
		}

		$templateSlug = (string)$this->request->getParam('templateSlug', '');
		if ($templateSlug === '') {
			return new JSONResponse(
				data: ['error' => 'templateSlug is required'],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		$data = $this->request->getParam('data', []);
		if (is_array($data) === false) {
			$data = [];
		}

		try {
			$result = $this->renderService->render(
				templateSlug: $templateSlug,
				tenantId: $this->resolveTenantId(),
				data: $data,
				options: $this->resolveOptions()
			);

			return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
		} catch (Exception $e) {
			return $this->exceptionResponse(exception: $e);
		}//end try

	}//end render()

	/**
	 * POST /api/v1/documents/render/batch
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-batch-render-and-zip-req-rra-03
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 20, period: 60)]
	public function renderBatch(): JSONResponse {
		$unauthorised = $this->requireValidToken();
		if ($unauthorised instanceof JSONResponse) {
			return $unauthorised;
		}

		$templateSlug = (string)$this->request->getParam('templateSlug', '');
		if ($templateSlug === '') {
			return new JSONResponse(
				data: ['error' => 'templateSlug is required'],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		$items = $this->request->getParam('items', []);
		if (is_array($items) === false || empty($items) === true) {
			return new JSONResponse(
				data: ['error' => 'items is required and must be a non-empty array'],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$result = $this->renderService->renderBatch(
				templateSlug: $templateSlug,
				tenantId: $this->resolveTenantId(),
				items: $items,
				options: $this->resolveOptions()
			);

			return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
		} catch (Exception $e) {
			return $this->exceptionResponse(exception: $e);
		}//end try

	}//end renderBatch()

	/**
	 * Parse the optional `tenantId` request parameter.
	 *
	 * @return string|null The tenant id, or null when absent/empty/non-string.
	 */
	private function resolveTenantId(): ?string {
		$raw = $this->request->getParam('tenantId');
		if (is_string($raw) === true && $raw !== '') {
			return $raw;
		}

		return null;
	}//end resolveTenantId()

	/**
	 * Parse the optional `options` request parameter, folding in `userId`
	 * when the caller supplied one at the top level.
	 *
	 * @return array<string, mixed> The options array.
	 */
	private function resolveOptions(): array {
		$options = $this->request->getParam('options', []);
		if (is_array($options) === false) {
			$options = [];
		}

		$userId = $this->request->getParam('userId');
		if ($userId !== null) {
			$options['userId'] = $userId;
		}

		return $options;
	}//end resolveOptions()

	/**
	 * Verify the caller's bearer token against the configured shared secret.
	 *
	 * Fails closed: an unconfigured token rejects every request rather than
	 * silently accepting all callers.
	 *
	 * @return JSONResponse|null A 401 response when the token is missing,
	 *                           mismatched, or unconfigured; null when valid.
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-shared-secret-authentication-req-rra-00
	 */
	private function requireValidToken(): ?JSONResponse {
		$configured = $this->appConfig->getValueString(
			app: 'filinq',
			key: self::CFG_API_TOKEN,
			default: ''
		);

		$header = (string)$this->request->getHeader('Authorization');
		$presented = '';
		if (str_starts_with(haystack: $header, needle: 'Bearer ') === true) {
			$presented = substr($header, 7);
		}

		if ($configured === '' || $presented === '' || hash_equals($configured, $presented) === false) {
			$this->logger->warning(message: '[ReportRenderController] Rejected render request: missing or invalid bearer token.');

			return new JSONResponse(
				data: ['error' => 'Unauthorized'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		return null;
	}//end requireValidToken()

	/**
	 * Map a service-layer Exception to a JSON error response, using the
	 * exception's own code when it is a valid HTTP status.
	 *
	 * @param Exception $exception The caught exception.
	 *
	 * @return JSONResponse
	 */
	private function exceptionResponse(Exception $exception): JSONResponse {
		$code = $exception->getCode();
		$status = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($code >= 400 && $code < 600) {
			$status = $code;
		}

		if ($status === Http::STATUS_INTERNAL_SERVER_ERROR) {
			$this->logger->error(
				message: '[ReportRenderController] Render failed: {msg}',
				context: ['msg' => $exception->getMessage(), 'exception' => $exception]
			);
		}

		return new JSONResponse(
			data: ['error' => $exception->getMessage()],
			statusCode: $status
		);
	}//end exceptionResponse()
}//end class
