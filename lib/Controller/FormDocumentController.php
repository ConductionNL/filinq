<?php

/**
 * Form Document Controller
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The two calls a form surface makes to filinq: check the document reference
 * a field holds, and file a submitted form as a document on its record.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Serves `POST api/forms/document-reference` and `POST api/forms/submissions` (REQ-DFT-05).
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 */
class FormDocumentController extends Controller {
	/**
	 * Constructor.
	 *
	 * Both routes are `NoAdminRequired` and not public, so Nextcloud answers
	 * 401 before a request without a session reaches them. Every record is
	 * read through OpenRegister's ObjectService with the caller's rights.
	 *
	 * @param IRequest                     $request The request.
	 * @param FormDocumentFiling          $forms   Checks references and files submitted forms.
	 * @param LoggerInterface              $logger  Logger for diagnostics.
	 * @param IL10N                        $l10n    Translations.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly FormDocumentFiling $forms,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * Check a document reference: `register`, `schema` and `id` in the body.
	 *
	 * @return JSONResponse The reference and the record's title, or the refusal.
	 *
	 * @spec openspec/changes/forms-as-documents/tasks.md#1-build
	 */
	#[NoAdminRequired]
	public function checkReference(): JSONResponse {
		$reference = [
			'register' => $this->request->getParam('register', ''),
			'schema' => $this->request->getParam('schema', ''),
			'id' => $this->request->getParam('id', ''),
		];

		return $this->answer(action: fn (): array => $this->forms->checkReference(reference: $reference));

	}//end checkReference()

	/**
	 * File a submitted form: `templateId`, `record`, `values`, `documentFields`, `filename` and `format` in the body.
	 *
	 * @return JSONResponse 201 with the filed document, or the refusal.
	 *
	 * @spec openspec/changes/forms-as-documents/tasks.md#1-build
	 */
	#[NoAdminRequired]
	public function submit(): JSONResponse {
		$submission = [];
		foreach (['templateId', 'record', 'values', 'documentFields', 'filename', 'format'] as $key) {
			$value = $this->request->getParam($key);
			if ($value !== null) {
				$submission[$key] = $value;
			}
		}

		return $this->answer(action: fn (): array => $this->forms->renderSubmission(submission: $submission), status: Http::STATUS_CREATED);

	}//end submit()

	/**
	 * Run an action and answer its result, a refusal with its own status, or a 500.
	 *
	 * @param callable $action The action.
	 * @param int      $status The status of a success.
	 *
	 * @return JSONResponse The answer.
	 */
	private function answer(callable $action, int $status=Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse(data: $action(), statusCode: $status);
		} catch (FormDocumentRefused $refused) {
			return new JSONResponse(data: ['error' => $this->l10n->t($refused->getMessage())], statusCode: $refused->getCode());
		} catch (Throwable $e) {
			$this->logger->error(message: 'Filinq: form document failed: ' . $e->getMessage(), context: ['exception' => $e]);
			return new JSONResponse(
				data: ['error' => $this->l10n->t('The submitted form could not be filed as a document.')],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end answer()
}//end class
