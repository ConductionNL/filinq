<?php

/**
 * Contract of GlAccountSuggestionService
 *
 * The public surface of the GlAccountSuggestionService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use RuntimeException;

/**
 * Contract of GlAccountSuggestionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface GlAccountSuggestionServiceInterface {
	/**
	 * Compute a GL-account suggestion for a prior financial extraction and
	 * dispatch the sibling `nl.conduction.filinq.gl-account.suggested`
	 * event (REQ-GLS-06).
	 *
	 * @param string $extractionId The `financialExtraction` object id.
	 * @param array<int, array<string, mixed>> $candidateAccounts Optional consumer-supplied allow-list
	 *                                                            (each `{code, label?}`).
	 * @param string $sourceApp Requesting app id.
	 * @param string $requestedBy Nextcloud user id that requested the
	 *                            suggestion.
	 *
	 * @return array<string, mixed> `{extractionId, supplierIdentity, identityType, suggestedAccounts, source}`.
	 *
	 * @throws RuntimeException (code 404) When no extraction exists for the given id.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function suggest(string $extractionId, array $candidateAccounts, string $sourceApp, string $requestedBy): array;

	/**
	 * Record a human-chosen GL account as booking history for future
	 * suggestions (REQ-GLS-05). No-op when the extraction has no resolvable
	 * supplier identity — there is no key to group the booking by.
	 *
	 * @param string $extractionId The `financialExtraction` object id.
	 * @param string $accountCode Opaque account code supplied by the consumer.
	 * @param string|null $accountLabel Optional opaque account label.
	 * @param string $correctedBy Nextcloud user id submitting the correction (unused for
	 *                            storage today, reserved for future per-user attribution).
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $correctedBy reserved for future attribution
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function recordBooking(string $extractionId, string $accountCode, ?string $accountLabel, string $correctedBy): void;
}//end interface
