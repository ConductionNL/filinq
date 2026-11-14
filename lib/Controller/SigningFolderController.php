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
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningMandateServiceInterface;
use OCA\Filinq\Service\ReadsCurrentUserId;
use OCA\Filinq\Service\Signing\SigningDelegationService;
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
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
 */
class SigningFolderController extends Controller {
	use ReadsCurrentUserId;

	/**
	 * Constructor.
	 *
	 * @param IRequest $request Request object.
	 * @param SigningFolderServiceInterface $folderService The folder query and the pass.
	 * @param SigningMandateServiceInterface $mandateService The mandate declarations.
	 * @param IUserSession $userSession User session.
	 * @param LoggerInterface $logger Logger.
	 * @param IL10N $l10n Localisation.
	 * @param SigningDelegationService|null $delegations Delegated signing authority (REQ-SCR-003).
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly SigningFolderServiceInterface $folderService,
		private readonly SigningMandateServiceInterface $mandateService,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
		private readonly ?SigningDelegationService $delegations = null,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * The folder for the calling signer.
	 *
	 * @param int $limit Page size.
	 * @param int $offset Page offset.
	 *
	 * @return JSONResponse The folder page.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
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
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
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
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
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
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
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
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
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
	 * The delegations the caller gave and received.
	 *
	 * @return JSONResponse {given: delegation[], received: delegation[]}
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	#[NoAdminRequired]
	public function delegations(): JSONResponse {
		$userId = $this->currentUserId();
		if ($userId === '' || $this->delegations === null) {
			return $this->noDelegations(userId: $userId);
		}

		try {
			return new JSONResponse($this->delegations->forUser(userId: $userId));
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to read the delegations', exception: $e);
		}

	}//end delegations()

	/**
	 * Delegate the caller's signing authority to a colleague for a period and document types.
	 *
	 * @return JSONResponse The stored delegation, 201.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	#[NoAdminRequired]
	public function declareDelegation(): JSONResponse {
		$userId = $this->currentUserId();
		if ($userId === '' || $this->delegations === null) {
			return $this->noDelegations(userId: $userId);
		}

		try {
			$delegation = $this->delegations->declare(
				delegator: $userId,
				delegate: (string) $this->request->getParam('delegate', ''),
				from: (string) $this->request->getParam('from', ''),
				until: (string) $this->request->getParam('until', ''),
				types: (array) $this->request->getParam('types', [])
			);

			return new JSONResponse(data: $delegation, statusCode: Http::STATUS_CREATED);
		} catch (InvalidArgumentException $e) {
			// About the caller's own input; it names no record.
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to record the delegation', exception: $e);
		}

	}//end declareDelegation()

	/**
	 * Withdraw a delegation the caller gave.
	 *
	 * @param string $id The delegation.
	 *
	 * @return JSONResponse {withdrawn: true}, or 404 when the caller gave no such delegation.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	#[NoAdminRequired]
	public function withdrawDelegation(string $id): JSONResponse {
		$userId = $this->currentUserId();
		if ($userId === '' || $this->delegations === null) {
			return $this->noDelegations(userId: $userId);
		}

		try {
			if ($this->delegations->withdraw(id: $id, delegator: $userId) === false) {
				return new JSONResponse(data: ['error' => $this->l10n->t('Delegation not found')], statusCode: Http::STATUS_NOT_FOUND);
			}

			return new JSONResponse(['withdrawn' => true]);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to withdraw the delegation', exception: $e);
		}

	}//end withdrawDelegation()

	/**
	 * The answer without a session, or without the delegation store.
	 *
	 * @param string $userId The caller, '' without a session.
	 *
	 * @return JSONResponse 401 or 503.
	 */
	private function noDelegations(string $userId): JSONResponse {
		if ($userId === '') {
			return new JSONResponse(data: ['error' => $this->l10n->t('Not authenticated')], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		return new JSONResponse(data: ['error' => $this->l10n->t('Delegated signing is not available')], statusCode: Http::STATUS_SERVICE_UNAVAILABLE);

	}//end noDelegations()

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
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function errorResponse(string $message, Exception $exception): JSONResponse {
		$this->logger->error($message . ': ' . $exception->getMessage(), ['exception' => $exception]);

		return new JSONResponse(
			['error' => $this->l10n->t($message)],
			Http::STATUS_INTERNAL_SERVER_ERROR
		);

	}//end errorResponse()
}//end class
