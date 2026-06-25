<?php
/**
 * Validation Runner
 *
 * Event-listener fallback that computes a document's validation verdict and
 * stores it, until OpenRegister's ADR-031 calculation runtime invokes
 * DocumentValidationService directly. This runner holds only orchestration
 * (resolve the file, call the service, persist the verdict via MetadataService);
 * all validation logic lives in DocumentValidationService and the event
 * listener itself contains no validation logic.
 *
 * @category  EventListener
 * @package   OCA\DocuDesk\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\DocumentValidationService;
use OCA\DocuDesk\Service\MetadataService;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs document validation as a calculation fallback for DocuDesk objects.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ValidationRunner
{
    /**
     * Compute + store the validation verdict for an OpenRegister object.
     *
     * Resolves the object's `fileId` to a NC file, runs the validation service,
     * and persists `validationStatus` + `validationFindings` via the existing
     * metadata save path. Best-effort: failures are logged, never thrown.
     *
     * @param mixed                     $object            The OpenRegister object.
     * @param DocumentValidationService $validationService The validation service.
     * @param MetadataService           $metadataService   The metadata save path.
     * @param IRootFolder               $rootFolder        Root folder for file access.
     * @param LoggerInterface           $logger            Logger.
     * @param string                    $logContext        Log context.
     *
     * @return void
     *
     * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
     */
    public function validateObject(
        mixed $object,
        DocumentValidationService $validationService,
        MetadataService $metadataService,
        IRootFolder $rootFolder,
        LoggerInterface $logger,
        string $logContext
    ): void {
        try {
            $data   = $object->getObject();
            $fileId = (int) ($data['fileId'] ?? 0);
            if ($fileId <= 0) {
                return;
            }

            $file = $this->resolveFile(rootFolder: $rootFolder, object: $object, fileId: $fileId);
            if ($file === null) {
                return;
            }

            $verdict = $validationService->validate(
                file: $file,
                record: $data,
                documentType: ($data['documentType'] ?? null)
            );

            $metadataService->saveEnrichedMetadata(
                $object->getUuid(),
                (string) $object->getRegister(),
                (string) $object->getSchema(),
                [
                    'validationStatus'   => $verdict['validationStatus'],
                    'validationFindings' => $verdict['validationFindings'],
                ]
            );

            $logger->info(
                'DocuDesk: Validation verdict stored for '.$logContext,
                [
                    'objectId' => $object->getUuid(),
                    'status'   => $verdict['validationStatus'],
                ]
            );
        } catch (Throwable $e) {
            $logger->error(
                'DocuDesk: Failed to validate object for '.$logContext,
                ['exception' => $e->getMessage()]
            );
        }//end try

    }//end validateObject()

    /**
     * Resolve the object's owning user's folder and fetch the file by id.
     *
     * Falls back to the object owner's user folder so background dispatch
     * (no active session) can still resolve the node.
     *
     * @param IRootFolder $rootFolder The root folder.
     * @param mixed       $object     The OpenRegister object (for owner uid).
     * @param int         $fileId     The Nextcloud file id.
     *
     * @return File|null The file or null.
     */
    private function resolveFile(IRootFolder $rootFolder, mixed $object, int $fileId): ?File
    {
        $owner = '';
        if (method_exists($object, 'getOwner') === true) {
            $owner = (string) ($object->getOwner() ?? '');
        }

        if ($owner === '') {
            return null;
        }

        try {
            $userFolder = $rootFolder->getUserFolder($owner);
            $nodes      = $userFolder->getById($fileId);
            if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
                return null;
            }

            return $nodes[0];
        } catch (Throwable $e) {
            return null;
        }

    }//end resolveFile()
}//end class
