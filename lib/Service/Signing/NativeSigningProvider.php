<?php

/**
 * Native Signing Provider
 *
 * Implements Simple Electronic Signature (SES) signing locally.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Signing;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\DocuDesk\Service\SettingsService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Native signing provider for SES-level signatures
 *
 * Sessions are persisted as OpenRegister objects (register/schema configured
 * via `signingSession_register` / `signingSession_schema` in IAppConfig).
 * Previously they lived only in a per-request `$sessions` array, so the
 * `initiateSigning()` HTTP request created a record that `checkStatus()`,
 * `downloadSignedDocument()` and `cancelSigning()` in subsequent requests
 * could never see (issue #287).
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
 */
class NativeSigningProvider implements SigningProviderInterface
{
    /**
     * Constructor
     *
     * @param LoggerInterface $logger          Logger interface
     * @param SettingsService $settingsService Settings service (provides OR ObjectService)
     * @param IAppConfig      $config          App config (resolves session register/schema)
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SettingsService $settingsService,
        private readonly IAppConfig $config
    ) {

    }//end __construct()

    /**
     * Get provider identifier
     *
     * @return string The provider identifier
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
     */
    public function getIdentifier(): string
    {
        return 'native';

    }//end getIdentifier()

    /**
     * Initiate a native SES signing flow
     *
     * @param string               $documentPath Path to the document
     * @param string               $documentName Display name of the document
     * @param array<string, mixed> $signers      Signer data array
     * @param string               $level        Signature level
     * @param array<string, mixed> $options      Additional options
     *
     * @return array<string, mixed> Result with signing session identifier
     *
     * @throws RuntimeException If the signature level is not supported
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
     */
    public function initiateSigning(
        string $documentPath,
        string $documentName,
        array $signers,
        string $level,
        array $options=[]
    ): array {
        // The native SES artifact writer is now wired (issue #304): the
        // completing signature produces a verifiable artifact via
        // produceSignedArtifact(). This session-oriented entry point creates the
        // persisted session used by the async status/download flow.
        if ($this->supportsLevel(level: $level) === false) {
            throw new RuntimeException(
                'Native provider only supports SES signature level, got: '.$level
            );
        }

        $externalId = 'native-'.bin2hex(random_bytes(16));

        $session = [
            'externalId'         => $externalId,
            'documentPath'       => $documentPath,
            'documentName'       => $documentName,
            'signers'            => $signers,
            'level'              => $level,
            'status'             => 'pending',
            'signatures'         => [],
            'createdAt'          => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'completedAt'        => null,
            'signedDocumentPath' => null,
            'markerEmbedded'     => false,
        ];

        $this->persistSession(session: $session);

        return [
            'success'    => true,
            'externalId' => $externalId,
            'message'    => 'Native SES signing session created',
        ];

    }//end initiateSigning()

    /**
     * Check status of a native signing session
     *
     * Orphan-auth seam (hydra gate-6): a provider-contract status *read*, not
     * an authorization guard. No native caller — the async status-poll leg is
     * a pluggable extension point (see SigningProviderInterface::checkStatus);
     * the live status surface is OR's ApprovalChain via
     * `SigningController::showRequest`. Classified as a legit plugin seam in
     * openspec/changes/orphan-auth-remediation/design.md.
     *
     * @param string $externalId The signing session identifier
     *
     * @return array<string, mixed> The session status
     *
     * @throws RuntimeException If session not found
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
     */
    public function checkStatus(string $externalId): array
    {
        $session = $this->loadSessionByExternalId(externalId: $externalId);

        return [
            'status'      => $session['status'] ?? 'pending',
            'signers'     => $session['signers'] ?? [],
            'signatures'  => $session['signatures'] ?? [],
            'completedAt' => $session['completedAt'] ?? null,
        ];

    }//end checkStatus()

    /**
     * Download the signed document
     *
     * Returns the path to the document for the persisted signing session.
     * When the session reaches a `completed` state, the SES marker block
     * (the same `/DocuDesk-Signature(base64-json)` PDF pattern that
     * SigningVerificationService::extractSignatures looks for, optionally
     * carrying the HMAC `mac` field over the document content-hash that
     * SigningVerificationService::verifyAssertion validates with the
     * `signing_verification_secret` app-config secret) must be embedded
     * into the produced file bytes. Embedding requires a writeable PDF
     * pipeline (mPDF re-render or a PDF cross-ref appending step) which
     * is not yet wired here; tracked as a follow-up to #287 — this method
     * therefore returns the persisted `signedDocumentPath` (falling back
     * to the original `documentPath`) and flags the session with
     * `markerEmbedded => false` until the marker writer ships.
     *
     * @param string $externalId The signing session identifier
     *
     * @return string The signed document path
     *
     * @throws RuntimeException If session not found or not completed
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
     */
    public function downloadSignedDocument(string $externalId): string
    {
        $session = $this->loadSessionByExternalId(externalId: $externalId);

        if (($session['status'] ?? '') !== 'completed') {
            throw new RuntimeException('Signing session is not completed (pipeline not yet integrated — see issue #304)');
        }

        $signedPath = $session['signedDocumentPath'] ?? null;
        if (is_string($signedPath) === true && $signedPath !== '') {
            return $signedPath;
        }

        // Marker not yet embedded — record that the caller hit the
        // download path before the marker writer is available so ops
        // can see how often the follow-up matters.
        $this->logger->info(
            'Native signing session '.$externalId.' downloaded without an embedded SES marker; '
            .'falling back to original document path (follow-up to #287).'
        );

        return (string) ($session['documentPath'] ?? '');

    }//end downloadSignedDocument()

