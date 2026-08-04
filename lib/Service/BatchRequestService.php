<?php
/**
 * Batch Request Service
 *
 * Request- and response-shaping helpers for the batch anonymization endpoints:
 * folder-parameter coercion and XOR validation, anonymize-body validation,
 * placeholder-scope resolution, batch progress aggregation, and the multi-status
 * mapping for a partially-successful batch. Extracted from
 * BatchAnonymizationController so the controller stays a thin HTTP boundary.
 *
 * Every method returns the `{status, body}` pair used elsewhere in this app
 * (see AnonymizationService::applyRelationSkipDecision) rather than an HTTP
 * response object, so the HTTP layer stays in the controller.
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
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\AppFramework\Http;
use OCP\IL10N;

/**
 * Validation and aggregation helpers for the batch anonymization endpoints.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */
class BatchRequestService
{

    /**
     * Validator for the shared `unredactedEntities[]` payload shape.
     *
     * @var UnredactedEntitiesValidator
     */
    private readonly UnredactedEntitiesValidator $unredactedValidator;

    /**
     * Constructor for BatchRequestService
     *
     * @param IL10N $l10n Translator for the user-facing validation messages.
     *
     * @return void
     */
    public function __construct(
        private readonly IL10N $l10n
    ) {
        $this->unredactedValidator = new UnredactedEntitiesValidator($l10n);

    }//end __construct()

    /**
     * Validate the batch-anonymize request body up to the outputFormat gate.
     *
     * The unredactedEntities entries themselves are validated separately (see
     * validateUnredactedEntries) because the controller resolves the
     * outputFormat in between, and that ordering determines which HTTP 400 a
     * doubly-malformed body receives.
     *
     * @param array<string, mixed> $params Request parameters.
     *
     * @return array{error: array{status: int, body: array<string, mixed>}|null,
     *               request: array<string, mixed>|null} The first validation failure, or the
     *                                                   normalised request under `request`.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
     */
    public function validateBody(array $params): array
    {
        $entities = ($params['entities'] ?? []);
        if (is_array($entities) === false || empty($entities) === true) {
            return $this->rejected(error: $this->badRequest(message: $this->l10n->t('No entities provided')));
        }

        $appendBasisSummary = false;
        if (array_key_exists('appendBasisSummary', $params) === true) {
            $appendBasisSummary = $params['appendBasisSummary'];
            if (is_bool($appendBasisSummary) === false) {
                return $this->rejected(
                    error: $this->badRequest(message: $this->l10n->t('appendBasisSummary must be a boolean'))
                );
            }
        }

        $unredacted = ($params['unredactedEntities'] ?? []);
        if (is_array($unredacted) === false) {
            return $this->rejected(
                error: $this->badRequest(message: $this->l10n->t('unredactedEntities must be an array'))
            );
        }

        return [
            'error'   => null,
            'request' => [
                'entities'           => $entities,
                'hasStrayBases'      => $this->hasStrayBases(entities: $entities),
                'appendBasisSummary' => $appendBasisSummary,
                'unredactedEntities' => $unredacted,
            ],
        ];

    }//end validateBody()

    /**
     * Validate the structure of each unredactedEntities[] entry.
     *
     * Mirrors the single-file anonymize endpoint so the batch endpoint rejects
     * malformed payloads with HTTP 400 before forwarding to the service layer.
     *
     * @param array<int, mixed> $entries The unredactedEntities array from the request.
     *
     * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
     *                                                             invalid entry, null when all valid.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
     */
    public function validateUnredactedEntries(array $entries): ?array
    {
        return $this->unredactedValidator->validate(entries: $entries);

    }//end validateUnredactedEntries()

    /**
     * Resolve the placeholder-numbering scope for a batch.
     *
     * A batch IS a folder/dossier, so the default is 'dossier' — a person gets
     * the same scope-local number across all the batch's files. Any value other
     * than 'document' keeps the dossier default.
     *
     * @param array<string, mixed> $params Request parameters.
     *
     * @return string Either 'dossier' or 'document'.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
     */
    public function resolveScope(array $params): string
    {
        if ((string) ($params['scope'] ?? 'dossier') === 'document') {
            return 'document';
        }

        return 'dossier';

    }//end resolveScope()

    /**
     * Resolve the HTTP status for a batch anonymization result.
     *
     * HTTP 200 — all files processed without prohibition failures.
     * HTTP 207 — some files had per-file 422 prohibition violations; others succeeded.
     * HTTP 422 — every processed file had a prohibition violation (none succeeded).
     *
     * @param array<string, mixed> $result Batch anonymization result.
     *
     * @return int HTTP status code.
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
     */
    public function resolveHttpStatus(array $result): int
    {
        $prohibitionFiles = (int) ($result['prohibitionSkippedFiles'] ?? 0);
        $conversionFails  = (int) ($result['conversionFailures'] ?? 0);
        $processed        = (int) ($result['processedFiles'] ?? 0);
        $total            = (int) ($result['totalFiles'] ?? 0);

        // Files that could not be anonymised for any documented reason
        // (prohibition carve-out or conversion-cascade exhaustion).
        $failedFiles = ($prohibitionFiles + $conversionFails);

        // Every file succeeded — straightforward 200.
        if ($failedFiles === 0) {
            return Http::STATUS_OK;
        }

        // Nothing was processed and every file failed — 422 Unprocessable.
        if ($processed === 0 && $failedFiles >= $total && $total > 0) {
            return Http::STATUS_UNPROCESSABLE_ENTITY;
        }

        // Mixed outcome (some succeeded, some failed) — 207 Multi-Status.
        return Http::STATUS_MULTI_STATUS;

    }//end resolveHttpStatus()

