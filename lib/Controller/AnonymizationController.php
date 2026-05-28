<?php
/**
 * Anonymization Controller
 *
 * Controller for the document anonymization pipeline.
 * Provides endpoints for uploading files, extracting/detecting entities,
 * and anonymizing documents.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-1
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\FileListingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for anonymization pipeline endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationController extends Controller
{
    /**
     * Constructor for AnonymizationController
     *
     * @param string               $appName              The application name
     * @param IRequest             $request              The request object
     * @param LoggerInterface      $logger               Logger for error reporting
     * @param AnonymizationService $anonymizationService Service for anonymization operations
     * @param FileListingService   $fileListingService   Service for file listing operations
     * @param IL10N                $l10n                 The localization service
     * @param IUserSession         $userSession          User session for authentication
     * @param IRootFolder          $rootFolder           Root folder for file access checks
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonymizationService,
        private readonly FileListingService $fileListingService,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession,
        private readonly IRootFolder $rootFolder,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * List all processed files with entity counts and status
     *
     * Returns files from the user's DocuDesk folder with their
     * entity detection counts and anonymization status.
     *
     * @return JSONResponse JSON response with array of file data
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-1
     */
    public function files(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $result = $this->fileListingService->listProcessedFiles();

            return new JSONResponse($result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                'Failed to list processed files: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to list processed files: %s', [$e->getMessage()])],
                $statusCode
            );
        }//end try

    }//end files()

    /**
     * Upload a file to the user's DocuDesk folder
     *
     * Reads the uploaded file from the request and saves it
     * to the user's DocuDesk folder.
     *
     * @return JSONResponse JSON response with upload result
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
     */
    public function upload(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $file = $this->request->getUploadedFile('file');

            if (empty($file) === true || isset($file['tmp_name']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('No file uploaded')],
                    400
                );
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('File upload failed with error code: %s', [$file['error']])],
                    400
                );
            }

            $fileName    = $file['name'];
            $fileContent = file_get_contents($file['tmp_name']);

            if ($fileContent === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Failed to read uploaded file')],
                    500
                );
            }

            $result = $this->fileListingService->uploadFile($fileName, $fileContent);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $statusCode = 500;
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            $this->logger->error(
                'Failed to upload file: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to upload file: %s', [$e->getMessage()])],
                $statusCode
            );
        }//end try

    }//end upload()

    /**
     * Verify the current user has access to the given file ID
     *
     * Resolves the file via the user's own file tree so that an authenticated
     * user cannot operate on files they do not own (security finding C3 —
     * file IDOR). Returns 404 on failure so callers cannot probe for existence.
     *
     * @param int $fileId The Nextcloud file ID to check
     *
     * @return JSONResponse|null Null when access is granted, 404 response otherwise
     */
    private function verifyFileAccess(int $fileId): ?JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                ['error' => $this->l10n->t('Not authenticated')],
                Http::STATUS_UNAUTHORIZED
            );
        }

        $nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
        if (empty($nodes) === true) {
            return new JSONResponse(
                ['error' => $this->l10n->t('File not found')],
                Http::STATUS_NOT_FOUND
            );
        }

        if (($nodes[0] instanceof File) === false) {
            return new JSONResponse(
                ['error' => $this->l10n->t('File not found')],
                Http::STATUS_NOT_FOUND
            );
        }

        return null;

    }//end verifyFileAccess()

    /**
     * Extract text and detect entities in a file
     *
     * Runs text extraction and entity recognition on the specified file.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return JSONResponse JSON response with extraction and detection results
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
     */
    public function extract(int $fileId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            $accessError = $this->verifyFileAccess(fileId: $fileId);
            if ($accessError !== null) {
                return $accessError;
            }

            $result = $this->anonymizationService->extractAndDetectEntities($fileId);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract and detect entities: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to extract and detect entities: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end extract()

    /**
     * Anonymize entities in a document
     *
     * Replaces detected entities in the document with anonymized placeholders.
     * Supports optional excludeTypes, minConfidence, appendBasisSummary, and
     * outputFormat parameters. Each entity may carry an optional `bases[]` array
     * (array of strings) that is forwarded verbatim to OpenRegister.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return JSONResponse JSON response with anonymization result
     *
     * @NoAdminRequired
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
     */
    public function anonymize(int $fileId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
            }

            // Validate request body BEFORE file-access checks so that malformed
            // input always yields HTTP 400 regardless of whether the file exists.
            $params   = $this->request->getParams();
            $entities = $params['entities'] ?? [];

            if (is_array($entities) === false || empty($entities) === true) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('No entities provided for anonymization')],
                    400
                );
            }

            $basesError = $this->validateEntityBases(entities: $entities);
            if ($basesError !== null) {
                return $basesError;
            }

            $appendBasisSummary = $this->extractAppendBasisSummary(params: $params);
            if ($appendBasisSummary instanceof JSONResponse) {
                return $appendBasisSummary;
            }

            $accessError = $this->verifyFileAccess(fileId: $fileId);
            if ($accessError !== null) {
                return $accessError;
            }

            $outputFormat = 'pdf';
            if (isset($params['outputFormat']) === true) {
                $outputFormat = (string) $params['outputFormat'];
            }

            $entities = $this->filterByExcludeTypes(entities: $entities, params: $params);
            $entities = $this->filterByConfidence(entities: $entities, params: $params);

            $result = $this->anonymizationService->anonymizeDocument(
                fileId: $fileId,
                entities: $entities,
                appendBasisSummary: $appendBasisSummary,
                outputFormat: $outputFormat
            );

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to anonymize document: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end anonymize()

    /**
     * Extract and validate the appendBasisSummary flag from request params.
     *
     * Returns a JSONResponse (HTTP 400) when the field is present but not boolean.
     *
     * @param array<string, mixed> $params Request parameters
     *
     * @return bool|JSONResponse False when omitted, true when set, 400 response on type error.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
     */
    private function extractAppendBasisSummary(array $params): bool|JSONResponse
    {
        if (array_key_exists('appendBasisSummary', $params) === false) {
            return false;
        }

        $value = $params['appendBasisSummary'];
        if (is_bool($value) === false) {
            return new JSONResponse(
                ['error' => $this->l10n->t('appendBasisSummary must be a boolean')],
                400
            );
        }

        return $value;

    }//end extractAppendBasisSummary()

    /**
     * Validate that each entity's optional `bases` field is an array of strings
     *
     * Returns a 400 JSONResponse on the first malformed entry, null when valid.
     *
     * @param array<int, array<string, mixed>> $entities The entities to validate
     *
     * @return JSONResponse|null Error response or null when all bases are valid
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-1
     */
    private function validateEntityBases(array $entities): ?JSONResponse
    {
        foreach ($entities as $entity) {
            if (isset($entity['bases']) === false) {
                continue;
            }

            if (is_array($entity['bases']) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Each entity bases field must be an array of strings')],
                    400
                );
            }

            foreach ($entity['bases'] as $base) {
                if (is_string($base) === false) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('Each entry in entity bases must be a string')],
                        400
                    );
                }
            }
        }//end foreach

        return null;

    }//end validateEntityBases()

    /**
     * Filter entities by excluded types
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
     */
    private function filterByExcludeTypes(array $entities, array $params): array
    {
        $excludeTypes = $params['excludeTypes'] ?? [];
        if (is_array($excludeTypes) === false || empty($excludeTypes) === true) {
            return $entities;
        }

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($excludeTypes): bool {
                    $type = $entity['type'] ?? $entity['entityType'] ?? '';
                    return in_array($type, $excludeTypes, true) === false;
                }
            )
        );

    }//end filterByExcludeTypes()

    /**
     * Filter entities by minimum confidence threshold
     *
     * @param array<int, array<string, mixed>> $entities The entities
     * @param array<string, mixed>             $params   Request parameters
     *
     * @return array<int, array<string, mixed>> Filtered entities
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
     */
    private function filterByConfidence(array $entities, array $params): array
    {
        if (isset($params['minConfidence']) === false) {
            return $entities;
        }

        $minConfidence = (float) $params['minConfidence'];

        return array_values(
            array_filter(
                $entities,
                static function (array $entity) use ($minConfidence): bool {
                    return (float) ($entity['confidence'] ?? 0.0) >= $minConfidence;
                }
            )
        );

    }//end filterByConfidence()
}//end class
