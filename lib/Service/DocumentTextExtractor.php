<?php
/**
 * Document Text Extractor
 *
 * Service for extracting text content from document objects and normalizing
 * date fields. Extracted from MetadataService to reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-43
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-44
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Service for extracting text and normalizing dates from document objects
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentTextExtractor
{
    /**
     * Constructor for DocumentTextExtractor
     *
     * @param LoggerInterface $logger Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Extract text content from object data
     *
     * @param array<string, mixed> $objectData The document object data
     *
     * @return string The text content, empty string if not found
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-43
     */
    public function extractTextContent(array $objectData): string
    {
        $text = $objectData['content'] ?? $objectData['text'] ?? $objectData['description'] ?? '';

        if (is_string($text) === false) {
            return '';
        }

        return $text;

    }//end extractTextContent()

    /**
     * Normalize date fields in object data
     *
     * @param array<string, mixed> $objectData The document object data
     *
     * @return array<string, string> Normalized date fields
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-44
     */
    public function normalizeDateFields(array $objectData): array
    {
        $dateFields = ['created', 'modified', 'date', 'creationDate', 'modificationDate'];
        $metadata   = [];

        foreach ($dateFields as $field) {
            if (empty($objectData[$field]) === true) {
                continue;
            }

            try {
                $date = new DateTime($objectData[$field]);
                $metadata[$field] = $date->format('c');
            } catch (Exception $e) {
                $this->logger->debug(
                    'Failed to normalize date field: '.$field,
                    [
                        'value'     => $objectData[$field],
                        'exception' => $e,
                    ]
                );
            }
        }

        return $metadata;

    }//end normalizeDateFields()
}//end class
