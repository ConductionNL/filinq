<?php

/**
 * DocuDesk GlAccountSuggestedEvent
 *
 * Public cross-app event DocuDesk dispatches when a GL-account suggestion has
 * been computed for a prior financial extraction. This is a **sibling** event
 * to `nl.conduction.docudesk.extraction.completed` (financial-document-field-
 * extraction) — it is intentionally NOT merged into that shipped contract;
 * see design.md Decision D6. Consumer fleet apps (e.g. shillinq's
 * gl-account-suggestion-consume follow-up) subscribe to this event to surface
 * a booking-account suggestion — DocuDesk owns the ranking only, never the
 * consumer's chart of accounts (ADR-022).
 *
 * @category  Event
 * @package   OCA\DocuDesk\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCP\EventDispatcher\Event;

/**
 * Cross-app completion event: DocuDesk reports a computed GL-account
 * suggestion. Fully immutable — DocuDesk constructs it from the computed
 * suggestion result; consumers only read.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */
class GlAccountSuggestedEvent extends Event
{
    /**
     * Construct the suggestion event.
     *
     * @param string                           $extractionId      The `financialExtraction` object id.
     * @param string|null                      $supplierIdentity  The resolved supplier identity, or null
     *                                                            when unresolvable.
     * @param string|null                      $identityType      `kvk`|`iban`|`name`, or null when
     *                                                            unresolvable.
     * @param array<int, array<string, mixed>> $suggestedAccounts Ranked candidate accounts (each `{code,
     *                                                            label, confidence, rationale}`).
     * @param string                           $source            `history`|`keyword-rule`|`none`.
     * @param string                           $sourceApp         App id of the requester.
     * @param string                           $requestedBy       Nextcloud user id that requested the
     *                                                            suggestion.
     *
     * @return void
     */
    public function __construct(
        private readonly string $extractionId,
        private readonly ?string $supplierIdentity,
        private readonly ?string $identityType,
        private readonly array $suggestedAccounts,
        private readonly string $source,
        private readonly string $sourceApp,
        private readonly string $requestedBy,
    ) {
        parent::__construct();

    }//end __construct()

    /**
     * Get the `financialExtraction` object id.
     *
     * @return string The extraction id.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getExtractionId(): string
    {
        return $this->extractionId;

    }//end getExtractionId()

    /**
     * Get the resolved supplier identity.
     *
     * @return string|null The identity, or null when unresolvable.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getSupplierIdentity(): ?string
    {
        return $this->supplierIdentity;

    }//end getSupplierIdentity()

    /**
     * Get the resolved identity type.
     *
     * @return string|null `kvk`|`iban`|`name`, or null when unresolvable.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getIdentityType(): ?string
    {
        return $this->identityType;

    }//end getIdentityType()

    /**
     * Get the ranked candidate accounts.
     *
     * @return array<int, array<string, mixed>> The ranked candidates (each `{code, label, confidence,
     *         rationale}`).
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getSuggestedAccounts(): array
    {
        return $this->suggestedAccounts;

    }//end getSuggestedAccounts()

    /**
     * Get the suggestion source.
     *
     * @return string `history`|`keyword-rule`|`none`.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getSource(): string
    {
        return $this->source;

    }//end getSource()

    /**
     * Get the requesting app id.
     *
     * @return string The source app id.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getSourceApp(): string
    {
        return $this->sourceApp;

    }//end getSourceApp()

    /**
     * Get the requesting Nextcloud user id.
     *
     * @return string The requesting user id.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function getRequestedBy(): string
    {
        return $this->requestedBy;

    }//end getRequestedBy()

    /**
     * Build the wire payload for `nl.conduction.docudesk.gl-account.suggested`.
     *
     * @return array<string, mixed> The payload shape.
     *
     * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
     */
    public function toPayload(): array
    {
        return [
            'extractionId'      => $this->extractionId,
            'supplierIdentity'  => $this->supplierIdentity,
            'identityType'      => $this->identityType,
            'suggestedAccounts' => $this->suggestedAccounts,
            'source'            => $this->source,
            'sourceApp'         => $this->sourceApp,
            'requestedBy'       => $this->requestedBy,
        ];

    }//end toPayload()
}//end class
