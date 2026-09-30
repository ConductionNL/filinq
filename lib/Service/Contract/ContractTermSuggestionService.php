<?php

/**
 * Contract term suggestions
 *
 * Reads a contract's documents and stores what it finds as proposed key
 * terms on the contract. It never writes a contract field: a person accepts
 * or rejects each proposal (ContractService::decideSuggestion). Switched off
 * with the app setting `enable_contract_term_extraction` = "0".
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use OCP\IAppConfig;

/**
 * Proposes key terms from a contract's documents.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-key-term-extraction-is-suggestion-only-req-ddclm-005
 */
class ContractTermSuggestionService {

	public const TOGGLE = 'enable_contract_term_extraction';

	/**
	 * Constructor.
	 *
	 * @param ContractRepository    $repository The contract store.
	 * @param ContractDocumentText  $text       Reads a document's text.
	 * @param IAppConfig            $appConfig  The app settings.
	 * @param ContractTermExtractor $extractor  The local patterns.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContractRepository $repository,
		private readonly ContractDocumentText $text,
		private readonly IAppConfig $appConfig,
		private readonly ContractTermExtractor $extractor = new ContractTermExtractor(),
	) {

	}//end __construct()

	/**
	 * Whether extraction is switched on (default on).
	 *
	 * @return bool True when it runs.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-2
	 */
	public function isEnabled(): bool {
		return $this->appConfig->getValueString('filinq', self::TOGGLE, '1') === '1';

	}//end isEnabled()

	/**
	 * Read the contract's documents and add what they propose. A term already
	 * suggested with the same value is not suggested again; a decided
	 * suggestion stays as it is. Contract fields are never touched.
	 *
	 * @param array<string, mixed> $contract The contract (from ContractService::requireReadable()).
	 * @param string               $userId   The caller, whose files are read.
	 *
	 * @return array{contract: array<string, mixed>, added: int, enabled: bool} The stored contract and how many were added.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-2
	 */
	public function suggest(array $contract, string $userId): array {
		if ($this->isEnabled() === false) {
			return ['contract' => $contract, 'added' => 0, 'enabled' => false];
		}

		$suggestions = array_values((array) ($contract['keyTermSuggestions'] ?? []));
		$known = [];
		foreach ($suggestions as $existing) {
			$known[($existing['field'] ?? '') . '|' . ($existing['value'] ?? '')] = true;
		}

		$added = 0;
		foreach ((array) ($contract['documents'] ?? []) as $reference) {
			if (ctype_digit((string) $reference) === false) {
				continue;
			}

			$found = $this->extractor->extract(text: $this->text->textOf(userId: $userId, fileId: (int) $reference));
			foreach ($found as $term) {
				$key = $term['field'] . '|' . $term['value'];
				if (isset($known[$key]) === true) {
					continue;
				}

				$known[$key] = true;
				$suggestions[] = $term + ['source' => 'file:' . $reference, 'status' => 'proposed'];
				$added++;
			}
		}

		if ($added === 0) {
			return ['contract' => $contract, 'added' => 0, 'enabled' => true];
		}

		$uuid = (string) ($contract['uuid'] ?? '');
		$contract['keyTermSuggestions'] = $suggestions;

		return ['contract' => $this->repository->save(contract: $contract, uuid: $uuid), 'added' => $added, 'enabled' => true];

	}//end suggest()
}//end class
