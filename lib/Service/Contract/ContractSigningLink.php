<?php

/**
 * Contract signing link
 *
 * Records on a contract the signing request sent for one of its documents,
 * and the signed document once that request completed. The request is read
 * through the signing service as the caller, so the signing surface's own
 * access rule (initiator or signer) decides what may be linked.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use InvalidArgumentException;
use OCA\Filinq\Service\SigningService;
use OCP\IUserSession;
use RuntimeException;

/**
 * Links a signing request, and its signed document, to a contract.
 */
class ContractSigningLink {

	/**
	 * Constructor.
	 *
	 * @param ContractRepository $contracts   The contracts.
	 * @param SigningService     $signing     The signing surface, read only.
	 * @param IUserSession       $userSession The caller.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContractRepository $contracts,
		private readonly SigningService $signing,
		private readonly IUserSession $userSession,
	) {

	}//end __construct()

	/**
	 * Record the request on the contract and, when it completed, the signed document.
	 *
	 * @param string $uuid             The contract.
	 * @param string $signingRequestId The signing request sent for it.
	 *
	 * @return array{contract: array<string, mixed>, signingRequest: array<string, mixed>} The contract and what the detail shows of the request.
	 *
	 * @throws ContractNotFoundException When the contract is not there or not readable.
	 * @throws InvalidArgumentException  400 without a request id, 404 when the caller cannot read the request.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
	 */
	public function link(string $uuid, string $signingRequestId): array {
		$signingRequestId = trim($signingRequestId);
		if ($signingRequestId === '') {
			throw new InvalidArgumentException(message: 'signing_request_required', code: 400);
		}

		$contract = $this->contracts->find(uuid: $uuid);
		if ($contract === null) {
			throw new ContractNotFoundException();
		}

		$request = $this->readRequest(requestId: $signingRequestId);
		$updated = $contract;
		$updated['signingRequestRef'] = $signingRequestId;
		$signed = ($request['status'] ?? '') === 'COMPLETED';
		if ($signed === true) {
			$updated['signedDocumentRef'] = $this->signedReference(request: $request);
		}

		if ($updated !== $contract) {
			$updated = $this->contracts->save(contract: $updated, uuid: $uuid);
		}

		return [
			'contract'       => $updated,
			'signingRequest' => [
				'id'           => $signingRequestId,
				'status'       => (string) ($request['status'] ?? ''),
				'documentName' => (string) ($request['documentName'] ?? ''),
				'signed'       => $signed,
			],
		];

	}//end link()

	/**
	 * Read the request as the caller; not found and not allowed read the same.
	 *
	 * @param string $requestId The request.
	 *
	 * @return array<string, mixed> The request.
	 *
	 * @throws InvalidArgumentException 404 when it cannot be read.
	 */
	private function readRequest(string $requestId): array {
		$caller = (string) $this->userSession->getUser()?->getUID();
		try {
			$request = $this->signing->getRequest(requestId: $requestId, callerUserId: $caller);
		} catch (RuntimeException) {
			$request = null;
		}

		if ($request === null) {
			throw new InvalidArgumentException(message: 'signing_request_not_found', code: 404);
		}

		return $request;

	}//end readRequest()

	/**
	 * The signed document of a completed request: the provider's reference,
	 * or the sent file when the provider signed it in place.
	 *
	 * @param array<string, mixed> $request The completed request.
	 *
	 * @return string The reference.
	 */
	private function signedReference(array $request): string {
		$reference = (string) ($request['signedDocumentRef'] ?? '');
		if ($reference !== '') {
			return $reference;
		}

		return (string) ($request['documentFileId'] ?? '');

	}//end signedReference()
}//end class