    /**
     * Cancel a native signing session
     *
     * @param string $externalId The signing session identifier
     *
     * @return bool True if cancelled
     *
     * @throws RuntimeException If session not found
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
     */
    public function cancelSigning(string $externalId): bool
    {
        $session = $this->loadSessionByExternalId(externalId: $externalId);

        $session['status'] = 'cancelled';
        $this->persistSession(session: $session);

        return true;

    }//end cancelSigning()

    /**
     * Check if this provider supports the given signature level
     *
     * @param string $level The signature level to check
     *
     * @return bool True if SES level
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#2-2
     */
    public function supportsLevel(string $level): bool
    {
        return $level === 'SES';

    }//end supportsLevel()

    /**
     * Produce a verifiable native SES signed artifact.
     *
     * Embeds a `/DocuDesk-Signature(base64-json)` marker binding the signer
     * identity, timestamp and IP, carrying an HMAC (`mac`) over the document
     * content-hash computed with the server-held `signing_verification_secret`.
     * The HMAC is computed over the *canonical* form of the produced document —
     * the bytes with every `/DocuDesk-Signature(...)` marker payload blanked —
     * so the assertion cannot cover itself and
     * `SigningVerificationService::verifyAssertion()` recomputes the identical
     * value and validates the artifact.
     *
     * Honest-completion gate (issue #304): when the secret is unset the writer
     * throws rather than emit an unverifiable artifact, so the completing
     * signature fails loudly instead of mislabelling the original as signed.
     *
     * @param string               $documentContent The original document bytes.
     * @param array<string, mixed> $context         Signing context.
     *
     * @return string The signed document bytes.
     *
     * @throws RuntimeException When the signing secret is unset.
     *
     * @spec openspec/changes/native-ses-signature-embedding/specs/document-signing/spec.md
     */
    public function produceSignedArtifact(string $documentContent, array $context): string
    {
        $secret = $this->config->getValueString('docudesk', 'signing_verification_secret', '');
        if ($secret === '') {
            throw new RuntimeException(
                'Cannot produce a native SES artifact: signing_verification_secret is unset. '
                .'Configure the signing secret in DocuDesk admin settings before enabling signing.'
            );
        }

        $assertion = [
            'signer'    => (string) ($context['signer'] ?? 'Unknown'),
            'signers'   => ($context['signers'] ?? []),
            'timestamp' => (string) ($context['timestamp'] ?? (new DateTimeImmutable())->format(DateTimeInterface::ATOM)),
            'level'     => (string) ($context['level'] ?? 'SES'),
            'method'    => 'native',
            'ip'        => (string) ($context['ip'] ?? ''),
        ];

        // Build the canonical (unsigned-marker) form the verifier will recompute:
        // the produced document with an empty marker payload. The HMAC is taken
        // over the hash of that canonical form so the MAC cannot cover itself.
        $canonical   = $this->assembleSignedBytes(documentContent: $documentContent, payload: '');
        $contentHash = hash('sha256', $canonical);
        $mac         = hash_hmac('sha256', $contentHash, $secret);

        $assertion['mac'] = $mac;
        $payload          = base64_encode((string) json_encode($assertion));

        return $this->assembleSignedBytes(documentContent: $documentContent, payload: $payload);

    }//end produceSignedArtifact()

    /**
     * Assemble the signed document bytes with the given marker payload.
     *
     * Appending the marker as a trailing PDF object keeps the original bytes
     * intact and lets the verifier recover the canonical form by blanking the
     * marker payload. An empty payload yields the canonical (hashed) form.
     *
     * @param string $documentContent The original document bytes.
     * @param string $payload         The base64 marker payload ('' for canonical).
     *
     * @return string The assembled bytes.
     */
    private function assembleSignedBytes(string $documentContent, string $payload): string
    {
        return $documentContent
            ."\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(".$payload.")\nendobj\n";

    }//end assembleSignedBytes()

