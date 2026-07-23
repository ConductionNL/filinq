<?php

/**
 * Signature Verification Link Service
 *
 * Mints and looks up `signatureVerification` records — the outcome-only
 * records that back the public, account-free `verify/{token}` portal.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Mint/lookup service for the `signatureVerification` schema (`document`
 * register).
 *
 * Design decision D1 (signature-verification-portal): the record carries the
 * verification OUTCOME (contentHash + a per-signature summary), never the
 * document bytes and never the signing secret, keyed by a high-entropy
 * (>=128-bit) non-enumerable token so an anonymous verifier needs no
 * Nextcloud account and no file-read access.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-non-enumerable-verification-token-backs-anonymous-verification-req-ddsvp-003
 */
class SignatureVerificationLinkService
{
    /**
     * Register/schema the `signatureVerification` records live in — matches
     * the literal 'document' register slug every other docudesk service in
     * this register already hardcodes (DocumentService, AnonymizationService,
     * CorrespondenceService), not an IAppConfig-resolved value.
     */
    private const REGISTER = 'document';

    private const SCHEMA = 'signatureVerification';

    /**
     * Constructor
     *
     * @param SettingsService $settingsService Settings service (OR ObjectService accessor)
     * @param LoggerInterface $logger          Logger
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Mint a new verification record.
     *
     * @param array<string, mixed> $data {
     *     @type string|null                          $token           Pre-minted token (e.g. embedded into a QR
     *                                                                  stamped into the artifact BEFORE the final
     *                                                                  bytes — and therefore the record — exist).
     *                                                                  When omitted, a fresh token is generated here.
     *     @type string                              $fileRef         OR reference to the source file object.
     *     @type string                              $fileName        Public-safe file name (never a path).
     *     @type string                              $contentHash     sha256 of the canonical signed bytes.
     *     @type array<int, array<string, mixed>>    $signatures      Per-signature summary (see
     *                                                                 {@see \OCA\DocuDesk\Service\SigningVerificationService::classifyForRecord()}).
     *     @type string|null                          $waarmerkRef     Optional waarmerk record reference.
     *     @type string|null                          $signingRequestId Optional signing-request id (audit rollup lookup).
     * }
     *
     * @return array<string, mixed> The persisted record, including the minted `token`.
     *
     * @throws \RuntimeException When OpenRegister is unavailable or persistence fails.
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-non-enumerable-verification-token-backs-anonymous-verification-req-ddsvp-003
     */
    public function mint(array $data): array
    {
        // >=128-bit, high-entropy, non-enumerable token (design.md D1). A
        // caller that already embedded a token into the artifact (e.g. the
        // QR stamp, minted before the final signed bytes exist) passes it
        // through so the stamp and the stored record agree.
        $token = (string) ($data['token'] ?? '');
        if ($token === '') {
            $token = bin2hex(random_bytes(16));
        }

        $record = [
            'token'            => $token,
            'fileRef'          => (string) ($data['fileRef'] ?? ''),
            'fileName'         => (string) ($data['fileName'] ?? ''),
            'contentHash'      => (string) ($data['contentHash'] ?? ''),
            'signatures'       => $data['signatures'] ?? [],
            'waarmerkRef'      => $data['waarmerkRef'] ?? null,
            'signingRequestId' => $data['signingRequestId'] ?? null,
            'createdAt'        => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'revoked'          => false,
        ];

        $objectService = $this->settingsService->getObjectService();
        $saved         = $objectService->saveObject(
            object: $record,
            register: self::REGISTER,
            schema: self::SCHEMA
        );

        return $this->toArray(object: $saved);

    }//end mint()

    /**
     * Look up a verification record by token.
     *
     * Non-oracle by construction: an unknown token, a malformed token, and a
     * revoked record all return null — the caller (PublicVerificationController)
     * renders the SAME `unknown` verdict shape for all three (REQ-DDSVP-001 /
     * REQ-DDSVP-004), so this method must never distinguish "does not exist"
     * from "exists but is revoked" via its return shape (it doesn't — both
     * collapse to null).
     *
     * @param string $token The token to resolve.
     *
     * @return array<string, mixed>|null The record, or null when unknown/revoked.
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-public-verification-portal-page-req-ddsvp-001
     */
    public function lookupByToken(string $token): ?array
    {
        if (trim($token) === '') {
            return null;
        }

        try {
            $objectService = $this->settingsService->getObjectService();

            $results = $objectService->findAll(
                [
                    'filters' => [
                        'register' => self::REGISTER,
                        'schema'   => self::SCHEMA,
                        'token'    => $token,
                    ],
                ]
            );

            if (is_iterable($results) === false) {
                return null;
            }

            foreach ($results as $entry) {
                $row = $this->toArray(object: $entry);
                if (($row['token'] ?? null) !== $token) {
                    // findAll() may resolve loosely; require an exact match
                    // (mirrors NativeSigningProvider::loadRawSessionByExternalId).
                    continue;
                }

                if (($row['revoked'] ?? false) === true) {
                    return null;
                }

                return $row;
            }//end foreach

            return null;
        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to look up signature verification token: '.$e->getMessage(),
                ['exception' => $e]
            );
            return null;
        }//end try

    }//end lookupByToken()

    /**
     * Normalise an ObjectService result (ObjectEntity or array) to a plain array.
     *
     * @param mixed $object The raw entry from saveObject()/findAll().
     *
     * @return array<string, mixed> The normalised row.
     */
    private function toArray(mixed $object): array
    {
        if (is_array($object) === true) {
            return $object;
        }

        if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
            $serialised = $object->jsonSerialize();
            if (is_array($serialised) === true) {
                return $serialised;
            }
        }

        return (array) $object;

    }//end toArray()
}//end class
