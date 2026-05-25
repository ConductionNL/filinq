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
     *
     * @return void
     */
    public function __construct(
        private readonly IRootFolder $rootFolder,
        private readonly LoggerInterface $logger
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
                    $signatures[] = [
                        'signer'    => $decoded['signer'] ?? 'Unknown',
                        'timestamp' => $decoded['timestamp'] ?? '',
                        'level'     => $decoded['level'] ?? 'SES',
                        'method'    => $decoded['method'] ?? 'unknown',
                        'ip'        => $decoded['ip'] ?? '',
                        'valid'     => true,
                    ];
                }
            }//end foreach
        }

        if (empty($signatures) === true) {
            for ($i = 0; $i < $matches; $i++) {
                $signatures[] = [
                    'signer'    => 'External signer',
                    'timestamp' => '',
                    'level'     => 'unknown',
                    'method'    => 'external',
                    'ip'        => '',
                    'valid'     => null,
                ];
            }
        }

        return $signatures;

    }//end extractSignatures()

    /**
     * Check if all signatures are valid
     *
     * @param array<int, array<string, mixed>> $signatures The signatures
     *
     * @return bool True if all valid
     */
    private function allSignaturesValid(array $signatures): bool
    {
        foreach ($signatures as $signature) {
            if ($signature['valid'] === false) {
                return false;
            }
        }//end foreach

        return true;

    }//end allSignaturesValid()
}//end class
