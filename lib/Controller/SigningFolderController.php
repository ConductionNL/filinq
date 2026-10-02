<?php

/**
 * Signing Folder Controller
 *
 * The folder a signer opens, the pass that signs a selection from it, and the
 * mandate declarations an administrator records on a consuming app's behalf.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Service\SigningFolderService;
use OCA\Filinq\Service\SigningMandateService;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for the signing folder.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */
class SigningFolderController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName App name.
	 * @param IRequest $request Request object.
	 * @param SigningFolderService $folderService The folder query and the pass.
	 * @param SigningMandateService $mandateService The mandate declarations.
	 * @param IUserSession $userSession User session.
	 * @param LoggerInterface $logger Logger.
	 * @param IL10N $l10n Localisation.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SigningFolderService $folderService,
		private readonly SigningMandateService $mandateService,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The folder for the calling signer.
	 *
	 * @param int $limit Page size.
	 * @param int $offset Page offset.
	 *
	 * @return JSONResponse The folder page.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	#[NoAdminRequired]
	public function folder(int $limit = 50, int $offset = 0): JSONResponse {
		$userId = $this->currentUserId();
		if ($userId === '') {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		try {
			return new JSONResponse($this->folderService->folder(userId: $userId, limit: $limit, offset: $offset));
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to read the signing folder', exception: $e);
		}

	}//end folder()

	/**
	 * Sign a selection from the folder.
	 *
	 * @return JSONResponse A result per selected document.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	#[NoAdminRequired]
	public function signFolder(): JSONResponse {
		$userId = $this->currentUserId();
		if ($userId === '') {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$requestIds = $this->request->getParam('requestIds', []);
		if (is_array($requestIds) === false || $requestIds === []) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Select at least one document to sign')],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			return new JSONResponse(
				$this->folderService->signSelection(requestIds: $requestIds, userId: $userId)
			);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to sign the selection', exception: $e);
		}

	}//end signFolder()

	/**
	 * The mandate declarations on this instance.
	 *
	 * @return JSONResponse The declarations, keyed by type reference.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function mandates(): JSONResponse {
		try {
			return new JSONResponse(['mandates' => $this->mandateService->declarations()]);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to read the mandate declarations', exception: $e);
		}

	}//end mandates()

	/**
	 * Record a consuming app's mandate for one of its record types.
	 *
	 * @return JSONResponse The stored declaration.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function declareMandate(): JSONResponse {
		$typeReference = (string)$this->request->getParam('typeReference', '');
		$groups = $this->request->getParam('groups', []);
		$rule = (string)$this->request->getParam('rule', '');

		try {
			$declaration = $this->mandateService->declareMandate(
				typeReference: $typeReference,
				groups: (array)$groups,
				rule: $rule
			);

			return new JSONResponse(['typeReference' => $typeReference, 'mandate' => $declaration]);
		} catch (InvalidArgumentException $e) {
			// The message here is about the caller's own input, names no
			// record and cannot probe for one, so it is returned verbatim.
			return new JSONResponse(
				data: ['error' => $e->getMessage()],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to record the mandate', exception: $e);
		}//end try

	}//end declareMandate()

	/**
	 * Withdraw the mandate declared for one record type.
	 *
	 * @param string $typeApp The consuming app half of the type reference.
	 * @param string $typeSchema The schema half of the type reference.
	 *
	 * @return JSONResponse Whether a declaration was withdrawn.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function withdrawMandate(string $typeApp, string $typeSchema): JSONResponse {
		try {
			$withdrawn = $this->mandateService->withdrawMandate($typeApp . '/' . $typeSchema);

			return new JSONResponse(['withdrawn' => $withdrawn]);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to withdraw the mandate', exception: $e);
		}

	}//end withdrawMandate()

	/**
	 * The calling user, or '' when there is none.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function currentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end currentUserId()

	/**
	 * One generic error body, with the detail in the log.
	 *
	 * The signing endpoints deliberately never return an exception message:
	 * distinct text confirms whether a request id exists even when the status
	 * code does not (filinq#100). The folder keeps that contract.
	 *
	 * @param string $message The generic message.
	 * @param Exception $exception The exception, logged and not returned.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function errorResponse(string $message, Exception $exception): JSONResponse {
		$this->logger->error($message . ': ' . $exception->getMessage(), ['exception' => $exception]);

		return new JSONResponse(
			['error' => $this->l10n->t($message)],
			Http::STATUS_INTERNAL_SERVER_ERROR
		);

	}//end errorResponse()
}//end class
