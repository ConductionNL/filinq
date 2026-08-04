<?php
/**
 * Unredacted Entities Validator
 *
 * Validates the `unredactedEntities[]` request payload shared by the single-file
 * and batch anonymization endpoints. Extracted from AnonymizationController and
 * BatchAnonymizationController, which carried byte-identical copies of the same
 * per-entry validation.
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
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IL10N;

/**
 * Validates `unredactedEntities[]` payload entries for the anonymize endpoints.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 */
class UnredactedEntitiesValidator
{
    /**
     * Constructor for UnredactedEntitiesValidator
     *
     * @param IL10N $l10n Translator for the user-facing validation messages.
     *
     * @return void
     */
    public function __construct(
        private readonly IL10N $l10n
    ) {

    }//end __construct()

    /**
     * Validate the unredactedEntities[] payload entries.
     *
     * Each entry must have:
     *   - entityId   (int, required)
     *   - entityText (string, required)
     *   - entityType (string, required)
     *   - publicationBases (array of strings, required, non-empty)
     * Optional:
     *   - contactEmail   (string)
     *   - contactAddress (string)
     *
     * @param array<int, mixed> $entries The unredactedEntities array from the request.
     *
     * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
     *                                                             invalid entry, null when all valid.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
     */
    public function validate(array $entries): ?array
    {
        foreach ($entries as $idx => $entry) {
            if (is_array($entry) === false) {
                return $this->badRequest(
                    message: $this->l10n->t('Each unredactedEntities entry must be an object (index %s)', [$idx])
                );
            }

            $error = $this->validateIdentityFields(entry: $entry, idx: $idx);
            if ($error !== null) {
                return $error;
            }

            $error = $this->validatePublicationBases(entry: $entry, idx: $idx);
            if ($error !== null) {
                return $error;
            }

            $error = $this->validateContactFields(entry: $entry, idx: $idx);
            if ($error !== null) {
                return $error;
            }
        }//end foreach

        return null;

    }//end validate()

    /**
     * Validate the required identity fields of one entry.
     *
     * @param array<string, mixed> $entry The entry being validated.
     * @param int|string           $idx   The entry's index, used in the error message.
     *
     * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    private function validateIdentityFields(array $entry, int|string $idx): ?array
    {
        if (isset($entry['entityId']) === false || is_int($entry['entityId']) === false) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].entityId is required and must be an integer', [$idx])
            );
        }

        if (empty($entry['entityText']) === true || is_string($entry['entityText']) === false) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].entityText is required and must be a string', [$idx])
            );
        }

        if (empty($entry['entityType']) === true || is_string($entry['entityType']) === false) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].entityType is required and must be a string', [$idx])
            );
        }

        return null;

    }//end validateIdentityFields()

    /**
     * Validate the publicationBases[] field of one entry.
     *
     * @param array<string, mixed> $entry The entry being validated.
     * @param int|string           $idx   The entry's index, used in the error message.
     *
     * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    private function validatePublicationBases(array $entry, int|string $idx): ?array
    {
        if (isset($entry['publicationBases']) === false || is_array($entry['publicationBases']) === false) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].publicationBases is required and must be an array', [$idx])
            );
        }

        if (empty($entry['publicationBases']) === true) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].publicationBases must contain at least one basis', [$idx])
            );
        }

        foreach ($entry['publicationBases'] as $base) {
            if (is_string($base) === false) {
                return $this->badRequest(
                    message: $this->l10n->t('Each entry in unredactedEntities[%s].publicationBases must be a string', [$idx])
                );
            }
        }

        return null;

    }//end validatePublicationBases()

    /**
     * Validate the optional contact fields of one entry.
     *
     * @param array<string, mixed> $entry The entry being validated.
     * @param int|string           $idx   The entry's index, used in the error message.
     *
     * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload, or null when valid.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    private function validateContactFields(array $entry, int|string $idx): ?array
    {
        if (isset($entry['contactEmail']) === true && is_string($entry['contactEmail']) === false) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].contactEmail must be a string', [$idx])
            );
        }

        if (isset($entry['contactAddress']) === true && is_string($entry['contactAddress']) === false) {
            return $this->badRequest(
                message: $this->l10n->t('unredactedEntities[%s].contactAddress must be a string', [$idx])
            );
        }

        return null;

    }//end validateContactFields()

    /**
     * Build the HTTP 400 status/body pair for a validation failure.
     *
     * @param string $message The already-translated error message.
     *
     * @return array{status: int, body: array<string, mixed>} The 400 payload.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
     */
    private function badRequest(string $message): array
    {
        return [
            'status' => 400,
            'body'   => ['error' => $message],
        ];

    }//end badRequest()
}//end class
