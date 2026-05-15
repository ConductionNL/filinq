<?php
/**
 * Entity Detection Service
 *
 * Service for detecting, normalizing, and mapping entities for anonymization.
 * Delegates result parsing to AnonymizationResultParser.
 * Extracted from AnonymizationService to reduce class complexity.
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

/**
 * Service for entity detection, normalization, and anonymization mapping
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityDetectionService
{


    /**
     * Constructor for EntityDetectionService
     *
     * @param AnonymizationResultParser $resultParser Anonymization result parser
     *
     * @return void
     */
    public function __construct(
        private readonly AnonymizationResultParser $resultParser
    ) {

    }//end __construct()


    /**
     * Normalize entity data to a consistent format
     *
     * @param array<mixed> $entities Raw entity objects or arrays
     *
     * @return array<int, array<string, mixed>> Normalized entity list
     */
    public function normalizeEntities(array $entities): array
    {
        $normalizedEntities = [];
        foreach ($entities as $entity) {
            $entityData = (array) $entity;
            if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
                $entityData = $entity->jsonSerialize();
            }

            $normalizedEntities[] = [
                'type'              => $entityData['entity_type'] ?? $entityData['entityType'] ?? 'UNKNOWN',
                'value'             => $entityData['entity_value'] ?? $entityData['entityValue'] ?? '',
                'confidence'        => $entityData['confidence'] ?? 0.0,
                // Forward-compat fields for the Wave 1.3 grondslagen flow.
                // relationId is needed to PATCH the row; bases/skipAnonymization
                // reflect any pre-existing decision metadata.
                'relationId'        => $entityData['relation_id'] ?? $entityData['relationId'] ?? null,
                'bases'             => $entityData['bases'] ?? null,
                'skipAnonymization' => (bool) ($entityData['skip_anonymization'] ?? $entityData['skipAnonymization'] ?? false),
            ];
        }

        return $normalizedEntities;

    }//end normalizeEntities()


    /**
     * Map entities to the format expected by OpenRegister's anonymizeDocument
     *
     * @param array<array<string, mixed>> $entities The raw entities
     *
     * @return array<int, array<string, string>> Mapped entities
     */
    public function mapEntitiesForAnonymization(array $entities): array
    {
        $mappedEntities = [];
        $seen           = [];
        foreach ($entities as $entity) {
            $text = (string) ($entity['value'] ?? $entity['text'] ?? '');

            if ($text === '' || strlen($text) < 3 || is_numeric($text) === true) {
                continue;
            }

            if (isset($seen[$text]) === true) {
                continue;
            }

            $seen[$text]      = true;
            $mappedEntities[] = [
                'text'       => $text,
                'entityType' => (string) ($entity['type'] ?? $entity['entityType'] ?? 'UNKNOWN'),
                'key'        => $this->generateUuid(),
            ];
        }

        return $mappedEntities;

    }//end mapEntitiesForAnonymization()


    /**
     * Parse anonymization result into a structured array
     *
     * @param mixed $result The raw anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     */
    public function parseAnonymizationResult(mixed $result): array
    {
        return $this->resultParser->parseResult($result);

    }//end parseAnonymizationResult()


    /**
     * Generate a UUID v4 string
     *
     * @return string A UUID v4 string
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }//end generateUuid()


}//end class
