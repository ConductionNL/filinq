<?php

/**
 * Signing Envelope Controller
 *
 * Envelope endpoints: create one over several documents, list, read with its
 * members, sign every member at once, cancel.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\SigningEnvelope\SigningEnvelopeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Every endpoint is for a signed-in user. An envelope is readable by its
 * initiator, a signer it names, or an admin; cancelling is the initiator's or
 * an admin's; signing is a named signer's. Anything else is a 404, so an
 * envelope id cannot be probed.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
class SigningEnvelopeController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                 $appName      App name
	 * @param IRequest               $request      The request
	 * @param SigningEnvelopeService $envelopes    Envelopes
	 * @param IUserSession           $userSession  The caller
	 * @param IGroupManager          $groupManager Admin check
	 * @param LoggerInterface        $logger       Logger
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SigningEnvelopeService $envelopes,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Create an envelope: `documents` [{documentFileId, documentName}], `signers`, level, mode, provider, title.
	 *
	 * @return JSONResponse 201 with the envelope and its members, 400 when the input or a document is refused
	 *
	 * @no-admin-idor-exempt Creates a new envelope owned by the caller; every member goes through the single-request create with its own checks.
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		$uid = $this->callerId();
		$input = [];
		foreach (['title', 'documents', 'signers', 'signatureLevel', 'signingMode', 'provider', 'requiredAssurance', 'deadline'] as $key) {
			$value = $this->request->getParam($key);
			if ($value !== null && $value !== '') {
				$input[$key] = $value;
			}
		}

		return $this->answer(
			operation: fn (): array => $this->envelopes->create(input: $input, userId: $uid),
			status: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * List the envelopes the caller started (every envelope for an admin).
	 *
	 * @return JSONResponse `{results: [...]}`
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): array => ['results' => $this->envelopes->listFor(userId: $uid, isAdmin: $isAdmin)]);

	}//end index()

	/**
	 * One envelope with its members and rolled-up status.
	 *
	 * @param string $id The envelope uuid
	 *
	 * @return JSONResponse The envelope, or 404
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): ?array => $this->envelopes->get(id: $id, userId: $uid, isAdmin: $isAdmin));

	}//end show()

	/**
	 * Sign every member the caller still has to sign.
	 *
	 * @param string $id The envelope uuid
	 *
	 * @return JSONResponse `{envelope, results}`, or 404 when the caller is not a signer of it
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	#[NoAdminRequired]
	public function signAll(string $id): JSONResponse {
		$uid = $this->callerId();

		return $this->answer(operation: fn (): ?array => $this->envelopes->signAll(id: $id, userId: $uid));

	}//end signAll()

	/**
	 * Cancel an envelope and its members that can still be cancelled.
	 *
	 * @param string $id The envelope uuid
	 *
	 * @return JSONResponse The envelope, or 404
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	#[NoAdminRequired]
	public function cancel(string $id): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): ?array => $this->envelopes->cancel(id: $id, userId: $uid, isAdmin: $isAdmin));

	}//end cancel()

	/**
	 * The caller's uid ('' only if the framework let an anonymous call through).
	 *
	 * @return string
	 */
	private function callerId(): string {
		return (string) $this->userSession->getUser()?->getUID();

	}//end callerId()

	/**
	 * Run an operation and map its outcome: null is 404; 400 and 409 carry their message.
	 *
	 * @param callable():(array|null) $operation The operation
	 * @param int                     $status    Success status
	 *
	 * @return JSONResponse
	 */
	private function answer(callable $operation, int $status = Http::STATUS_OK): JSONResponse {
		try {
			$result = $operation();
		} catch (Throwable $e) {
			$code = (int) $e->getCode();
			if (in_array($code, [Http::STATUS_BAD_REQUEST, Http::STATUS_CONFLICT], true) === true) {
				return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $code);
			}

			$this->logger->error('Envelope request failed: ' . $e->getMessage(), ['exception' => $e]);
			return new JSONResponse(data: ['error' => 'Envelope request failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		if ($result === null) {
			return new JSONResponse(data: ['error' => 'Envelope not found'], statusCode: Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse(data: $result, statusCode: $status);

	}//end answer()
}//end class
