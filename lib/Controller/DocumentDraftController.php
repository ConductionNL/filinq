<?php

/**
 * Document drafts: open a generation for editing, and finish it.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Controller\Support\ControllerFeedback;
use OCA\Filinq\Exception\ErrorDetailsInterface;
use OCA\Filinq\Service\Draft\DocumentDraftService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Two routes: create a draft, finish a draft. Saving an edit and discarding a
 * draft are plain object writes through OpenRegister's API (design D5).
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.1
 */
class DocumentDraftController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Download extension and media type per binary format.
	 */
	private const DOWNLOAD_TYPES = [
		'pdf' => ['.pdf', 'application/pdf'],
		'odf' => ['.odt', 'application/vnd.oasis.opendocument.text'],
		'docx' => ['.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
	];

	/**
	 * Constructor.
	 *
	 * @param string               $appName     The app name.
	 * @param IRequest             $request     The request.
	 * @param DocumentDraftService $drafts      Creates and finishes drafts.
	 * @param IUserSession         $userSession The session.
	 * @param ControllerFeedback   $feedback    Translations and the logger.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly DocumentDraftService $drafts,
		private readonly IUserSession $userSession,
		private readonly ControllerFeedback $feedback,
	) {
		parent::__construct(appName: $appName, request: $request);
	}//end __construct()

	/**
	 * Render a generation and save it as the session user's draft.
	 *
	 * POST /api/documents/drafts with templateId, dataRefs, options and origin
	 * (correspondence, document or wizard). Nothing is filed or logged.
	 *
	 * @return JSONResponse 201 with the draft id, stage, html, warnings and expiry;
	 *                      409 for a template with a plain-language version.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.1
	 *
	 * @no-admin-idor-exempt the draft is created for the session user, and the
	 * data it renders is read under OpenRegister's RBAC like the preview route.
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: $this->feedback->l10n->t('Not authenticated'));
		}

		$templateId = (string) $this->request->getParam('templateId', '');
		if ($templateId === '') {
			return new JSONResponse(
				data: ['error' => $this->feedback->l10n->t('templateId is required')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$draft = $this->drafts->create(
				templateId: $templateId,
				dataRefs: $this->arrayParam(name: 'dataRefs'),
				options: $this->arrayParam(name: 'options'),
				origin: (string) $this->request->getParam('origin', 'document')
			);

			return new JSONResponse(data: $draft, statusCode: Http::STATUS_CREATED);
		} catch (Exception $e) {
			return $this->failure(exception: $e);
		}
	}//end create()

	/**
	 * Produce, file and log a draft's edited text and close the draft.
	 *
	 * POST /api/documents/drafts/{id}/finish. Answers like a direct generation:
	 * the document as a download, JSON for HTML or for output mode `files`.
	 *
	 * @param string $id The draft.
	 *
	 * @return DataDownloadResponse|JSONResponse The document, or 404 for a draft that is not the user's.
	 *
	 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-2.3
	 */
	#[NoAdminRequired]
	public function finish(string $id): DataDownloadResponse|JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: $this->feedback->l10n->t('Not authenticated'));
		}

		try {
			$result = $this->drafts->finish(draftId: $id, userId: $user->getUID());
		} catch (Exception $e) {
			return $this->failure(exception: $e);
		}

		return $this->documentResponse(result: $result);
	}//end finish()

	/**
	 * The answer for a finished draft.
	 *
	 * @param array<string, mixed> $result The generation result.
	 *
	 * @return DataDownloadResponse|JSONResponse The document or its refs.
	 */
	private function documentResponse(array $result): DataDownloadResponse|JSONResponse {
		$output = (array) ($result['output'] ?? []);
		$format = (string) ($result['format'] ?? 'pdf');
		$refs   = ['draftId' => $result['draftId'], 'recordId' => $result['recordId'], 'format' => $format, 'warnings' => $result['warnings'] ?? []];
		if (($output['mode'] ?? 'return') === 'files') {
			return new JSONResponse(
				data: $refs + ['fileId' => $output['fileId'] ?? null, 'path' => $output['path'] ?? null, 'name' => $output['name'] ?? null],
				statusCode: Http::STATUS_OK
			);
		}

		if ($format === 'html') {
			return new JSONResponse(data: $refs + ['content' => $result['content']], statusCode: Http::STATUS_OK);
		}

		[$extension, $contentType] = (self::DOWNLOAD_TYPES[$format] ?? self::DOWNLOAD_TYPES['pdf']);
		$response = new DataDownloadResponse(data: $result['content'], filename: 'document' . $extension, contentType: $contentType);
		$response->addHeader('X-Filinq-Draft-Id', (string) $result['draftId']);
		$response->addHeader('X-Filinq-Record-Id', (string) $result['recordId']);

		return $response;
	}//end documentResponse()

	/**
	 * The answer for a failure: the exception's HTTP code when it carries one, else 500.
	 *
	 * @param Exception $exception The failure.
	 *
	 * @return JSONResponse The error.
	 */
	private function failure(Exception $exception): JSONResponse {
		$status = Http::STATUS_INTERNAL_SERVER_ERROR;
		$code   = $exception->getCode();
		if ($code >= 400 && $code < 600) {
			$status = $code;
		}

		$message = $exception->getMessage();
		if ($status === Http::STATUS_INTERNAL_SERVER_ERROR) {
			$this->feedback->logger->error(message: 'Document draft failed: ' . $message, context: ['exception' => $exception]);
			$message = $this->feedback->l10n->t('The draft could not be processed');
		}

		$data = ['error' => $message];
		if ($exception instanceof ErrorDetailsInterface) {
			$data = array_merge($data, $exception->getErrorDetails());
		}

		return new JSONResponse(data: $data, statusCode: $status);
	}//end failure()

	/**
	 * A request parameter that must be an array.
	 *
	 * @param string $name The parameter.
	 *
	 * @return array<mixed> The value, or [] when it is not an array.
	 */
	private function arrayParam(string $name): array {
		$value = $this->request->getParam($name, []);
		if (is_array($value) === false) {
			return [];
		}

		return $value;
	}//end arrayParam()
}//end class
