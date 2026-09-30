<?php

/**
 * Contract service
 *
 * The three contract actions that are more than a field edit: renew (a
 * successor draft, linked both ways), terminate (with a reason) and deciding
 * on a suggested key term. Every write carries the whole contract forward.
 * Which status moves are allowed is declared on the schema and enforced by
 * OpenRegister; this service does not repeat that rule.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use InvalidArgumentException;

/**
 * Renew, terminate and decide on suggestions.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-contract-status-lifecycle-is-declaratively-guarded-req-ddclm-002
 */
class ContractService {

	/**
	 * The fields a successor contract carries forward from the one it renews.
	 */
	private const CARRIED_FORWARD = ['title', 'contractType', 'parties', 'internalOwner', 'value', 'currency', 'renewalType', 'noticePeriodDays'];

	/**
	 * Constructor.
	 *
	 * @param ContractRepository $repository The contract store.
	 * @param ContractDates      $dates      The notice-deadline rule.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContractRepository $repository,
		private readonly ContractDates $dates = new ContractDates(),
	) {

	}//end __construct()

	/**
	 * Renew an active contract: a successor draft carrying its parties, type,
	 * owner and value, linked both ways, and the original marked renewed.
	 *
	 * @param string $uuid The contract to renew.
	 *
	 * @return array{contract: array<string, mixed>, successor: array<string, mixed>} Both contracts as stored.
	 *
	 * @throws ContractNotFoundException When the caller cannot read the contract.
	 * @throws InvalidArgumentException  When the contract is not active (409).
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function renew(string $uuid): array {
		$contract = $this->activeContract(uuid: $uuid);

		$successor = ['status' => 'draft', 'renews' => $uuid, 'documents' => [], 'keyTermSuggestions' => []];
		foreach (self::CARRIED_FORWARD as $field) {
			if (array_key_exists($field, $contract) === true) {
				$successor[$field] = $contract[$field];
			}
		}

		$storedSuccessor = $this->repository->save(contract: $successor);

		$contract['status'] = 'renewed';
		$contract['renewedBy'] = (string) ($storedSuccessor['uuid'] ?? '');

		return [
			'contract'  => $this->repository->save(contract: $contract, uuid: $uuid),
			'successor' => $storedSuccessor,
		];

	}//end renew()

	/**
	 * End an active contract early. A reason is required and stored.
	 *
	 * @param string $uuid   The contract to terminate.
	 * @param string $reason Why it ends.
	 *
	 * @return array<string, mixed> The stored contract.
	 *
	 * @throws ContractNotFoundException When the caller cannot read the contract.
	 * @throws InvalidArgumentException  When the reason is empty (400) or the contract is not active (409).
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function terminate(string $uuid, string $reason): array {
		$reason = trim($reason);
		if ($reason === '') {
			throw new InvalidArgumentException('A termination needs a reason.', 400);
		}

		$contract = $this->activeContract(uuid: $uuid);
		$contract['status'] = 'terminated';
		$contract['terminationReason'] = mb_substr($reason, 0, 2000);

		return $this->repository->save(contract: $contract, uuid: $uuid);

	}//end terminate()

	/**
	 * Accept or reject one proposed key term. Accepting writes the value into
	 * its field; rejecting only marks the suggestion. A suggestion that was
	 * already decided is refused.
	 *
	 * @param string $uuid   The contract.
	 * @param int    $index  The suggestion's position in `keyTermSuggestions`.
	 * @param bool   $accept True to accept, false to reject.
	 *
	 * @return array<string, mixed> The stored contract.
	 *
	 * @throws ContractNotFoundException When the caller cannot read the contract.
	 * @throws InvalidArgumentException  When there is no such proposed suggestion (404/409) or its value does not fit (422).
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function decideSuggestion(string $uuid, int $index, bool $accept): array {
		$contract = $this->requireReadable(uuid: $uuid);
		$suggestions = (array) ($contract['keyTermSuggestions'] ?? []);
		if (isset($suggestions[$index]) === false || is_array($suggestions[$index]) === false) {
			throw new InvalidArgumentException('No such suggestion.', 404);
		}

		$suggestion = $suggestions[$index];
		if (($suggestion['status'] ?? '') !== 'proposed') {
			throw new InvalidArgumentException('This suggestion was already decided.', 409);
		}

		$suggestion['status'] = 'rejected';
		if ($accept === true) {
			$contract = $this->applySuggestion(contract: $contract, suggestion: $suggestion);
			$suggestion['status'] = 'accepted';
		}

		$suggestions[$index] = $suggestion;
		$contract['keyTermSuggestions'] = array_values($suggestions);

		return $this->repository->save(contract: $this->dates->withNoticeDeadline(contract: $contract), uuid: $uuid);

	}//end decideSuggestion()

	/**
	 * A contract the caller can read.
	 *
	 * @param string $uuid The contract uuid.
	 *
	 * @return array<string, mixed> The contract.
	 *
	 * @throws ContractNotFoundException When it is not found or not readable.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-3
	 */
	public function requireReadable(string $uuid): array {
		$contract = $this->repository->find(uuid: $uuid);
		if ($contract === null) {
			throw new ContractNotFoundException('Contract not found.');
		}

		return $contract;

	}//end contract()

	/**
	 * A contract that is active, or a 409.
	 *
	 * @param string $uuid The contract uuid.
	 *
	 * @return array<string, mixed> The contract.
	 *
	 * @throws ContractNotFoundException When it is not found or not readable.
	 * @throws InvalidArgumentException  When it is not active.
	 */
	private function activeContract(string $uuid): array {
		$contract = $this->requireReadable(uuid: $uuid);
		if (($contract['status'] ?? '') !== 'active') {
			throw new InvalidArgumentException('Only an active contract can be renewed or terminated.', 409);
		}

		return $contract;

	}//end activeContract()

	/**
	 * Write an accepted suggestion's value into the contract.
	 *
	 * @param array<string, mixed>  $contract   The contract.
	 * @param array<string, mixed>  $suggestion The suggestion.
	 *
	 * @return array<string, mixed> The contract with the value written.
	 *
	 * @throws InvalidArgumentException When the value does not fit the field (422).
	 */
	private function applySuggestion(array $contract, array $suggestion): array {
		$field = (string) ($suggestion['field'] ?? '');
		$value = trim((string) ($suggestion['value'] ?? ''));

		switch ($field) {
			case 'startDate':
			case 'endDate':
				if ($this->dates->noticeDeadlineFor(endDate: $value, noticePeriodDays: 0) === null) {
					throw new InvalidArgumentException('The suggested date is not a date.', 422);
				}

				$contract[$field] = $value;
				break;
			case 'noticePeriodDays':
				if (ctype_digit($value) === false) {
					throw new InvalidArgumentException('The suggested notice period is not a number of days.', 422);
				}

				$contract[$field] = (int) $value;
				break;
			case 'value':
				if (is_numeric($value) === false) {
					throw new InvalidArgumentException('The suggested value is not a number.', 422);
				}

				$contract[$field] = (float) $value;
				break;
			case 'currency':
				$contract[$field] = strtoupper($value);
				break;
			case 'party':
				$contract['parties'] = array_merge((array) ($contract['parties'] ?? []), [['displayName' => $value]]);
				break;
			default:
				throw new InvalidArgumentException('Unknown suggestion field.', 422);
		}//end switch

		return $contract;

	}//end applySuggestion()
}//end class
