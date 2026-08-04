<?php

/**
 * DocuDesk SigningConcludedEvent Factory
 *
 * Maps a persisted signing-request array onto the public cross-app
 * SigningConcludedEvent. Extracted from the event's former `fromRequest()`
 * named constructor so the mapping is an injectable collaborator with its own
 * tests, and SigningConcludedEvent stays what its docblock claims it is: a
 * fully immutable value object that consumers only read.
 *
 * @category  Event
 * @package   OCA\DocuDesk\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

/**
 * Builds a SigningConcludedEvent from a persisted signing-request array.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 */
class SigningConcludedEventFactory
{
    /**
     * Build a conclusion event from a persisted signing-request array.
     *
     * The request array already carries the provenance fields (persisted by
     * SigningService::createRequest from the request event) and the signers;
     * the status is the normalised terminal vocabulary value supplied by the
     * caller.
     *
     * @param array<string, mixed> $request           Persisted signing-request object array
     * @param string               $status            Normalised status (signed|declined|expired|cancelled)
     * @param string|null          $signedDocumentRef Reference to the signed document, when signed
     *
     * @return SigningConcludedEvent The mapped conclusion event.
     *
     * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
     */
    public function create(
        array $request,
        string $status,
        ?string $signedDocumentRef=null
    ): SigningConcludedEvent {
        return new SigningConcludedEvent(
            signingRequestId: (string) ($request['id'] ?? $request['uuid'] ?? ''),
            status: $status,
            signedDocumentRef: $signedDocumentRef,
            signers: (array) ($request['signerIds'] ?? []),
            signedAt: ($request['signedAt'] ?? null),
            provenance: new SigningProvenance(
                sourceApp: (string) ($request['sourceApp'] ?? ''),
                subjectRegister: ($request['subjectRegister'] ?? null),
                subjectSchema: ($request['subjectSchema'] ?? null),
                subjectId: ($request['subjectId'] ?? null),
                externalReference: (string) ($request['externalReference'] ?? ''),
                correlationId: (string) ($request['correlationId'] ?? ''),
            ),
            assuranceLevel: $this->resolveAssuranceLevel(request: $request),
        );

    }//end create()

    /**
     * Resolve the eIDAS assurance level for the completion payload
     * (signing-trust-rebuild REQ-DDSTR-010).
     *
     * Conservative-by-construction: the native provider only ever produces SES
     * artifacts (`NativeSigningProvider::supportsLevel()`), so a native/SES
     * request always resolves `low`. Any level this map does not explicitly
     * recognise falls back to `low` rather than over-claiming an assurance the
     * request cannot actually evidence. `signer-identity-rails` is expected to
     * populate a broker-resolved, higher assurance into this SAME field for a
     * request whose provider actually supports it — this mapping never needs
     * to change for that, only the request's own `provider`/`signatureLevel`
     * do.
     *
     * @param array<string, mixed> $request The persisted signing-request array.
     *
     * @return string One of low|substantial|high.
     */
    private function resolveAssuranceLevel(array $request): string
    {
        $provider = (string) ($request['provider'] ?? 'native');
        $level    = (string) ($request['signatureLevel'] ?? 'SES');

        if ($provider === 'native') {
            // The native provider only ever produces SES — never claim more.
            return 'low';
        }

        return match ($level) {
            'QES' => 'high',
            'AdES' => 'substantial',
            default => 'low',
        };

    }//end resolveAssuranceLevel()
}//end class
