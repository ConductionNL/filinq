<?php

/**
 * Final Document Controller
 *
 * The HTTP surface of a final document: read its state, make it final, correct
 * it by superseding, unfreeze it with a reason, and store or apply a consuming
 * app's declaration of which record-type states make a document final.
 *
 * Every access resolves through the requesting user's own folder inside the
 * services, so an inaccessible document answers 404 without disclosing that it
 * exists (ADR-005).
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\DocumentFinalityRuleService;
use OCA\Filinq\Service\FinalDocumentCorrectionService;
use OCA\Filinq\Service\FinalDocumentFailureMapper;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\FinalDocumentUnfreezeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for the final state of a document.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class FinalDocumentController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param FinalDocumentService $finalDocuments The finalisation service.
	 * @param FinalDocumentCorrectionService $corrections The correction service.
	 * @param FinalDocumentUnfreezeService $unfreeze The recorded exception.
	 * @param DocumentFinalityRuleService $rules The declaration store.
	 * @param FinalDocumentFailureMapper $failures Failure shaping shared by every method here.
	 * @param IUserSession $userSession The current user session.
	 * @param IL10N $l10n Localisation.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly FinalDocumentService $finalDocuments,
		private readonly FinalDocumentCorrectionService $corrections,
		private readonly FinalDocumentUnfreezeService $unfreeze,
		private readonly DocumentFinalityRuleService $rules,
		private readonly FinalDocumentFailureMapper $failures,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Read the final state of a document, its chain and its checksum.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse The state, or a localised error.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$chain = $this->corrections->chainFor(fileId: $fileId);
			$version = $chain['version'];

			return new JSONResponse(
				data: [
					'final' => ($version !== null && $this->finalDocuments->isFinal(record: $version) === true),
					'version' => $version,
					'supersedes' => $chain['supersedes'],
					'supersededBy' => $chain['supersededBy'],
					'checksum' => $this->finalDocuments->verifyChecksum(fileId: $fileId),
					'mayUnfreeze' => $this->unfreeze->mayUnfreeze(),
				],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(exception: $e, fallback: $this->l10n->t('Could not read the final state of this document'));
		}//end try

	}//end show()

	/**
	 * Make a document's current version final.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse The stored final version, or a localised error.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function finalise(int $fileId): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		$reason = trim((string)$this->request->getParam('reason', ''));
		if ($reason === '') {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Say why this document is final. The reason is part of the record.')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			return new JSONResponse(
				data: ['version' => $this->finalDocuments->finalise(fileId: $fileId, reason: $reason)],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(exception: $e, fallback: $this->l10n->t('Could not make this document final'));
		}

	}//end finalise()

	/**
	 * Correct a final document by superseding it.
	 *
	 * @param int $fileId The Nextcloud file id of the final document.
	 *
	 * @return JSONResponse The correction, or a localised error.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function correct(int $fileId): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		$reason = trim((string)$this->request->getParam('reason', ''));
		if ($reason === '') {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Say what the correction changes. The reason is part of the record.')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			return new JSONResponse(
				data: $this->corrections->correct(fileId: $fileId, reason: $reason),
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(exception: $e, fallback: $this->l10n->t('Could not issue a correction'));
		}

	}//end correct()

	/**
	 * Unfreeze a final version, with a reason, leaving a permanent mark.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse The unfrozen version, or a localised error.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function unfreeze(int $fileId): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		if ($this->unfreeze->mayUnfreeze() === false) {
			return new JSONResponse(
				data: [
					'error' => $this->l10n->t(
						'Unfreezing a final document needs the final-document administrator right.'
					),
				],
				statusCode: Http::STATUS_FORBIDDEN
			);
		}

		$reason = trim((string)$this->request->getParam('reason', ''));
		if ($reason === '') {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Say why you are unfreezing this document. The reason stays on the record.')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			return new JSONResponse(
				data: ['version' => $this->unfreeze->unfreeze(fileId: $fileId, reason: $reason)],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(exception: $e, fallback: $this->l10n->t('Could not unfreeze this document'));
		}

	}//end unfreeze()

	/**
	 * Store a consuming app's declaration of which states make a document final.
	 *
	 * @return JSONResponse The stored declaration, or a localised error.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function declareRule(): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		if ($this->unfreeze->mayUnfreeze() === false) {
			return new JSONResponse(
				data: [
					'error' => $this->l10n->t(
						'Declaring which states make a document final needs the final-document administrator right.'
					),
				],
				statusCode: Http::STATUS_FORBIDDEN
			);
		}

		$states = $this->request->getParam('finalStates', []);
		if (is_array($states) === false) {
			$states = [];
		}

		$user      = $this->userSession->getUser();
		$declaredBy = null;
		if ($user !== null) {
			$declaredBy = $user->getUID();
		}

		try {
			return new JSONResponse(
				data: [
					'rule' => $this->rules->declareRule(
						declaringApp: (string)$this->request->getParam('declaringApp', ''),
						typeReference: (string)$this->request->getParam('typeReference', ''),
						finalStates: $states,
						documentRole: (string)$this->request->getParam('documentRole', ''),
						reasonTemplate: (string)$this->request->getParam('reasonTemplate', ''),
						declaredBy: $declaredBy
					),
				],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(exception: $e, fallback: $this->l10n->t('Could not store the declaration'));
		}

	}//end declareRule()

	/**
	 * Apply a consuming app's state change to that record's documents.
	 *
	 * @return JSONResponse The versions this call made final, or a localised error.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function applyStateChange(): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		$fileIds = $this->request->getParam('fileIds', []);
		if (is_array($fileIds) === false) {
			$fileIds = [];
		}

		try {
			$finalised = $this->rules->applyStateChange(
				declaringApp: (string)$this->request->getParam('declaringApp', ''),
				typeReference: (string)$this->request->getParam('typeReference', ''),
				state: (string)$this->request->getParam('state', ''),
				fileIds: array_map('intval', $fileIds),
				documentRole: (string)$this->request->getParam('documentRole', '')
			);

			return new JSONResponse(
				data: ['finalised' => $finalised, 'count' => count($finalised)],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(exception: $e, fallback: $this->l10n->t('Could not apply the state change'));
		}

	}//end applyStateChange()

	/**
	 * Refuse an unauthenticated caller, or let the request through.
	 *
	 * @return JSONResponse|null The refusal, or null when there is a user.
	 *
	 * @spec exclude Authentication precondition shared by every method here.
	 */
	private function requireUser(): ?JSONResponse {
		if ($this->userSession->getUser() !== null) {
			return null;
		}

		return new JSONResponse(
			data: ['error' => $this->l10n->t('Not authenticated')],
			statusCode: Http::STATUS_UNAUTHORIZED
		);

	}//end requireUser()

	/**
	 * Translate a service failure into the response the caller sees.
	 *
	 * A refusal because the document is final is a 409, not a 500: the caller
	 * asked for something the document's state does not allow, and the sentence
	 * it carries is the one to show. Anything else is logged and reported
	 * generically, so a failure never leaks paths or identities.
	 *
	 * @param Throwable $exception The failure.
	 * @param string $fallback The localised message for an unrecognised failure.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec exclude Error shaping shared by every method here; ADR-105.
	 */
	private function failure(Throwable $exception, string $fallback): JSONResponse {
		$shape = $this->failures->shape(exception: $exception, fallback: $fallback);

		return new JSONResponse(data: $shape['body'], statusCode: $shape['status']);

	}//end failure()
}//end class
