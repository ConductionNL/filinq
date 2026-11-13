<?php

/**
 * Extraction Duplicate Lookup
 *
 * Whether an earlier financial extraction holds the same supplier identifier
 * and invoice number, so the same invoice sent twice goes to a person.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Looks up an earlier extraction of the same invoice.
 *
 * The lookup reads other users' extractions on the server and answers only
 * yes or no; it never returns the other extraction (design, risks). When the
 * lookup cannot run it answers yes: an unchecked invoice is not one that can
 * be trusted without a person.
 */
class ExtractionDuplicateLookup {

	/**
	 * The supplier identifiers that name a supplier.
	 */
	public const IDENTIFIERS = ['supplierIban', 'supplierKvk', 'supplierVatId'];

	/**
	 * Candidate rows read per lookup.
	 */
	private const LIMIT = 100;

	/**
	 * Constructor.
	 *
	 * @param SettingsService      $settings  Gives the OpenRegister object service.
	 * @param OpenRegisterResolver $registers Resolves the financialExtraction binding.
	 * @param LoggerInterface      $logger    Logs a lookup that could not run.
	 */
	public function __construct(
		private readonly SettingsService $settings,
		private readonly OpenRegisterResolver $registers,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Whether an earlier extraction has the same supplier identifier and invoice number.
	 *
	 * Without an invoice number or any supplier identifier there is nothing to
	 * match on, and the answer is no.
	 *
	 * @param array<string, mixed> $fields The fields of the new extraction.
	 *
	 * @return bool True when a match exists or the lookup could not run.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public function hasEarlier(array $fields): bool {
		$invoiceNumber = self::normalised(value: ($fields['invoiceNumber'] ?? null));
		$identifiers   = self::identifiersOf(fields: $fields);
		if ($invoiceNumber === '' || $identifiers === []) {
			return false;
		}

		try {
			foreach ($this->candidates(invoiceNumber: (string)$fields['invoiceNumber']) as $row) {
				if (self::matches(row: $row, invoiceNumber: $invoiceNumber, identifiers: $identifiers) === true) {
					return true;
				}
			}
		} catch (Throwable $e) {
			$this->logger->warning('Filinq: the duplicate check of a financial extraction could not run; the extraction goes to review.', ['exception' => $e]);
			return true;
		}

		return false;
	}//end hasEarlier()

	/**
	 * Whether one stored extraction is the same invoice.
	 *
	 * @param array<string, mixed>  $row           A stored extraction.
	 * @param string                $invoiceNumber The normalised invoice number.
	 * @param array<string, string> $identifiers   The normalised identifiers by field.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/specs/financial-document-field-extraction/spec.md#requirement-each-extraction-carries-an-unattended-verdict-with-its-reasons-req-erp-003
	 */
	public static function matches(array $row, string $invoiceNumber, array $identifiers): bool {
		$fields = ($row['fields'] ?? []);
		if (is_array($fields) === false || self::normalised(value: ($fields['invoiceNumber'] ?? null)) !== $invoiceNumber) {
			return false;
		}

		foreach (self::identifiersOf(fields: $fields) as $field => $value) {
			if (($identifiers[$field] ?? null) === $value) {
				return true;
			}
		}

		return false;
	}//end matches()

	/**
	 * The supplier identifiers present in a set of fields, normalised.
	 *
	 * @param array<string, mixed> $fields The fields.
	 *
	 * @return array<string, string> The identifiers by field.
	 */
	private static function identifiersOf(array $fields): array {
		$found = [];
		foreach (self::IDENTIFIERS as $field) {
			$value = self::normalised(value: ($fields[$field] ?? null));
			if ($value !== '') {
				$found[$field] = $value;
			}
		}

		return $found;
	}//end identifiersOf()

	/**
	 * A value compared without case or spaces: "NL91 ABNA 0417 1643 00" is "NL91ABNA0417164300".
	 *
	 * @param mixed $value The value.
	 *
	 * @return string The normalised value, '' for anything that is not text or a number.
	 */
	private static function normalised(mixed $value): string {
		if (is_string($value) === false && is_int($value) === false) {
			return '';
		}

		return strtoupper((string)preg_replace('/\s+/', '', (string)$value));
	}//end normalised()

	/**
	 * Stored extractions that mention the invoice number.
	 *
	 * @param string $invoiceNumber The invoice number as read.
	 *
	 * @return list<array<string, mixed>> The rows.
	 */
	private function candidates(string $invoiceNumber): array {
		$objectService = $this->settings->getObjectService();
		if ($objectService === null) {
			throw new \RuntimeException('OpenRegister is not available.');
		}

		['register' => $register, 'schema' => $schema] = $this->registers->getFinancialExtractionRegisterAndSchema();

		// Past RBAC: the check must see other users' extractions, and it
		// returns only yes or no (docs/authorization-decisions.md).
		$results = $objectService->searchObjects(
			query: [
				'@self' => ['register' => $register, 'schema' => $schema],
				'_search' => $invoiceNumber,
				'_limit' => self::LIMIT,
			],
			_rbac: false,
			_multitenancy: false
		);

		if (is_iterable($results) === false) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$rows[] = self::rowOf(result: $result);
		}

		return $rows;
	}//end candidates()

	/**
	 * One search result as an array.
	 *
	 * @param mixed $result A search result.
	 *
	 * @return array<string, mixed>
	 */
	private static function rowOf(mixed $result): array {
		if (is_array($result) === true) {
			return $result;
		}

		if (is_object($result) === true && method_exists($result, 'getObject') === true) {
			return (array)$result->getObject();
		}

		if ($result instanceof \JsonSerializable) {
			return (array)$result->jsonSerialize();
		}

		return [];
	}//end rowOf()
}//end class
