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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
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
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
 */
class EntityDetectionService
{
    /**
     * Entity types that represent sensitive identifiers which may be short
     * and/or fully numeric (BSN, IBAN, phone numbers, postcodes, bank/account
     * numbers, case reference numbers, KvK / BTW identifiers). These MUST be
     * forwarded to the redaction step regardless of length or numeric-ness
     * (closes #285).
     *
     * @var array<int, string>
     */
    private const TYPED_PII_TYPES = [
        'BSN',
        'IBAN',
        'PHONE',
        'PHONE_NUMBER',
        'POSTCODE',
        'POSTAL_CODE',
        'ZIP',
        'ACCOUNT',
        'ACCOUNT_NUMBER',
        'BANK_ACCOUNT',
        'CASE_NUMBER',
        'CASE_REFERENCE',
        'KVK',
        'BTW',
        'VAT',
        'EMAIL',
        'PERSON',
        'ADDRESS',
        'LOCATION',
        'ORGANIZATION',
        'DATE',
        'DATE_TIME',
        'CREDIT_CARD',
        'SSN',
    ];

    /**
     * Minimum length (in characters, not bytes) for an entity value of an
     * UNKNOWN / unclassified type to be considered worth redacting. Single-
     * character tokens are almost always NER noise; values of length 2 are
     * already eligible. This floor is NEVER applied to a typed PII entity
     * (see TYPED_PII_TYPES) — those are always redacted (closes #285).
     *
     * @var int
     */
    private const UNTYPED_MIN_LENGTH = 2;

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
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
     * Per-entity `bases[]` is forwarded verbatim when present so OpenRegister
     * can persist the legal basis on the EntityRelation row.
     *
     * Filtering rules (closes #285):
     *   - An empty value is always skipped (nothing to redact).
     *   - A typed PII value (see TYPED_PII_TYPES — BSN, IBAN, PHONE,
     *     POSTCODE, ACCOUNT, CASE_NUMBER, KVK, BTW, EMAIL, PERSON, …) is
     *     ALWAYS forwarded for redaction, regardless of length or
     *     numeric-ness. The previous heuristic silently dropped these,
     *     letting the most sensitive identifiers survive verbatim in the
     *     "anonymized" output.
     *   - Only for UNKNOWN / unclassified entity types do we still apply a
     *     length floor of UNTYPED_MIN_LENGTH characters, measured with
     *     mb_strlen() so multibyte text is counted by characters, not
     *     bytes.
     *   - The previous is_numeric() exclusion is removed entirely: numeric
     *     values (BSN, postcode, phone, case reference) are among the
     *     most sensitive PII and must be redacted.
     *
     * @param array<array<string, mixed>> $entities The raw entities
     *
     * @return array<int, array<string, mixed>> Mapped entities
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-2
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
     */
    public function mapEntitiesForAnonymization(array $entities): array
    {
        $mappedEntities = [];
        $seen           = [];
        foreach ($entities as $entity) {
            $text = (string) ($entity['value'] ?? $entity['text'] ?? '');
            $type = strtoupper((string) ($entity['type'] ?? $entity['entityType'] ?? 'UNKNOWN'));

            if ($text === '') {
                continue;
            }

            // Typed PII (BSN/IBAN/PHONE/…): always redact. Otherwise
            // apply a character-based (NOT byte-based) length floor to
            // avoid forwarding single-character noise tokens.
            if (in_array($type, self::TYPED_PII_TYPES, true) === false
                && mb_strlen($text) < self::UNTYPED_MIN_LENGTH
            ) {
                continue;
            }

            if (isset($seen[$text]) === true) {
                continue;
            }

            $seen[$text] = true;
            $mapped      = [
                'text'       => $text,
                'entityType' => (string) ($entity['type'] ?? $entity['entityType'] ?? 'UNKNOWN'),
                'key'        => $this->generateUuid(),
            ];

            if (isset($entity['bases']) === true) {
                $mapped['bases'] = $entity['bases'];
            }

            $mappedEntities[] = $mapped;
        }//end foreach

        return $mappedEntities;

    }//end mapEntitiesForAnonymization()

    /**
     * Parse anonymization result into a structured array
     *
     * @param mixed $result The raw anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
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