    /**
     * Persist a signing session as an OpenRegister object
     *
     * Honours `externalId` as the natural key — when a session with the
     * same externalId already exists its `id`/`uuid` is preserved so OR
     * updates the existing row instead of creating a duplicate. Uses the
     * canonical OR ObjectService surface (`saveObject(object, extend,
     * register, schema, uuid)`).
     *
     * @param array<string, mixed> $session The session data
     *
     * @return void
     *
     * @throws RuntimeException If OR is unavailable
     */
    private function persistSession(array $session): void
    {
        $objectService = $this->settingsService->getObjectService();
        if ($objectService === null) {
            throw new RuntimeException('OpenRegister is not available; cannot persist signing session');
        }

        [$register, $schema] = $this->resolveSessionRegisterSchema();

        $uuid = null;
        // Preserve OR uuid when updating an existing session row.
        if (isset($session['externalId']) === true) {
            $existing = $this->loadRawSessionByExternalId(externalId: (string) $session['externalId']);
            if ($existing !== null) {
                if (isset($existing['uuid']) === true) {
                    $uuid = (string) $existing['uuid'];
                } else if (isset($existing['id']) === true) {
                    $uuid = (string) $existing['id'];
                }
            }
        }

        // When updating an existing session row, embed the OR uuid in the
        // object data so the canonical ObjectService::saveObject(object:,
        // register:, schema:) can detect and update the existing record
        // rather than creating a duplicate.
        if ($uuid !== null) {
            $session['id'] = $uuid;
        }

        $objectService->saveObject(object: $session, register: $register, schema: $schema);

    }//end persistSession()

    /**
     * Load a session by externalId, throwing if missing
     *
     * @param string $externalId The externalId to look up
     *
     * @return array<string, mixed> The session row
     *
     * @throws RuntimeException If the session is not found
     */
    private function loadSessionByExternalId(string $externalId): array
    {
        $session = $this->loadRawSessionByExternalId(externalId: $externalId);
        if ($session === null) {
            throw new RuntimeException('Native signing session not found: '.$externalId);
        }

        return $session;

    }//end loadSessionByExternalId()

    /**
     * Load a session by externalId, returning null if missing
     *
     * Uses OR's findAll(config) facade with a filter on externalId so the
     * call goes through the canonical zoeken-filteren pipeline rather than
     * a non-existent `getObjects($register, $schema)` shortcut.
     *
     * @param string $externalId The externalId to look up
     *
     * @return array<string, mixed>|null The session row or null
     */
    private function loadRawSessionByExternalId(string $externalId): ?array
    {
        try {
            $objectService = $this->settingsService->getObjectService();
            if ($objectService === null) {
                return null;
            }

            [$register, $schema] = $this->resolveSessionRegisterSchema();

            $results = $objectService->findAll(
                [
                    'filters' => [
                        'register'   => $register,
                        'schema'     => $schema,
                        'externalId' => $externalId,
                    ],
                ]
            );

            if (is_iterable($results) === false) {
                return null;
            }

            foreach ($results as $entry) {
                $row = $this->normaliseEntry(entry: $entry);
                if ($row === null) {
                    continue;
                }

                if (($row['externalId'] ?? null) === $externalId) {
                    return $row;
                }
            }

            return null;
        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to load signing session '.$externalId.': '.$e->getMessage(),
                ['exception' => $e]
            );
            return null;
        }//end try

    }//end loadRawSessionByExternalId()

    /**
     * Normalise an OR entry (ObjectEntity or array) into a plain array
     *
     * @param mixed $entry The raw entry from findAll()
     *
     * @return array<string, mixed>|null The normalised row, or null on failure
     */
    private function normaliseEntry(mixed $entry): ?array
    {
        if (is_array($entry) === true) {
            return $entry;
        }

        if (is_object($entry) === true && method_exists($entry, 'jsonSerialize') === true) {
            $serialised = $entry->jsonSerialize();
            if (is_array($serialised) === true) {
                return $serialised;
            }
        }

        if (is_object($entry) === true && method_exists($entry, 'getObject') === true) {
            $inner = $entry->getObject();
            if (is_array($inner) === true) {
                return $inner;
            }
        }

        return null;

    }//end normaliseEntry()

    /**
     * Resolve the OR register/schema pair used to persist sessions
     *
     * @return array{0:string,1:string} [register, schema]
     */
    private function resolveSessionRegisterSchema(): array
    {
        $register = $this->config->getValueString('docudesk', 'signingSession_register', 'signing');
        $schema   = $this->config->getValueString('docudesk', 'signingSession_schema', 'signingSession');

        return [$register, $schema];

    }//end resolveSessionRegisterSchema()
}//end class
