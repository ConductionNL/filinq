<?php

/**
 * Document Storage Service
 *
 * Single-responsibility service: validate a target path, resolve/create
 * the destination folder for a given Nextcloud user, dedupe the filename
 * using the platform convention, and write a generated document's bytes
 * into that user's Files. Used by both single-document generation
 * (DocumentService::generateDocument()) and the async bulk job
 * (BatchDocumentJob), the latter of which has no live session — only a
 * captured userId string — so this service takes userId explicitly rather
 * than pulling it from IUserSession.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service for storing generated documents in a user's Nextcloud Files
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
 */
class DocumentStorageService
{

    /**
     * HTTP status code used for a targetPath validation failure.
     *
     * @var int
     */
    public const ERROR_CODE_INVALID_PATH = 400;

    /**
     * HTTP status code used for a storage-layer execution failure
     * (quota exceeded, permission denied, or any other Files-layer error
     * encountered after targetPath validation already passed).
     *
     * @var int
     */
    public const ERROR_CODE_STORAGE_FAILURE = 507;

    /**
     * Allowed character set for a single path segment.
     *
     * @var string
     */
    private const SEGMENT_PATTERN = '/^[A-Za-z0-9 _.\-]+$/';

    /**
     * Constructor for DocumentStorageService.
     *
     * @param IRootFolder     $rootFolder Root folder for per-user file operations
     * @param LoggerInterface $logger     Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly IRootFolder $rootFolder,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Validate a targetPath supplied by the caller.
     *
     * Must be relative (no leading '/'), must not contain a '..' path
     * segment, and every path segment must match the allowed charset.
     *
     * @param string $targetPath The target path to validate
     *
     * @return void
     *
     * @throws Exception Code 400 if the path is invalid
     *
     * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
     */
    public function validateTargetPath(string $targetPath): void
    {
        if (trim($targetPath) === '') {
            throw new Exception(
                message: 'options.output.targetPath must not be empty',
                code: self::ERROR_CODE_INVALID_PATH
            );
        }

        if (str_starts_with($targetPath, '/') === true) {
            throw new Exception(
                message: 'options.output.targetPath must be relative (no leading "/")',
                code: self::ERROR_CODE_INVALID_PATH
            );
        }

        $segments = explode('/', $targetPath);
        foreach ($segments as $segment) {
            if ($segment === '') {
                throw new Exception(
                    message: 'options.output.targetPath must not contain empty path segments',
                    code: self::ERROR_CODE_INVALID_PATH
                );
            }

            if ($segment === '.' || $segment === '..') {
                throw new Exception(
                    message: 'options.output.targetPath must not contain "." or ".." path segments',
                    code: self::ERROR_CODE_INVALID_PATH
                );
            }

            if (preg_match(self::SEGMENT_PATTERN, $segment) !== 1) {
                throw new Exception(
                    message: "options.output.targetPath segment '{$segment}' contains disallowed characters",
                    code: self::ERROR_CODE_INVALID_PATH
                );
            }
        }//end foreach

    }//end validateTargetPath()

    /**
     * Store generated document bytes in a user's Files.
     *
     * Validates targetPath, creates the destination folder recursively
     * (idempotent — existing segments are reused, never recreated), dedupes
     * the filename via the platform's own `name (2).ext` convention, and
     * writes the file.
     *
     * @param string $userId     The Nextcloud user id to store the file for
     * @param string $targetPath Relative folder path within the user's Files
     * @param string $filename   The desired filename (extension included)
     * @param string $content    The raw file content to write
     *
     * @return array{fileId: int, path: string, name: string, size: int}
     *
     * @throws Exception Code 400 for an invalid targetPath, code 507 for a
     *                    storage-layer execution failure
     *
     * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-1
     */
    public function store(
        string $userId,
        string $targetPath,
        string $filename,
        string $content
    ): array {
        $this->validateTargetPath(targetPath: $targetPath);

        try {
            $folder     = $this->resolveFolder(userId: $userId, targetPath: $targetPath);
            $uniqueName = $folder->getNonExistingName($filename);
            $file       = $folder->newFile($uniqueName, $content);

            return [
                'fileId' => $file->getId(),
                'path'   => $file->getPath(),
                'name'   => $uniqueName,
                'size'   => $file->getSize(),
            ];
        } catch (Exception $e) {
            if ($e->getCode() === self::ERROR_CODE_STORAGE_FAILURE) {
                // Already a properly-coded/-messaged failure raised by
                // resolveFolder() itself (e.g. "path segment is not a
                // folder") — rethrow unchanged instead of double-wrapping.
                throw $e;
            }

            $this->logger->error(
                message: 'Failed to store generated document: '.$e->getMessage(),
                context: ['userId' => $userId, 'targetPath' => $targetPath, 'exception' => $e]
            );

            throw new Exception(
                message: 'Failed to store generated document in Files: '.$e->getMessage(),
                code: self::ERROR_CODE_STORAGE_FAILURE,
                previous: $e
            );
        } catch (Throwable $e) {
            $this->logger->error(
                message: 'Failed to store generated document: '.$e->getMessage(),
                context: ['userId' => $userId, 'targetPath' => $targetPath, 'exception' => $e]
            );

            throw new Exception(
                message: 'Failed to store generated document in Files: '.$e->getMessage(),
                code: self::ERROR_CODE_STORAGE_FAILURE,
                previous: $e
            );
        }//end try

    }//end store()

    /**
     * Resolve the destination folder for a user, creating any missing
     * path segments idempotently.
     *
     * @param string $userId     The Nextcloud user id
     * @param string $targetPath The relative folder path to resolve/create
     *
     * @return Folder The resolved destination folder
     *
     * @throws Exception Code 507 if a path segment exists but is not a folder
     */
    private function resolveFolder(string $userId, string $targetPath): Folder
    {
        $folder   = $this->rootFolder->getUserFolder($userId);
        $segments = explode('/', trim($targetPath, '/'));

        foreach ($segments as $segment) {
            if ($folder->nodeExists($segment) === false) {
                $folder->newFolder($segment);
            }

            $node = $folder->get($segment);
            if ($node instanceof Folder === false) {
                throw new Exception(
                    message: "Path segment '{$segment}' exists but is not a folder",
                    code: self::ERROR_CODE_STORAGE_FAILURE
                );
            }

            $folder = $node;
        }//end foreach

        return $folder;

    }//end resolveFolder()
}//end class
