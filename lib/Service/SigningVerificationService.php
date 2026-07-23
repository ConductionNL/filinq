<?php

/**
 * Signing Verification Service
 *
 * Verifies signatures embedded in PDF documents.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
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

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use RuntimeException;

/**
 * Service for verifying document signatures
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#5-1
 */
class SigningVerificationService
{
    /**
     * Constructor
     *
     * @param IRootFolder $rootFolder Root folder
     * @param IAppConfig  $config     App config
     *
     * @return void
     */
    public function __construct(
        private readonly IRootFolder $rootFolder,
        private readonly IAppConfig $config
    ) {

    }//end __construct()

    /**
     * Verify all signatures in a document
     *
     * @param int    $fileId The Nextcloud file ID
     * @param string $userId The user ID requesting verification
     *
     * @return array<string, mixed> Verification result
     *
     * @throws RuntimeException If file cannot be accessed
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#5-1
     */
    public function verifyDocument(int $fileId, string $userId): array
    {
        $userFolder = $this->rootFolder->getUserFolder($userId);
        $nodes      = $userFolder->getById($fileId);

        if (empty($nodes) === true) {
            throw new RuntimeException('File not found: '.$fileId);
        }

        $file = $nodes[0];
        if (($file instanceof File) === false) {
            throw new RuntimeException('Node is not a file: '.$fileId);
        }

        $content    = $file->getContent();
        $signatures = $this->extractSignatures(pdfContent: $content);

        return [
            'fileId'     => $fileId,
            'fileName'   => $file->getName(),
            'signatures' => $signatures,
            'isValid'    => $this->allSignaturesValid(signatures: $signatures),
            'verifiedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];

    }//end verifyDocument()

    /**
     * Whether the active signing pipeline binds signer identity into the
     * signature MAC.
     *
     * Verified at HEAD (design.md Context /
     * `NativeSigningProvider::produceSignedArtifact()`): the HMAC covers only
     * the canonicalised content hash; `signer`/`timestamp`/`level`/`ip` are
     * NOT in the MAC input, so a validly signed artifact's signer field can be
     * rewritten and still verify. This method is the single flip-point for
     * that fact: once the active `signing-trust-rebuild` change (GH #284)
     * ships an identity-bound MAC, this method starts returning true and the
     * mint step (SigningService) records `identityBound: true` on every new
     * verification record — no portal code changes beyond reading the flag
     * (design.md D2).
     *
     * @return bool False until signing-trust-rebuild lands.
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-content-integrity-and-signer-identity-are-presented-as-distinct-guarantees-req-ddsvp-002
     */
    public function isIdentityBoundSupported(): bool
    {
        return false;

    }//end isIdentityBoundSupported()

    /**
     * Classify every signature found in signed PDF bytes for a verification
     * record — the tri-state summary
     * {@see SignatureVerificationLinkService::mint()} persists so an
     * anonymous verifier can be served without file-read access.
     *
     * Unlike {@see extractSignatures()} (binary valid/invalid, kept for the
     * authenticated `verifyDocument()` path), this method reports a tri-state
     * `status` per signature:
     *
     * - `verified`     — a DocuDesk marker whose MAC matched (content
     *                    integrity cryptographically confirmed).
     * - `invalid`      — a DocuDesk marker whose MAC did NOT match (tamper).
     * - `unverifiable` — an external `/Type /Sig` entry with no DocuDesk
     *                    marker — honestly reported as "cannot yet validate",
     *                    never as tamper (REQ-DDSVP-002).
     *
     * `identityBound` is always {@see isIdentityBoundSupported()} — false
     * until signing-trust-rebuild lands — so a rewritten `signer` field is
     * NEVER presented as a cryptographically verified identity, even when the
     * content-hash MAC matches.
     *
     * @param string $pdfContent The signed PDF bytes (already produced —
     *                           called at mint time, not re-derived later).
     *
     * @return array<int, array<string, mixed>> Per-signature summaries:
     *         level, method, signerAsserted, status, integrityVerified,
     *         identityBound.
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-content-integrity-and-signer-identity-are-presented-as-distinct-guarantees-req-ddsvp-002
     */
    public function classifyForRecord(string $pdfContent): array
    {
        $identityBound = $this->isIdentityBoundSupported();
        $summaries      = [];

        $dataPattern = '/\/DocuDesk-Signature\s*\(([^)]+)\)/';
        preg_match_all($dataPattern, $pdfContent, $dataMatches);

        if (empty($dataMatches[1]) === false) {
            foreach ($dataMatches[1] as $encoded) {
                $decoded = json_decode(base64_decode($encoded), true);
                if (is_array($decoded) === false) {
                    continue;
                }

                $integrityVerified = $this->verifyAssertion(assertion: $decoded, pdfContent: $pdfContent);

                $summaries[] = [
                    'level'             => (string) ($decoded['level'] ?? 'SES'),
                    'method'            => (string) ($decoded['method'] ?? 'native'),
                    'signerAsserted'    => (string) ($decoded['signer'] ?? 'Unknown'),
                    'status'            => $integrityVerified ? 'verified' : 'invalid',
                    'integrityVerified' => $integrityVerified,
                    'identityBound'     => $identityBound,
                ];
            }//end foreach

            return $summaries;
        }//end if

        // No DocuDesk marker found — a genuine external `/Type /Sig` CMS
        // signature (or no signature at all) reports `unverifiable`, never
        // `invalid`: DocuDesk cannot yet validate it, but that is not evidence
        // of tampering (REQ-DDSVP-002 scenario: "External CMS signature
        // reports unverifiable, not invalid").
        $pattern = '/\/Type\s*\/Sig/';
        $matches = preg_match_all($pattern, $pdfContent);
        if ($matches !== false && $matches > 0) {
            for ($i = 0; $i < $matches; $i++) {
                $summaries[] = [
                    'level'             => 'unknown',
                    'method'            => 'external',
                    'signerAsserted'    => 'Unknown',
                    'status'            => 'unverifiable',
                    'integrityVerified' => false,
                    'identityBound'     => $identityBound,
                ];
            }
        }

        return $summaries;

    }//end classifyForRecord()

    /**
     * Render a stored verification record into an anonymous-safe verdict.
     *
     * No `userId`/file-read dependency (task 2.2) — the record already
     * carries the outcome computed at mint time
     * ({@see classifyForRecord()}); this method only formats it. The caller
     * (PublicVerificationController) is responsible for the non-oracle
     * unknown-token handling — a null/absent record never reaches here.
     *
     * @param array<string, mixed> $record The `signatureVerification` record.
     *
     * @return array<string, mixed> The public verdict payload.
     *
     * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-content-integrity-and-signer-identity-are-presented-as-distinct-guarantees-req-ddsvp-002
     */
    public function verifyByRecord(array $record): array
    {
        $signatures = $record['signatures'] ?? [];
        if (is_array($signatures) === false) {
            $signatures = [];
        }

        $allVerified = (count($signatures) > 0);
        foreach ($signatures as $signature) {
            if (($signature['status'] ?? '') !== 'verified') {
                $allVerified = false;
                break;
            }
        }

        return [
            'status'      => 'ok',
            'fileName'    => (string) ($record['fileName'] ?? ''),
            'contentHash' => (string) ($record['contentHash'] ?? ''),
            'signatures'  => array_values($signatures),
            'isValid'     => $allVerified,
            'waarmerkRef' => $record['waarmerkRef'] ?? null,
            'createdAt'   => $record['createdAt'] ?? null,
        ];

    }//end verifyByRecord()

    /**
     * Extract signature information from a PDF document
     *
     * Security note (finding #284): the embedded `/DocuDesk-Signature(...)`
     * blob is entirely attacker-controlled (anyone can append it to a PDF),
     * so its mere presence proves nothing. This verifier is therefore
     * FAIL-CLOSED: a self-asserted blob is reported with `valid => false`
     * unless its content can be cryptographically verified against the
     * document (HMAC over the document content-hash using a server-held
     * secret, see verifyAssertion()). Real PAdES/CMS signature validation is
     * not yet implemented; embedded `/Type /Sig` entries we cannot verify are
     * reported as `valid => false` rather than trusted.
     *
     * @param string $pdfContent The PDF file content
     *
     * @return array<int, array<string, mixed>> List of signature records
     */
    private function extractSignatures(string $pdfContent): array
    {
        $signatures = [];

        $pattern = '/\/Type\s*\/Sig/';
        $matches = preg_match_all($pattern, $pdfContent);

        if ($matches === false || $matches === 0) {
            return $signatures;
        }

        $dataPattern = '/\/DocuDesk-Signature\s*\(([^)]+)\)/';
        preg_match_all($dataPattern, $pdfContent, $dataMatches);

        if (empty($dataMatches[1]) === false) {
            foreach ($dataMatches[1] as $encoded) {
                $decoded = json_decode(base64_decode($encoded), true);
                if (is_array($decoded) === true) {
                    // Fail-closed: only trust the blob if it carries a valid
                    // server-verifiable HMAC over the document content. A
                    // self-asserted (unsigned) blob reports valid => false.
                    $signatures[] = [
                        'signer'    => $decoded['signer'] ?? 'Unknown',
                        'timestamp' => $decoded['timestamp'] ?? '',
                        'level'     => $decoded['level'] ?? 'SES',
                        'method'    => $decoded['method'] ?? 'unknown',
                        'ip'        => $decoded['ip'] ?? '',
                        'valid'     => $this->verifyAssertion(
                            assertion: $decoded,
                            pdfContent: $pdfContent
                        ),
                    ];
                }
            }//end foreach
        }//end if

        if (empty($signatures) === true) {
            for ($i = 0; $i < $matches; $i++) {
                $signatures[] = [
                    'signer'    => 'External signer',
                    'timestamp' => '',
                    'level'     => 'unknown',
                    'method'    => 'external',
                    'ip'        => '',
                    'valid'     => false,
                ];
            }
        }

        return $signatures;

    }//end extractSignatures()

    /**
     * Cryptographically verify a self-asserted DocuDesk signature blob
     *
     * Verifies an HMAC-SHA256 over the document content-hash (the PDF with
     * the signature blob's own `mac` field stripped) using a server-held
     * secret. Without a configured secret or a matching MAC the assertion is
     * rejected (fail-closed, security finding #284).
     *
     * @param array<string, mixed> $assertion  The decoded signature blob
     * @param string               $pdfContent The full PDF content
     *
     * @return bool True only if the assertion is cryptographically verified
     */
    private function verifyAssertion(array $assertion, string $pdfContent): bool
    {
        $mac = $assertion['mac'] ?? '';
        if (is_string($mac) === false || $mac === '') {
            // No server-issued MAC present: cannot be trusted.
            return false;
        }

        $secret = $this->getSigningSecret();
        if ($secret === '') {
            // No server secret configured: nothing can be verified.
            return false;
        }

        // Recompute the MAC over the *canonical* form of the document — the
        // bytes with every `/DocuDesk-Signature(...)` marker payload blanked —
        // so the self-asserted blob (which carries the mac) cannot cover
        // itself. The writer (NativeSigningProvider::produceSignedArtifact)
        // computes the HMAC over the identical canonical form, so a genuine
        // artifact matches. Stripping only the literal mac substring (its
        // previous behaviour) could never work: the mac lives base64-encoded
        // inside the marker payload, so it never appears literally and the hash
        // stayed self-referential — no writer could satisfy it.
        $canonical = $this->canonicaliseForAssertion(pdfContent: $pdfContent);
        // Defensive: also strip any literal occurrence of the mac (a no-op on
        // the canonical form, retained for the finding #284 fail-closed intent).
        $canonical   = $this->stripAssertionMac(pdfContent: $canonical, mac: $mac);
        $contentHash = hash('sha256', $canonical);
        $expected    = hash_hmac('sha256', $contentHash, $secret);

        return hash_equals($expected, $mac);

    }//end verifyAssertion()

    /**
     * Compute the canonical content-hash used both by `verifyAssertion()`
     * (MAC input) and by the `signatureVerification` record's `contentHash`
     * field (design.md D1) — a single authoritative implementation so mint
     * time and verify time can never drift apart.
     *
     * @param string $pdfContent The signed PDF bytes (post-QR-stamp,
     *                           post-marker — the exact bytes stored to the file).
     *
     * @return string The sha256 hex digest of the canonical form.
     *
     * @spec openspec/changes/signature-verification-portal/design.md#d1
     */
    public function computeContentHash(string $pdfContent): string
    {
        return hash('sha256', $this->canonicaliseForAssertion(pdfContent: $pdfContent));

    }//end computeContentHash()

    /**
     * Blank every DocuDesk signature marker payload to recover the canonical form
     *
     * The signed artifact is the original document plus a
     * `/DocuDesk-Signature(base64-json)` marker. The marker's own `mac` field
     * cannot be part of the hashed content, so verification (and the writer)
     * hash the document with every marker payload emptied. This yields the exact
     * bytes the writer hashed before it knew the mac, making writer and verifier
     * symmetric.
     *
     * @param string $pdfContent The full PDF content
     *
     * @return string The content with all marker payloads blanked
     */
    private function canonicaliseForAssertion(string $pdfContent): string
    {
        return preg_replace(
            '/\/DocuDesk-Signature\s*\([^)]*\)/',
            '/DocuDesk-Signature()',
            $pdfContent
        ) ?? $pdfContent;

    }//end canonicaliseForAssertion()

    /**
     * Remove the asserted MAC value from the PDF byte stream before hashing
     *
     * @param string $pdfContent The full PDF content
     * @param string $mac        The asserted MAC value to strip
     *
     * @return string The PDF content with the MAC value removed
     */
    private function stripAssertionMac(string $pdfContent, string $mac): string
    {
        // Use preg_replace with a literal match (preg_quote) so that any
        // special regex characters in the MAC value are treated as plain text.
        return preg_replace('/'.preg_quote($mac, '/').'/', '', $pdfContent) ?? $pdfContent;

    }//end stripAssertionMac()

    /**
     * Get the server-held signing secret used to verify assertions
     *
     * @return string The configured secret, or an empty string if unset
     */
    private function getSigningSecret(): string
    {
        return $this->config->getValueString('docudesk', 'signing_verification_secret', '');

    }//end getSigningSecret()

    /**
     * Check if all signatures are valid
     *
     * @param array<int, array<string, mixed>> $signatures The signatures
     *
     * @return bool True if all valid
     */
    private function allSignaturesValid(array $signatures): bool
    {
        // An empty signatures array means nothing was verified — treat as invalid.
        if (count($signatures) === 0) {
            return false;
        }

        foreach ($signatures as $signature) {
            if ($signature['valid'] === false) {
                return false;
            }
        }//end foreach

        return true;

    }//end allSignaturesValid()
}//end class
