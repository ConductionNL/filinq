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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Signing;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Native signing provider for SES-level signatures
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class NativeSigningProvider implements SigningProviderInterface
{

    /**
     * In-memory store for signing sessions
     *
     * @var array<string, array<string, mixed>>
     */
    private array $sessions = [];

    /**
     * Constructor
     *
     * @param IUserSession    $userSession The user session
     * @param LoggerInterface $logger      Logger interface
     *
     * @return void
     */
    public function __construct(
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Get provider identifier
     *
     * @return string The provider identifier
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
     */
    public function initiateSigning(
        string $documentPath,
        string $documentName,
        array $signers,
        string $level,
        array $options=[]
    ): array {
        if ($this->supportsLevel(level: $level) === false) {
            throw new RuntimeException(
                'Native provider only supports SES signature level, got: '.$level
            );
        }

        $externalId = 'native-'.bin2hex(random_bytes(16));

        $this->sessions[$externalId] = [
            'documentPath' => $documentPath,
            'documentName' => $documentName,
            'signers'      => $signers,
            'level'        => $level,
            'status'       => 'pending',
            'signatures'   => [],
            'createdAt'    => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];

        return [
            'success'    => true,
            'externalId' => $externalId,
            'message'    => 'Native SES signing session created',
        ];

    }//end initiateSigning()

    /**
     * Check status of a native signing session
     *
     * @param string $externalId The signing session identifier
     *
     * @return array<string, mixed> The session status
     *
     * @throws RuntimeException If session not found
     */
    public function checkStatus(string $externalId): array
    {
        if (isset($this->sessions[$externalId]) === false) {
            throw new RuntimeException('Native signing session not found: '.$externalId);
        }

        $session = $this->sessions[$externalId];

        return [
            'status'      => $session['status'],
            'signers'     => $session['signers'],
            'signatures'  => $session['signatures'],
            'completedAt' => $session['completedAt'] ?? null,
        ];

    }//end checkStatus()

    /**
     * Download the signed document
     *
     * @param string $externalId The signing session identifier
     *
     * @return string The signed document path
     *
     * @throws RuntimeException If session not found or not completed
     */
    public function downloadSignedDocument(string $externalId): string
    {
        if (isset($this->sessions[$externalId]) === false) {
            throw new RuntimeException('Native signing session not found: '.$externalId);
        }

        $session = $this->sessions[$externalId];
        if ($session['status'] !== 'completed') {
            throw new RuntimeException('Signing session is not completed');
        }

        return $session['documentPath'];

    }//end downloadSignedDocument()

    /**
     * Cancel a native signing session
     *
     * @param string $externalId The signing session identifier
     *
     * @return bool True if cancelled
     *
     * @throws RuntimeException If session not found
     */
    public function cancelSigning(string $externalId): bool
    {
        if (isset($this->sessions[$externalId]) === false) {
            throw new RuntimeException('Native signing session not found: '.$externalId);
        }

        $this->sessions[$externalId]['status'] = 'cancelled';

        return true;

    }//end cancelSigning()

    /**
     * Check if this provider supports the given signature level
     *
     * @param string $level The signature level to check
     *
     * @return bool True if SES level
     */
    public function supportsLevel(string $level): bool
    {
        return $level === 'SES';

    }//end supportsLevel()
}//end class
