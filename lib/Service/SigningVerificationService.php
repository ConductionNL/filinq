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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Service for verifying document signatures
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SigningVerificationService
{
    /**
     * Constructor
     *
     * @param IRootFolder     $rootFolder Root folder
     * @param LoggerInterface $logger     Logger
     * @param IAppConfig      $config     App config
     *
     * @return void
     */
    public function __construct(
        private readonly IRootFolder $rootFolder,
        private readonly LoggerInterface $logger,
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

        // Recompute the MAC over the document with the asserted `mac` field
        // removed, so the MAC cannot cover itself. Strip the literal blob's
        // mac value out of the byte stream before hashing.
        $contentHash = hash('sha256', $this->stripAssertionMac(pdfContent: $pdfContent, mac: $mac));
        $expected    = hash_hmac('sha256', $contentHash, $secret);

        return hash_equals($expected, $mac);

    }//end verifyAssertion()

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
        return preg_replace('/' . preg_quote($mac, '/') . '/', '', $pdfContent) ?? $pdfContent;

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
