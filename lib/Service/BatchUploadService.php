<?php
/**
 * Batch Upload Service
 *
 * Accepts multipart uploads from an anonymization batch request, persists
 * each file via FileUploadService, and hands the resulting file list to
 * BatchStateService to create a new batch record.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\IRequest;

/**
 * Collects and persists uploaded files that make up an anonymization batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchUploadService
{
    /**
     * Constructor for BatchUploadService
     *
     * @param FileUploadService $uploadService Service that persists individual uploaded files.
     * @param BatchStateService $stateService  Service that creates and manages batch records.
     *
     * @return void
     */
    public function __construct(
        private readonly FileUploadService $uploadService,
        private readonly BatchStateService $stateService,
    ) {

    }//end __construct()

    /**
     * Return the current user's identifier.
     *
     * @return string Current user ID, or an empty string when no user is signed in.
     */
    public function getUserId(): string
    {
        return $this->uploadService->getCurrentUserId();

    }//end getUserId()

    /**
     * Collect uploaded files from the request.
     *
     * Supports two multipart conventions: indexed fields (`files0`, `files1`, ...)
     * and a single `files` field carrying an array of uploads.
     *
     * @param IRequest $request Incoming HTTP request holding the multipart uploads.
     *
     * @return array<int, array{name: string, tmp_name: string, error: int}> List of raw uploaded file entries.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     */
    public function collectFiles(IRequest $request): array
    {
        $files = [];
        $index = 0;
        while (true) {
            $file = $request->getUploadedFile('files'.$index);
            if (empty($file) === true || isset($file['tmp_name']) === false) {
                if ($index === 0) {
                    $arr = $request->getUploadedFile('files');
                    if (empty($arr) === false && is_array($arr['tmp_name']) === true) {
                        $fileCount = count($arr['tmp_name']);
                        for ($i = 0; $i < $fileCount; $i++) {
                            $files[] = [
                                'name'     => (string) $arr['name'][$i],
                                'tmp_name' => (string) $arr['tmp_name'][$i],
                                'error'    => (int) $arr['error'][$i],
                            ];
                        }
                    }
                }

                break;
            }

            $files[] = [
                'name'     => (string) ($file['name'] ?? ''),
                'tmp_name' => (string) $file['tmp_name'],
                'error'    => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            ];
            $index++;
        }//end while

        return $files;

    }//end collectFiles()

    /**
     * Persist a set of uploaded files and create a new batch record.
     *
     * Each entry is uploaded through FileUploadService. Failures are recorded
     * on the resulting batch file entry rather than aborting the whole batch.
     *
     * @param string                           $userId User identifier to associate with the new batch.
     * @param array<int, array<string, mixed>> $files  Raw uploaded file entries as produced by collectFiles().
     *
     * @return array<string, mixed> The new batch record as returned by BatchStateService.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     */
    public function processBatchUpload(string $userId, array $files): array
    {
        $batchFiles = [];
        foreach ($files as $uploaded) {
            $base = [
                'fileId'           => null,
                'fileName'         => $uploaded['name'],
                'status'           => 'error',
                'entityCount'      => 0,
                'replacementCount' => 0,
                'error'            => null,
            ];
            if ($uploaded['error'] !== UPLOAD_ERR_OK) {
                $base['error'] = 'Upload error: '.$uploaded['error'];
                $batchFiles[]  = $base;
                continue;
            }

            $content = file_get_contents($uploaded['tmp_name']);
            if ($content === false) {
                $base['error'] = 'Failed to read file';
                $batchFiles[]  = $base;
                continue;
            }

            $result       = $this->uploadService->uploadFile($uploaded['name'], $content);
            $batchFiles[] = [
                'fileId'           => $result['fileId'],
                'fileName'         => $result['fileName'],
                'status'           => 'uploaded',
                'entityCount'      => 0,
                'replacementCount' => 0,
                'error'            => null,
            ];
        }//end foreach

        return $this->stateService->createBatch($userId, $batchFiles);

    }//end processBatchUpload()
}//end class
