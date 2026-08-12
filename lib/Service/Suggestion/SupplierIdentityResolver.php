<?php

/**
 * Supplier Identity Resolver
 *
 * Pure, side-effect-free helper that resolves a single, stable supplier
 * identity key from a `financialExtraction` object's `fields`, preferring
 * KvK, then IBAN, then a normalised supplier name (REQ-GLS-01). The
 * resolved identity is the grouping key `glAccountBooking` history is
 * stored/queried against.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Suggestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Suggestion;

/**
 * Resolves a supplier identity (KvK > IBAN > normalised name) from
 * extraction fields.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Suggestion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */
class SupplierIdentityResolver {
	/**
	 * Resolve the supplier identity and its type from extraction fields.
	 *
	 * @param array<string, mixed> $fields The `financialExtraction` `fields` map
	 *                                     (`supplierKvk`, `supplierIban`, `supplierName`).
	 *
	 * @return array{identity: string, identityType: string}|null The resolved
	 *                                                            identity, or null when none of the three source fields yield
	 *                                                            a usable value.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function resolve(array $fields): ?array {
		$kvk = trim((string)($fields['supplierKvk'] ?? ''));
		if ($kvk !== '') {
			return ['identity' => $kvk, 'identityType' => 'kvk'];
		}

		$iban = trim((string)($fields['supplierIban'] ?? ''));
		if ($iban !== '') {
			return ['identity' => $iban, 'identityType' => 'iban'];
		}

		$normalisedName = $this->normaliseName(name: (string)($fields['supplierName'] ?? ''));
		if ($normalisedName !== '') {
			return ['identity' => $normalisedName, 'identityType' => 'name'];
		}

		return null;
	}//end resolve()

	/**
	 * Normalise a supplier name for use as a stable grouping key: trimmed,
	 * whitespace-collapsed, lower-cased.
	 *
	 * @param string $name The raw supplier name.
	 *
	 * @return string The normalised name, or '' when the input is blank.
	 */
	private function normaliseName(string $name): string {
		$trimmed = trim($name);
		if ($trimmed === '') {
			return '';
		}

		$collapsed = preg_replace('/\s+/', ' ', $trimmed);
		if ($collapsed === null) {
			$collapsed = $trimmed;
		}

		return mb_strtolower($collapsed);
	}//end normaliseName()
}//end class
