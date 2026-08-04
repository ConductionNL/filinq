<?php
/**
 * Folder File Enumerator
 *
 * Resolves the Nextcloud folder a batch is created from (by node ID or by
 * relative path) and enumerates its analysable file children, applying the
 * legacy `_anonymized` output filter and the optional confidentiality-based
 * analysis-priority ordering. Extracted from `FolderBatchService`, which keeps
 * the batch lifecycle itself.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;

/**
 * Resolves a source folder and enumerates its analysable files.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FolderFileEnumerator
{
    /**
     * App config key for the optional confidentiality-based analysis-priority
     * hint. Defaults to off — ordering is byte-for-byte identical to the
     * pre-change behaviour until an admin opts in (files-confidential-labels,
     * design.md D3).
     *
     * @var string
     */
    private const PRIORITISE_ANALYSIS_KEY = 'docudesk.confidentiality.prioritise_analysis';

    /**
     * Constructor for FolderFileEnumerator
     *
     * @param OutputLayoutResolver        $layout               Output-layout helper (used here to
     *                                                          identify legacy `_anonymized` outputs
     *                                                          and exclude them from source discovery).
     * @param ConfidentialityLabelService $confidentialityLabel Read-only files_confidential signal,
     *                                                          used (only when
     *                                                          `docudesk.confidentiality.prioritise_analysis`
     *                                                          is on) as a secondary, tie-breaking
     *                                                          sort key so higher-confidentiality
     *                                                          files are analysed sooner
     *                                                          (files-confidential-labels).
     * @param IAppConfig                  $appConfig            App configuration for the priority-hint flag
     *
     * @return void
     */
    public function __construct(
        private readonly OutputLayoutResolver $layout,
        private readonly ConfidentialityLabelService $confidentialityLabel,
        private readonly IAppConfig $appConfig
    ) {

    }//end __construct()

    /**
     * Resolve the source folder from either a folder ID or folder path
     *
     * Enforces XOR on the inputs (exactly one must be provided). When ID is
     * used, chooses a writable mount first, falling back to the first
     * readable node. When path is used, preserves the existing lookup via
     * Folder::get(). Maps the "not found" case to HTTP 404 for both inputs,
     * and a resolved non-folder node to HTTP 400.
     *
     * @param int|null    $folderId   Node ID of the folder, or null
     * @param string|null $folderPath Relative path of the folder, or null
     * @param Folder      $userFolder The current user's root folder
     *
     * @return Folder The resolved folder
     *
     * @throws Exception If neither/both inputs provided (400), folder not found (404),
     *                   or the resolved node is not a folder (400)
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
     */
    public function resolveFolder(?int $folderId, ?string $folderPath, Folder $userFolder): Folder
    {
        $node = $this->resolveFolderNode(
            folderId: $folderId,
            folderPath: $folderPath,
            userFolder: $userFolder
        );

        if (($node instanceof Folder) === false) {
            throw new Exception('Path is not a folder', 400);
        }

        return $node;

    }//end resolveFolder()

    /**
     * Resolve the folder node from either a folder ID or folder path
     *
     * @param int|null    $folderId   Node ID of the folder, or null
     * @param string|null $folderPath Relative path of the folder, or null
     * @param Folder      $userFolder The current user's root folder
     *
     * @return Node The resolved node (type is validated by the caller)
     *
     * @throws Exception If neither/both inputs provided (400), or folder not found (404)
     *
     * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
     */
    private function resolveFolderNode(?int $folderId, ?string $folderPath, Folder $userFolder): Node
    {
        $hasId   = $folderId !== null;
        $hasPath = $folderPath !== null && $folderPath !== '';

        if ($hasId === false && $hasPath === false) {
            throw new Exception('Either folderId or folderPath must be provided', 400);
        }

        if ($hasId === true && $hasPath === true) {
            throw new Exception('Provide only one of folderId or folderPath', 400);
        }

        if ($hasId === true) {
            $nodes = $userFolder->getById($folderId);
            if (empty($nodes) === true) {
                throw new Exception('Folder not found', 404);
            }

            return $this->pickPreferredNode(nodes: $nodes);
        }

        try {
            return $userFolder->get($folderPath);
        } catch (NotFoundException $e) {
            throw new Exception('Folder not found', 404, $e);
        }

    }//end resolveFolderNode()

    /**
     * Pick the preferred node when getById returns multiple mounts
     *
     * The same file ID can surface through multiple mounts in one user's
     * tree (personal storage + share + group folder). Prefer a writable
     * mount because the batch anonymization flow writes output files back
     * into the source folder; a read-only mount would succeed at extraction
     * but fail at write-back time. Fall back to the first readable node
     * when no writable mount exists — extraction-only use remains valid.
     *
     * @param Node[] $nodes Non-empty array of nodes returned by getById
     *
     * @return Node The preferred node
     */
    private function pickPreferredNode(array $nodes): Node
    {
        foreach ($nodes as $candidate) {
            if (($candidate->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE) {
                return $candidate;
            }
        }

        return $nodes[0];

    }//end pickPreferredNode()

    /**
     * Enumerate direct file children of a folder (flat, no recursion)
     *
     * Files whose base name ends with the legacy `_anonymized` suffix are
     * excluded so a re-run of folder-analysis on a folder that already
     * contains prior anonymisation outputs does not pick up the redacted
     * copies as fresh source material. The discriminator lives on
     * `OutputLayoutResolver::isLegacyAnonymizedOutput()` so the same
     * filter is reused across the folder flow and any future
     * folder-flow integration point.
     *
     * @param Folder $folder The folder to enumerate
     *
     * @return File[] Array of file nodes
     *
     * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
     */
    public function enumerate(Folder $folder): array
    {
        $files = [];
        foreach ($folder->getDirectoryListing() as $node) {
            if ($node instanceof File === false) {
                continue;
            }

            $baseName = pathinfo($node->getName(), PATHINFO_FILENAME);
            if ($this->layout->isLegacyAnonymizedOutput(baseName: $baseName) === true) {
                continue;
            }

            $files[] = $node;
        }

        return $this->applyConfidentialityPriorityOrdering(files: $files);

    }//end enumerate()

    /**
     * Optionally reorder enumerated files by confidentiality level.
     *
     * A pure suggestion signal: when
     * `docudesk.confidentiality.prioritise_analysis` is off (default),
     * returns `$files` untouched — ordering stays byte-for-byte identical to
     * today. When on, sorts by the normalised confidentiality level
     * descending (unlabelled files = level 0), using each file's original
     * position as an explicit, deterministic tie-break — it never skips,
     * blocks or redacts anything, it only reorders the work queue
     * (files-confidential-labels, design.md D3).
     *
     * @param File[] $files Enumerated files, in their original (directory-listing) order
     *
     * @return File[] Files in analysis order
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
     */
    private function applyConfidentialityPriorityOrdering(array $files): array
    {
        if ($this->appConfig->getValueBool('docudesk', self::PRIORITISE_ANALYSIS_KEY, false) === false) {
            return $files;
        }

        $decorated = [];
        foreach (array_values($files) as $index => $file) {
            $level = 0;
            $label = $this->confidentialityLabel->getLabelForFile($file->getId());
            if ($label !== null) {
                $level = $label->getLevel();
            }

            $decorated[] = [
                'level' => $level,
                'index' => $index,
                'file'  => $file,
            ];
        }

        usort(
            $decorated,
            static function (array $a, array $b): int {
                if ($a['level'] !== $b['level']) {
                    return $b['level'] <=> $a['level'];
                }

                return $a['index'] <=> $b['index'];
            }
        );

        return array_map(static fn (array $entry) => $entry['file'], $decorated);

    }//end applyConfidentialityPriorityOrdering()
}//end class
