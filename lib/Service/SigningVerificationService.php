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
use OCA\DocuDesk\Service\Signing\AssertionCanonicalizer;
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
     * @param IRootFolder            $rootFolder    Root folder
     * @param IAppConfig             $config        App config
     * @param AssertionCanonicalizer $canonicalizer Canonical-JSON encoder shared with the writer
     *
     * @return void
     */
    public function __construct(
        private readonly IRootFolder $rootFolder,
        private readonly IAppConfig $config,
        private readonly AssertionCanonicalizer $canonicalizer=new AssertionCanonicalizer()
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
            'verdict'    => $this->computeVerdict(signatures: $signatures),
            'verifiedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];

    }//end verifyDocument()

    /**
     * Compute the document-level tri-state verdict from per-signature status.
     *
     * `isValid` keeps its strict pre-existing meaning (at least one signature,
     * all `verified`); `verdict` additionally distinguishes tampering from
     * mere inability to verify (signing-trust-rebuild REQ-DDSTR-005): all
     * signatures `verified` -> `verified`; all `invalid` -> `tampered`; all
     * `unverifiable` -> `unverifiable`; any other combination -> `mixed`.
     *
     * @param array<int, array<string, mixed>> $signatures The per-signature results.
     *
     * @return string One of verified|tampered|unverifiable|mixed.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    private function computeVerdict(array $signatures): string
    {
        if (count($signatures) === 0) {
            return 'unverifiable';
        }

        $statuses = array_values(array_unique(array_column($signatures, 'status')));

        if (count($statuses) === 1) {
            return match ($statuses[0]) {
                'verified' => 'verified',
                'invalid' => 'tampered',
                default => 'unverifiable',
            };
        }

        return 'mixed';

    }//end computeVerdict()

    /**
     * Extract signature information from a PDF document
     *
     * Security note (finding #284 / signing-trust-rebuild REQ-DDSTR-005): the
     * embedded `/DocuDesk-Signature(...)` blob is entirely attacker-controlled
     * (anyone can append it to a PDF), so its mere presence proves nothing.
     * This verifier is therefore FAIL-CLOSED and reports one of three honest
     * states per signature: `verified` (v2 MAC recomputed and matches),
     * `invalid` (a v2 marker whose MAC fails — tamper evidence), or
     * `unverifiable` (a legacy v1 marker, or a genuine external `/Type /Sig`
     * signature DocuDesk cannot yet cryptographically validate). `valid` is
     * retained as the derived boolean `status === 'verified'` for
     * response-shape backward compatibility.
     *
     * @param string $pdfContent The PDF file content
     *
     * @return array<int, array<string, mixed>> List of signature records
     *
     * @spec openspec/specs/document-signing/spec.md
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
                    $verification = $this->verifyAssertion(
                        assertion: $decoded,
                        pdfContent: $pdfContent
                    );

                    $signatures[] = [
                        'signer'    => $decoded['signer'] ?? 'Unknown',
                        'timestamp' => $decoded['timestamp'] ?? '',
                        'level'     => $decoded['level'] ?? 'SES',
                        'method'    => $decoded['method'] ?? 'unknown',
                        'ip'        => $decoded['ip'] ?? '',
                        'status'    => $verification['status'],
                        'reason'    => $verification['reason'],
                        // Derived boolean kept for response-shape
                        // compatibility (REQ-DDSTR-005).
                        'valid'     => ($verification['status'] === 'verified'),
                    ];
                }
            }//end foreach
        }//end if

        if (empty($signatures) === true) {
            // A `/Type /Sig` entry with no DocuDesk marker is a genuine
            // external signature DocuDesk cannot yet validate — honestly
            // `unverifiable`, never `invalid` (that would mislabel an
            // inability to verify as tampering, REQ-DDSTR-005).
            for ($i = 0; $i < $matches; $i++) {
                $signatures[] = [
                    'signer'    => 'External signer',
                    'timestamp' => '',
                    'level'     => 'unknown',
                    'method'    => 'external',
                    'ip'        => '',
                    'status'    => 'unverifiable',
                    'reason'    => 'external-signature-unsupported',
                    'valid'     => false,
                ];
            }
        }

        return $signatures;

    }//end extractSignatures()

    /**
     * Cryptographically verify a self-asserted DocuDesk signature blob (v2)
     *
     * V2 assertions carry `v: 2` and a MAC computed as `HMAC-SHA256(secret,
     * sha256(canonical-document) . "\n" . canonical-JSON(assertion-minus-mac))`
     * — the identity fields (`signer`, `timestamp`, `level`, `method`, `ip`,
     * and any bound portal-identity claims) are inside the MAC input, so
     * rewriting any of them while keeping the original `mac` recomputes to a
     * DIFFERENT value and reports `invalid` (closes the #284 residual /
     * portaliq#3 forgeable-signer class, signing-trust-rebuild REQ-DDSTR-001).
     * An assertion without `v: 2` or without `mac` is a legacy v1 artifact (or
     * malformed) and reports `unverifiable`/`legacy-assertion-v1` — it MUST
     * NEVER be reported `verified` (fail-closed).
     *
     * @param array<string, mixed> $assertion  The decoded signature blob
     * @param string               $pdfContent The full PDF content
     *
     * @return array{status: string, reason: string} The tri-state verification result.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    private function verifyAssertion(array $assertion, string $pdfContent): array
    {
        $mac = $assertion['mac'] ?? '';
        if (is_string($mac) === false || $mac === '') {
            // No server-issued MAC present: legacy/malformed, never trusted.
            return ['status' => 'unverifiable', 'reason' => 'legacy-assertion-v1'];
        }

        if ((int) ($assertion['v'] ?? 0) !== 2) {
            // A pre-rebuild (v1) assertion carried a MAC over the content-hash
            // ONLY — its identity fields were never covered. Reporting it
            // `verified` would resurrect the #284 forgery for old artifacts;
            // reporting it `invalid` would mislabel a merely-unverifiable
            // legacy artifact as tampered. Fail-closed to `unverifiable`.
            return ['status' => 'unverifiable', 'reason' => 'legacy-assertion-v1'];
        }

        $secret = $this->getSigningSecret();
        if ($secret === '') {
            // No server secret configured: nothing can be verified.
            return ['status' => 'unverifiable', 'reason' => 'signing-secret-not-configured'];
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

        // V2: the MAC covers the content-hash AND the canonical-JSON of the
        // assertion fields (minus `mac` itself) — recompute over BOTH parts so
        // any rewritten identity field (signer, timestamp, level, method, ip,
        // or a bound portal-identity claim) invalidates the MAC.
        $assertionWithoutMac = $assertion;
        unset($assertionWithoutMac['mac']);
        $payloadCore = $this->canonicalizer->canonicalJson(data: $assertionWithoutMac);

        $expected = hash_hmac('sha256', $contentHash."\n".$payloadCore, $secret);

        if (hash_equals($expected, $mac) === false) {
            return ['status' => 'invalid', 'reason' => 'mac-mismatch'];
        }

        return ['status' => 'verified', 'reason' => 'ok'];

    }//end verifyAssertion()

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