    /**
     * Aggregate a batch's per-file entries into the status snapshot.
     *
     * @param array<string, mixed> $batch The stored batch record.
     *
     * @return array{totalEntities: int, progress: float|int, totalFiles: int} The snapshot counters.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
     */
    public function summariseBatch(array $batch): array
    {
        $ent = 0;
        $ext = 0;
        foreach ($batch['files'] as $f) {
            $ent += ($f['entityCount'] ?? 0);
            if (in_array($f['status'], ['extracted', 'anonymized', 'error'], true) === true) {
                $ext++;
            }
        }

        $total = count($batch['files']);
        $prog  = 0;
        if ($total > 0) {
            $prog = round((($ext / $total) * 100), 1);
        }

        return [
            'totalEntities' => $ent,
            'progress'      => $prog,
            'totalFiles'    => $total,
        ];

    }//end summariseBatch()

    /**
     * Count the files of a batch whose extraction has finished (or failed).
     *
     * @param array<string, mixed> $batch The stored batch record.
     *
     * @return int Number of files in a terminal extraction state.
     *
     * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
     */
    public function countProcessedFiles(array $batch): int
    {
        $filesProcessed = 0;
        foreach ($batch['files'] as $f) {
            if (in_array($f['status'], ['extracted', 'error'], true) === true) {
                $filesProcessed++;
            }
        }

        return $filesProcessed;

    }//end countProcessedFiles()

    /**
     * Coerce and XOR-validate the folder-batch request parameters.
     *
     * @param mixed $rawFolderId   Raw `folderId` param value from the request.
     * @param mixed $rawFolderPath Raw `folderPath` param value from the request.
     *
     * @return array{folderId: int|null, folderPath: string|null,
     *               error: array{status: int, body: array<string, mixed>}|null} The coerced params,
     *                                                                          plus the validation
     *                                                                          failure when both or
     *                                                                          neither were supplied.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     */
    public function resolveFolderParams(mixed $rawFolderId, mixed $rawFolderPath): array
    {
        $folderId   = $this->coerceFolderId(raw: $rawFolderId);
        $folderPath = $this->coerceFolderPath(raw: $rawFolderPath);

        return [
            'folderId'   => $folderId,
            'folderPath' => $folderPath,
            'error'      => $this->validateFolderParams(folderId: $folderId, folderPath: $folderPath),
        ];

    }//end resolveFolderParams()

    /**
     * Coerce the raw folderId request param to an int, or null when absent/empty.
     *
     * @param mixed $raw Raw param value from the request.
     *
     * @return int|null Integer folder ID, or null when the caller did not supply one.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     */
    private function coerceFolderId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;

    }//end coerceFolderId()

    /**
     * Coerce the raw folderPath request param to a string, or null when absent/empty.
     *
     * @param mixed $raw Raw param value from the request.
     *
     * @return string|null Path string, or null when the caller did not supply one.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     */
    private function coerceFolderPath(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (string) $raw;

    }//end coerceFolderPath()

    /**
     * Validate XOR between folderId and folderPath at the controller boundary.
     *
     * @param int|null    $folderId   Coerced folder ID.
     * @param string|null $folderPath Coerced folder path.
     *
     * @return array{status: int, body: array<string, mixed>}|null Error payload when validation
     *                                                             fails, null when OK.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     */
    private function validateFolderParams(?int $folderId, ?string $folderPath): ?array
    {
        if ($folderId === null && $folderPath === null) {
            return $this->badRequest(
                message: $this->l10n->t('Either folderId or folderPath must be provided')
            );
        }

        if ($folderId !== null && $folderPath !== null) {
            return $this->badRequest(
                message: $this->l10n->t('Provide only one of folderId or folderPath')
            );
        }

        return null;

    }//end validateFolderParams()

    /**
     * Detect stray `bases[]` fields on entity entries.
     *
     * Bases are set through OpenRegister's PATCH /api/entity-relations/{id};
     * a stray field on the anonymize payload is ignored but reported back as
     * `ignoredFields` for GDPR accountability.
     *
     * @param array<int, mixed> $entities The submitted entity entries.
     *
     * @return bool True when at least one entry carries a `bases` key.
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
     */
    private function hasStrayBases(array $entities): bool
    {
        foreach ($entities as $entity) {
            if (is_array($entity) === true && array_key_exists('bases', $entity) === true) {
                return true;
            }
        }

        return false;

    }//end hasStrayBases()

    /**
     * Wrap a validation failure in the validateBody() return shape.
     *
     * @param array{status: int, body: array<string, mixed>} $error The failing status/body pair.
     *
     * @return array{error: array{status: int, body: array<string, mixed>}, request: null} The rejection.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
     */
    private function rejected(array $error): array
    {
        return ['error' => $error, 'request' => null];

    }//end rejected()

    /**
     * Build the HTTP 400 status/body pair for a validation failure.
     *
     * @param string $message The already-translated error message.
     *
     * @return array{status: int, body: array<string, mixed>} The 400 payload.
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
     */
    private function badRequest(string $message): array
    {
        return [
            'status' => 400,
            'body'   => ['error' => $message],
        ];

    }//end badRequest()
}//end class
