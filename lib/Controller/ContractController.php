<?php

/**
 * Contract controller
 *
 * The contract actions that are more than a field edit: renew, terminate,
 * read suggestions from the documents, and accept or reject a suggestion.
 * Creating, reading and editing a contract go through OpenRegister's object
 * API; there is no pass-through here. Every action first reads the contract
 * as the caller, so a contract the caller cannot read answers 404.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Service\Contract\ContractNotFoundException;
use OCA\Filinq\Service\Contract\ContractService;
use OCA\Filinq\Service\Contract\ContractTermSuggestionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Renew, terminate and suggestion routes for contracts.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-renewal-pipeline-view-req-ddclm-006
 */
class ContractController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                        $appName     The app id.
	 * @param IRequest                      $request     The request.
	 * @param ContractService               $contracts   The contract actions.
	 * @param ContractTermSuggestionService $suggestions The key-term suggestions.
	 * @param IUserSession                  $userSession The session.
	 * @param LoggerInterface               $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ContractService $contracts,
		private readonly ContractTermSuggestionService $suggestions,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Renew an active contract into a linked successor draft.
	 *
	 * @param string $id The contract uuid.
	 *
	 * @return JSONResponse {contract, successor}, or 404/409.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
	 */
	#[NoAdminRequired]
	public function renew(string $id): JSONResponse {
		return $this->run(
			id: $id,
			action: fn (): array => $this->contracts->renew(uuid: $id)
		);

	}//end renew()

	/**
	 * Terminate an active contract with a reason.
	 *
	 * @param string $id     The contract uuid.
	 * @param string $reason Why it ends.
	 *
	 * @return JSONResponse The contract, or 400/404/409.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
	 */
	#[NoAdminRequired]
	public function terminate(string $id, string $reason=''): JSONResponse {
		return $this->run(
			id: $id,
			action: fn (): array => $this->contracts->terminate(uuid: $id, reason: $reason)
		);

	}//end terminate()

	/**
	 * Read the contract's documents and add proposed key terms.
	 *
	 * @param string $id The contract uuid.
	 *
	 * @return JSONResponse {contract, added, enabled}, or 404.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
	 */
	#[NoAdminRequired]
	public function suggest(string $id): JSONResponse {
		$userId = (string) $this->userSession->getUser()?->getUID();

		return $this->run(
			id: $id,
			action: fn (): array => $this->suggestions->suggest(contract: $this->contracts->requireReadable(uuid: $id), userId: $userId)
		);

	}//end suggest()

	/**
	 * Accept or reject one proposed key term.
	 *
	 * @param string $id     The contract uuid.
	 * @param int    $index  The suggestion's position.
	 * @param bool   $accept True to accept, false to reject.
	 *
	 * @return JSONResponse The contract, or 404/409/422.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
	 */
	#[NoAdminRequired]
	public function decideSuggestion(string $id, int $index, bool $accept=false): JSONResponse {
		return $this->run(
			id: $id,
			action: fn (): array => $this->contracts->decideSuggestion(uuid: $id, index: $index, accept: $accept)
		);

	}//end decideSuggestion()

	/**
	 * Read the contract as the caller first, then run the action; map the outcome to a status.
	 *
	 * @param string   $id     The contract uuid.
	 * @param callable $action The action, returning the response body.
	 *
	 * @return JSONResponse The response.
	 */
	private function run(string $id, callable $action): JSONResponse {
		try {
			// The per-object guard: OpenRegister reads as the caller, so a
			// contract they may not read is not found, before anything is written.
			$this->contracts->requireReadable(uuid: $id);

			return new JSONResponse(data: $action());
		} catch (ContractNotFoundException) {
			return new JSONResponse(data: ['error' => 'not_found'], statusCode: Http::STATUS_NOT_FOUND);
		} catch (InvalidArgumentException $e) {
			$status = $e->getCode();
			if (in_array($status, [400, 404, 409, 422], true) === false) {
				$status = Http::STATUS_BAD_REQUEST;
			}

			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $status);
		} catch (Throwable $e) {
			$this->logger->error('Contract action failed', ['id' => $id, 'exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end run()
}//end class
