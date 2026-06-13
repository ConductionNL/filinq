<?php

/**
 * Grondslagen Summary Service
 *
 * Renders per-document and per-dossier grondslagen summary PDFs.
 * Per-document summaries append a grondslag page to an anonymised PDF (PDF/A-3b)
 * or save a separate summary PDF for preserve-mode output. Per-dossier summaries
 * aggregate entity-grondslag data across all files and write a standalone PDF/A-3b
 * to the dossier folder.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-1
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-2
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-3
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-4
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Service for rendering grondslagen summary PDFs
 *
 * Handles both per-document append flow (for PDF/A-3b anonymised output)
 * and per-dossier standalone summary generation. Uses PdfService (Twig + mPDF)
 * for rendering and mPDF + FPDI for appending pages to existing PDFs.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-1
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-3
 */
class GrondslagenSummaryService
{

    /**
     * DocuDesk register slug for OR object lookups.
     *
     * @var string
     */
    private const REGISTER = 'docudesk';

    /**
     * Dossier schema slug.
     *
     * @var string
     */
    private const DOSSIER_SCHEMA = 'dossier';

    /**
     * Base schema slug (grondslagen catalogue entries).
     *
     * @var string
     */
    private const BASE_SCHEMA = 'base';

    /**
     * Destination filename inside the anonymised subfolder.
     *
     * @var string
     */
    private const DOSSIER_SUMMARY_FILENAME = 'grondslagen.pdf';

    /**
     * Primary destination subfolder (per anonymisation-output-folder-layout).
     *
     * @var string
     */
    private const ANONYMISED_SUBFOLDER = 'anonymised';

    /**
     * Constructor for GrondslagenSummaryService
     *
     * @param PdfService         $pdfService  PDF rendering service (Twig + mPDF)
     * @param ContainerInterface $container   DI container for lazy OR service resolution
     * @param IAppManager        $appManager  App manager for OR availability check
     * @param LoggerInterface    $logger      Logger for diagnostics
     * @param IRootFolder        $rootFolder  Nextcloud root folder for file operations
     * @param IUserSession       $userSession User session for operator identification
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-1
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly LoggerInterface $logger,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
    ) {

    }//end __construct()

    /**
     * Append a grondslagen summary page to an anonymised PDF in-place.
     *
     * Renders the per-document summary template and appends it to the existing
     * PDF/A-3b anonymised file using mPDF + FPDI. Writes the result back to the
     * same NC file node atomically. Throws on render/append failure so the caller
     * (AnonymizationService::tryAppendBasisSummary) can surface a non-fatal warning.
     *
     * @param mixed $node Nextcloud file node of the anonymised PDF
     *
     * @return void
     *
     * @throws Exception On rendering or file-write failure
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-3
     */
    public function appendSummaryToPdf(mixed $node): void
    {
        $fileId   = (int) $node->getId();
        $entities = $this->loadAnonymisedEntitiesForFile(fileId: $fileId);
        $html     = $this->renderPerDocSummaryHtml(node: $node, entities: $entities);

        $tempInput = tempnam(dir: '/tmp/mpdf', prefix: 'grondsl_in_');

        try {
            // Write current file content to a temp file for FPDI to read.
            $sourceContent = $node->getContent();
            file_put_contents(filename: $tempInput, data: $sourceContent);

            $pdfContent = $this->buildAppendedPdf(
                sourcePath: $tempInput,
                summaryHtml: $html
            );

            // Atomically replace the file content in NC.
            $node->putContent(data: $pdfContent);
        } finally {
            if (file_exists(filename: $tempInput) === true) {
                unlink(filename: $tempInput);
            }
        }//end try

    }//end appendSummaryToPdf()

    /**
     * Render a summary as a separate PDF alongside the anonymised preserve-mode file.
     *
     * Saves a file `<original-base>_anonymized_grondslagen.pdf` in the same folder
     * as the anonymised file. Returns file metadata (fileId + filePath).
     *
     * @param mixed $node Nextcloud file node of the anonymised preserve-mode file
     *
     * @return array{fileId: int|null, filePath: string|null} Saved file metadata
     *
     * @throws Exception On rendering or file-write failure
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-4
     */
    public function appendSummaryAsSeparatePdf(mixed $node): array
    {
        $fileId   = (int) $node->getId();
        $entities = $this->loadAnonymisedEntitiesForFile(fileId: $fileId);
        $html     = $this->renderPerDocSummaryHtml(node: $node, entities: $entities);

        $pdfContent = $this->pdfService->renderPdf(
            templateContent: $html,
            data: [],
            options: ['pdfa' => true, 'title' => 'Grondslagen samenvatting']
        );

        // Derive summary filename from the anonymised file's name.
        $originalName = $node->getName();
        // phpcs:disable CustomSn.Functions.NamedParameters - pathinfo() has no named params in PHP 8.3
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        // phpcs:enable CustomSn.Functions.NamedParameters
        $summaryName = $baseName.'_grondslagen.pdf';

        $parentFolder = $node->getParent();
        $savedNode    = $this->saveFileToFolder(
            folder: $parentFolder,
            fileName: $summaryName,
            content: $pdfContent
        );

        $savedFileId   = null;
        $savedFilePath = null;
        if ($savedNode !== null) {
            $savedFileId   = $savedNode->getId();
            $savedFilePath = $savedNode->getPath();
        }

        return [
            'fileId'   => $savedFileId,
            'filePath' => $savedFilePath,
        ];

    }//end appendSummaryAsSeparatePdf()

    /**
     * Authorise the session user for the given dossier before rendering.
     *
     * Resolves the dossier through OpenRegister's ObjectService under the
     * caller's view; OR's standard RBAC governs visibility. A dossier the
     * caller may not read resolves to null and we deny by throwing. Throwing
     * here is caught by the controller and surfaced as an HTTP error.
     *
     * @param string $dossierId Dossier UUID
     *
     * @return void
     *
     * @throws RuntimeException When the caller may not access the dossier
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
     */
    public function authorizeAccess(string $dossierId): void
    {
        // Resolve the dossier through OpenRegister's ObjectService. The lookup
        // runs under the session user's view, so OR's standard RBAC governs
        // visibility: a dossier the caller may not read resolves to null (or
        // raises), and we deny by throwing. A successful resolution means the
        // operator is permitted to (re)generate the dossier summary.
        $objectService = $this->getObjectService();
        $dossier       = $objectService->find(
            id: $dossierId,
            register: self::REGISTER,
            schema: self::DOSSIER_SCHEMA
        );

        if ($dossier === null) {
            throw new RuntimeException('Access denied or dossier not found: '.$dossierId, 403);
        }

    }//end authorizeAccess()

    /**
     * Render the per-dossier grondslagen summary PDF and write it to the dossier folder.
     *
     * Walks all files under the dossier's folder, loads anonymised entities for each,
     * aggregates per-document and per-grondslag tables, renders the summary, and saves
     * the result to `<dossier-folder>/anonymised/grondslagen.pdf` (fallback:
     * `<dossier-folder>/grondslagen.pdf`). Updates `configuration.grondslagen.fileId`
     * and `configuration.grondslagen.lastGeneratedAt` on the dossier object.
     *
     * An empty dossier (no anonymised files) produces a valid near-empty PDF.
     *
     * @param string $dossierUuid Dossier UUID
     *
     * @return \OCA\OpenRegister\Db\ObjectEntity|null Saved dossier object
     *
     * @throws Exception On fatal render failure (dossier-not-found, save error)
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
     */
    public function renderDossierSummary(string $dossierUuid): mixed
    {
        $objectService = $this->getObjectService();
        $dossier       = $objectService->find(
            id: $dossierUuid,
            register: self::REGISTER,
            schema: self::DOSSIER_SCHEMA
        );

        if ($dossier === null) {
            throw new RuntimeException('Dossier not found: '.$dossierUuid, 404);
        }

        $dossierData   = $dossier->getObject();
        $dossierFolder = $dossier->getFolder();
        $perDocData    = $this->collectDossierEntityData(dossierFolder: $dossierFolder);
        $perGrondslag  = $this->aggregatePerGrondslag(perDocData: $perDocData);
        $generatedAt   = gmdate('Y-m-d\TH:i:s\Z');

        $templateContent = $this->getTemplateContent(filename: 'summary_per_dossier.twig');
        $html            = $this->pdfService->renderPdf(
            templateContent: $templateContent,
            data: [
                'dossierName'        => $dossierData['name'] ?? $dossierData['title'] ?? '',
                'dossierDescription' => $dossierData['description'] ?? '',
                'checkedOn'          => $dossierData['checkedOn'] ?? null,
                'generatedAt'        => $generatedAt,
                'documents'          => $perDocData,
                'perGrondslag'       => $perGrondslag,
                'totalEntities'      => array_sum(array_column(array: $perDocData, column_key: 'entityCount')),
                'totalBases'         => count($perGrondslag),
            ],
            options: ['pdfa' => true, 'title' => 'Grondslagen overzicht']
        );

        // Write summary PDF to dossier folder.
        $savedNode   = $this->writeDossierSummaryFile(
            dossierFolder: $dossierFolder,
            content: $html
        );
        $savedFileId = null;
        if ($savedNode !== null) {
            $savedFileId = $savedNode->getId();
        }

        // Update dossier configuration.grondslagen fields.
        $configuration = $dossierData['configuration'] ?? [];
        if (is_array($configuration) === false) {
            $configuration = [];
        }

        if (isset($configuration['grondslagen']) === false || is_array($configuration['grondslagen']) === false) {
            $configuration['grondslagen'] = [];
        }

        $configuration['grondslagen']['fileId']          = $savedFileId;
        $configuration['grondslagen']['lastGeneratedAt'] = $generatedAt;

        $dossierData['configuration'] = $configuration;

        return $objectService->saveObject(
            object: $dossierData,
            register: self::REGISTER,
            schema: self::DOSSIER_SCHEMA
        );

    }//end renderDossierSummary()

    /**
     * Resolve an array of base UUIDs to human-readable names.
     *
     * Queries DocuDesk's `dossier` register's `base` schema via ObjectService.
     * Unresolvable UUIDs produce a labelled placeholder. Null input produces a
     * "no grondslag recorded" placeholder.
     *
     * @param array<string>|null $baseUuids Array of base UUIDs, or null
     *
     * @return array<string> Resolved base names or placeholder strings
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-1
     */
    public function resolveBaseLabels(?array $baseUuids): array
    {
        if ($baseUuids === null) {
            return ['⟨geen grondslag vastgelegd⟩'];
        }

        if (empty($baseUuids) === true) {
            return ['⟨geen grondslag vastgelegd⟩'];
        }

        $labels = [];
        foreach ($baseUuids as $uuid) {
            $ref          = (string) $uuid;
            $labels[$ref] = $this->resolveSingleBase(uuid: $ref);
        }

        return $labels;

    }//end resolveBaseLabels()

    /**
     * Load anonymised entities for a single NC file.
     *
     * Queries OR's EntityRelationMapper for the file, filters rows where
     * `anonymized = true`, and returns a structured array for template use.
     * Bases are read defensively (entity-relation-grondslagen may not have landed).
     *
     * @param int $fileId Nextcloud file ID
     *
     * @return array<int, array{entityText: string, entityType: string, anonymizedValue: string, bases: array<string>|null}> Entity data
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-1
     */
    public function loadAnonymisedEntitiesForFile(int $fileId): array
    {
        $mapper = $this->getEntityRelationMapper();

        // FindEntitiesForFile joins entity table for type + value data.
        $joined = $mapper->findEntitiesForFile(fileId: $fileId);

        // FindByFileId returns EntityRelation objects with anonymized + bases fields.
        $relations = $mapper->findByFileId(fileId: $fileId);

        // Index EntityRelation objects by entity_id; keep last (highest position).
        $anonymizedByEntityId = [];
        foreach ($relations as $rel) {
            if ($rel->getAnonymized() !== true) {
                continue;
            }

            $eid = (int) $rel->getEntityId();
            $anonymizedByEntityId[$eid] = $rel;
        }

        // Merge join data + anonymized flag; deduplicate by entity_id.
        $seenEntityIds = [];
        $result        = [];

        foreach ($joined as $row) {
            $entityId = (int) ($row['entity_id'] ?? 0);
            if ($entityId === 0 || isset($anonymizedByEntityId[$entityId]) === false) {
                continue;
            }

            if (isset($seenEntityIds[$entityId]) === true) {
                continue;
            }

            $seenEntityIds[$entityId] = true;
            $relation = $anonymizedByEntityId[$entityId];
            $bases    = $this->getBasesFromRelation(relation: $relation);

            $result[] = [
                'entityText'      => (string) ($row['entity_value'] ?? ''),
                'entityType'      => (string) ($row['entity_type'] ?? ''),
                'anonymizedValue' => (string) ($relation->getAnonymizedValue() ?? ''),
                'bases'           => $bases,
            ];
        }//end foreach

        return $result;

    }//end loadAnonymisedEntitiesForFile()

    /**
     * Read bases from an EntityRelation object defensively.
     *
     * Returns the bases array when entity-relation-grondslagen has landed
     * (getBases() method exists), null otherwise (no grondslag recorded).
     *
     * @param \OCA\OpenRegister\Db\EntityRelation $relation The entity relation
     *
     * @return array<string>|null Bases array or null
     */
    private function getBasesFromRelation(mixed $relation): ?array
    {
        if (method_exists(object_or_class: $relation, method: 'getBases') === true) {
            $bases = $relation->getBases();
            if (is_array($bases) === true) {
                return $bases;
            }
        }

        return null;

    }//end getBasesFromRelation()

    /**
     * Resolve a single base UUID to its human-readable name.
     *
     * @param string $uuid Base UUID
     *
     * @return string Resolved name or placeholder
     */
    private function resolveSingleBase(string $uuid): string
    {
        try {
            $objectService = $this->getObjectService();
            $base          = $objectService->find(
                id: $uuid,
                register: self::REGISTER,
                schema: self::BASE_SCHEMA
            );

            if ($base === null) {
                return $this->buildUnresolvedPlaceholder(uuid: $uuid);
            }

            $data = $base->getObject();
            $name = $data['name'] ?? $data['title'] ?? null;

            if ($name === null || (string) $name === '') {
                return $this->buildUnresolvedPlaceholder(uuid: $uuid);
            }

            return (string) $name;
        } catch (\Throwable $e) {
            $this->logger->debug(
                message: 'Could not resolve base UUID: '.$uuid,
                context: ['exception' => $e->getMessage()]
            );
            return $this->buildUnresolvedPlaceholder(uuid: $uuid);
        }//end try

    }//end resolveSingleBase()

    /**
     * Build the "grondslag verwijderd" placeholder for an unresolvable UUID.
     *
     * @param string $uuid The UUID that could not be resolved
     *
     * @return string Placeholder string carrying the unresolved ref
     */
    private function buildUnresolvedPlaceholder(string $uuid): string
    {
        $message = '⟨grondslag verwijderd: '.$uuid.'⟩';

        $this->logger->warning(
            message: 'Unresolved base UUID in grondslagen summary',
            context: ['uuid' => $uuid]
        );

        return $message;

    }//end buildUnresolvedPlaceholder()

    /**
     * Render the per-document summary HTML with resolved base labels.
     *
     * @param mixed $node     File node (for header metadata)
     * @param array $entities Anonymised entities (entityText, entityType, anonymizedValue, bases)
     *
     * @return string Rendered HTML (ready for PdfService)
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-2
     */
    private function renderPerDocSummaryHtml(mixed $node, array $entities): string
    {
        // Resolve base labels for each entity.
        $enriched = [];
        foreach ($entities as $entity) {
            $entity['baseLabels'] = $this->resolveBaseLabels(baseUuids: $entity['bases']);
            $enriched[]           = $entity;
        }

        $distinctBases = $this->collectDistinctBases(entities: $enriched);

        $templateContent = $this->getTemplateContent(filename: 'summary_per_doc.twig');

        $user        = $this->userSession->getUser();
        $operatorUid = 'unknown';
        if ($user !== null) {
            $operatorUid = $user->getUID();
        }

        if ($templateContent === '') {
            return '';
        }

        return $this->pdfService->renderHtmlPreview(
            templateContent: $templateContent,
            data: [
                'fileName'           => $node->getName(),
                'anonymisedAt'       => gmdate('Y-m-d\TH:i:s\Z'),
                'operator'           => $operatorUid,
                'toolName'           => 'OpenAnonymiser via OpenRegister',
                'entities'           => $enriched,
                'entityCount'        => count($enriched),
                'distinctBases'      => $distinctBases,
                'distinctBasesCount' => count($distinctBases),
            ]
        );

    }//end renderPerDocSummaryHtml()

    /**
     * Build the appended PDF: existing pages + summary page.
     *
     * Uses mPDF + FPDI to import pages from the source PDF and append
     * a new page with the summary HTML content.
     *
     * @param string $sourcePath  Absolute path to the existing anonymised PDF
     * @param string $summaryHtml HTML content for the summary page
     *
     * @return string Combined PDF binary content
     *
     * @throws MpdfException On mPDF/FPDI failure
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-3
     */
    private function buildAppendedPdf(string $sourcePath, string $summaryHtml): string
    {
        $tempDir = '/tmp/mpdf';
        if (file_exists(filename: $tempDir) === false) {
            mkdir(directory: $tempDir, permissions: 0777, recursive: true);
        }

        $fontDir = dirname(path: __DIR__).'/Fonts';
        $config  = [
            'tempDir'  => $tempDir,
            'PDFA'     => true,
            'PDFAauto' => true,
        ];

        if (is_dir(filename: $fontDir) === true) {
            $config['fontDir']      = [$fontDir];
            $config['fontdata']     = [
                'dejavusans' => [
                    'R'  => 'DejaVuSans.ttf',
                    'B'  => 'DejaVuSans-Bold.ttf',
                    'I'  => 'DejaVuSans-Oblique.ttf',
                    'BI' => 'DejaVuSans-BoldOblique.ttf',
                ],
            ];
            $config['default_font'] = 'dejavusans';
        }

        $mpdf      = new Mpdf(config: $config);
        $pageCount = $mpdf->setSourceFile(filename: $sourcePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId       = $mpdf->importPage(pageNumber: $i);
            $size        = $mpdf->getTemplateSize(template: $tplId);
            $orientation = 'P';
            if ($size['width'] > $size['height']) {
                $orientation = 'L';
            }

            $mpdf->addPage(
                orientation: $orientation,
                newformat: [$size['width'], $size['height']]
            );
            $mpdf->useTemplate(template: $tplId);
        }

        // Add summary page.
        $mpdf->addPage();
        $mpdf->WriteHTML(html: $summaryHtml);

        return $mpdf->Output(name: '', dest: \Mpdf\Output\Destination::STRING_RETURN);

    }//end buildAppendedPdf()

    /**
     * Collect entity data for all files in the dossier folder.
     *
     * @param string|null $dossierFolder NC file path of the dossier folder
     *
     * @return array<int, array{fileName: string, fileId: int, entityCount: int, bases: array<string, int>, entities: array}> Per-document data
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
     */
    private function collectDossierEntityData(?string $dossierFolder): array
    {
        if ($dossierFolder === null || $dossierFolder === '') {
            return [];
        }

        try {
            $folder = $this->rootFolder->get(path: $dossierFolder);
        } catch (\Throwable $e) {
            $this->logger->warning(
                message: 'Could not access dossier folder: '.$dossierFolder,
                context: ['exception' => $e->getMessage()]
            );
            return [];
        }

        if (($folder instanceof \OCP\Files\Folder) === false) {
            return [];
        }

        $docData = [];
        $this->collectFromFolder(folder: $folder, result: $docData);

        return $docData;

    }//end collectDossierEntityData()

    /**
     * Recursively collect entity data from a folder's files.
     *
     * @param \OCP\Files\Folder                $folder NC folder node
     * @param array<int, array<string, mixed>> $result Accumulator for per-document data
     *
     * @return void
     */
    private function collectFromFolder(\OCP\Files\Folder $folder, array &$result): void
    {
        $nodes = $folder->getDirectoryListing();
        foreach ($nodes as $node) {
            if ($node instanceof \OCP\Files\Folder) {
                // Skip the anonymised output subfolder to avoid double-counting
                // redacted entity data (the output files are scanned separately).
                if (in_array($node->getName(), ['anonymised', 'anonymized', 'redacted'], true) === true) {
                    continue;
                }

                $this->collectFromFolder(folder: $node, result: $result);
                continue;
            }

            if (($node instanceof \OCP\Files\File) === false) {
                continue;
            }

            $fileId = (int) $node->getId();
            try {
                $entities = $this->loadAnonymisedEntitiesForFile(fileId: $fileId);
            } catch (\Throwable $e) {
                $this->logger->debug(
                    message: 'Could not load entities for file '.$fileId,
                    context: ['exception' => $e->getMessage()]
                );
                $entities = [];
            }

            if (empty($entities) === true) {
                continue;
            }

            // Build per-basis count for this file.
            $basesCount = [];
            foreach ($entities as $entity) {
                foreach ($this->resolveBaseLabels(baseUuids: $entity['bases']) as $label) {
                    $basesCount[$label] = ($basesCount[$label] ?? 0) + 1;
                }
            }

            $result[] = [
                'fileName'    => $node->getName(),
                'fileId'      => $fileId,
                'entityCount' => count($entities),
                'bases'       => $basesCount,
                'entities'    => $entities,
            ];
        }//end foreach

    }//end collectFromFolder()

    /**
     * Aggregate a per-grondslag summary from per-document data.
     *
     * @param array<int, array<string, mixed>> $perDocData Per-document entity data
     *
     * @return array<string, array{name: string, fileCount: int, entityCount: int}> Per-grondslag totals
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-6
     */
    private function aggregatePerGrondslag(array $perDocData): array
    {
        $totals = [];
        foreach ($perDocData as $doc) {
            foreach ($doc['bases'] as $baseLabel => $count) {
                if (isset($totals[$baseLabel]) === false) {
                    $totals[$baseLabel] = [
                        'name'        => $baseLabel,
                        'fileCount'   => 0,
                        'entityCount' => 0,
                    ];
                }

                $totals[$baseLabel]['fileCount']++;
                $totals[$baseLabel]['entityCount'] += $count;
            }
        }

        return array_values($totals);

    }//end aggregatePerGrondslag()

    /**
     * Write the dossier summary PDF to the correct destination path.
     *
     * Tries `<dossier-folder>/anonymised/grondslagen.pdf` first; falls back to
     * `<dossier-folder>/grondslagen.pdf` when the anonymised subfolder does not
     * exist (before anonymisation-output-folder-layout lands).
     *
     * @param string|null $dossierFolder NC folder path of the dossier
     * @param string      $content       PDF binary content
     *
     * @return \OCP\Files\File|null Saved file node or null on failure
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-5
     */
    private function writeDossierSummaryFile(?string $dossierFolder, string $content): mixed
    {
        if ($dossierFolder === null || $dossierFolder === '') {
            return null;
        }

        try {
            $folder = $this->rootFolder->get(path: $dossierFolder);
        } catch (\Throwable $e) {
            $this->logger->warning(
                message: 'Dossier folder not found: '.$dossierFolder,
                context: ['exception' => $e->getMessage()]
            );
            return null;
        }

        if (($folder instanceof \OCP\Files\Folder) === false) {
            return null;
        }

        // Try canonical path with anonymised subfolder first.
        $targetFolder = null;
        if ($folder->nodeExists(path: self::ANONYMISED_SUBFOLDER) === true) {
            $sub = $folder->get(path: self::ANONYMISED_SUBFOLDER);
            if ($sub instanceof \OCP\Files\Folder) {
                $targetFolder = $sub;
            }
        }

        // Fallback: write directly to the dossier folder.
        if ($targetFolder === null) {
            $targetFolder = $folder;
        }

        return $this->saveFileToFolder(
            folder: $targetFolder,
            fileName: self::DOSSIER_SUMMARY_FILENAME,
            content: $content
        );

    }//end writeDossierSummaryFile()

    /**
     * Save a file to a Nextcloud folder, overwriting if it exists.
     *
     * @param \OCP\Files\Folder $folder   Target NC folder
     * @param string            $fileName File name within the folder
     * @param string            $content  File content (binary or text)
     *
     * @return \OCP\Files\File|null Saved file node, or null on failure
     */
    private function saveFileToFolder(\OCP\Files\Folder $folder, string $fileName, string $content): mixed
    {
        try {
            if ($folder->nodeExists(path: $fileName) === true) {
                $existingFile = $folder->get(path: $fileName);
                $existingFile->putContent(data: $content);
                return $existingFile;
            }

            return $folder->newFile(path: $fileName, content: $content);
        } catch (\Throwable $e) {
            $this->logger->error(
                message: 'Failed to save file '.$fileName.' to NC folder',
                context: ['exception' => $e]
            );
            return null;
        }//end try

    }//end saveFileToFolder()

    /**
     * Collect distinct resolved base names from a list of enriched entities.
     *
     * @param array<int, array<string, mixed>> $entities Entities with baseLabels
     *
     * @return array<string> Unique base names
     */
    private function collectDistinctBases(array $entities): array
    {
        $seen   = [];
        $result = [];
        foreach ($entities as $entity) {
            foreach ($entity['baseLabels'] as $label) {
                if (isset($seen[$label]) === false) {
                    $seen[$label] = true;
                    $result[]     = $label;
                }
            }
        }

        return $result;

    }//end collectDistinctBases()

    /**
     * Count the distinct union of raw `bases` refs across a list of entities.
     *
     * Each entity may carry a `bases` key holding an array of base refs, an
     * empty array, or null. Null/empty bases contribute nothing. The result is
     * the number of unique refs across every entity.
     *
     * @param array<int, array<string, mixed>> $entities Entities each with an optional `bases` array
     *
     * @return int Count of distinct base refs
     */
    private function countDistinctBases(array $entities): int
    {
        $seen = [];
        foreach ($entities as $entity) {
            $bases = ($entity['bases'] ?? null);
            if (is_array($bases) === false) {
                continue;
            }

            foreach ($bases as $base) {
                $ref        = (string) $base;
                $seen[$ref] = true;
            }
        }

        return count($seen);

    }//end countDistinctBases()

    /**
     * Aggregate per-file entity/grondslagen data into the per-dossier shape.
     *
     * Produces a flat `rows` array keyed by basis ref (each row carries the
     * resolved `label` from $labelMap plus an `entityCount`) and a `totals`
     * block carrying the document count, summed entity count, and the count of
     * distinct bases across all files.
     *
     * @param array<int, array{fileId?: int, filename?: string, entities?: array<int, array<string, mixed>>}> $perFile  Per-file entity data
     * @param array<string, string>                                                                           $labelMap Map of basis ref to human label
     *
     * @return array{rows: array<int, array{ref: string, label: string, entityCount: int}>, totals: array{documentCount: int, entityCount: int, distinctBasesCount: int}} Aggregated dossier summary
     */
    private function aggregateForDossier(array $perFile, array $labelMap): array
    {
        // Flatten every entity across all files for counting.
        $allEntities = [];
        $entityCount = 0;
        foreach ($perFile as $file) {
            $entities = ($file['entities'] ?? []);
            if (is_array($entities) === false) {
                $entities = [];
            }

            foreach ($entities as $entity) {
                $allEntities[] = $entity;
                $entityCount  += (int) ($entity['count'] ?? 0);
            }
        }

        // Build per-basis rows: one row per distinct ref, with its resolved
        // label and the number of entities that reference it.
        $rowsByRef = [];
        foreach ($allEntities as $entity) {
            $bases = ($entity['bases'] ?? null);
            if (is_array($bases) === false) {
                continue;
            }

            foreach ($bases as $base) {
                $ref = (string) $base;
                if (isset($rowsByRef[$ref]) === false) {
                    $rowsByRef[$ref] = [
                        'ref'         => $ref,
                        'label'       => ($labelMap[$ref] ?? $ref),
                        'entityCount' => 0,
                    ];
                }

                $rowsByRef[$ref]['entityCount']++;
            }
        }

        return [
            'rows'   => array_values($rowsByRef),
            'totals' => [
                'documentCount'      => count($perFile),
                'entityCount'        => $entityCount,
                'distinctBasesCount' => $this->countDistinctBases(entities: $allEntities),
            ],
        ];

    }//end aggregateForDossier()

    /**
     * Load Twig template content from the templates directory.
     *
     * @param string $filename Template filename (relative to grondslagen dir)
     *
     * @return string Template content
     *
     * @throws RuntimeException When the template file cannot be read
     */
    private function getTemplateContent(string $filename): string
    {
        $path = dirname(path: __DIR__).'/Resources/templates/grondslagen/'.$filename;

        if (file_exists(filename: $path) === false) {
            throw new RuntimeException('Template not found: '.$path);
        }

        $content = file_get_contents(filename: $path);
        if ($content === false) {
            throw new RuntimeException('Could not read template: '.$path);
        }

        return $content;

    }//end getTemplateContent()

    /**
     * Get the OR EntityRelationMapper from the container.
     *
     * @return \OCA\OpenRegister\Db\EntityRelationMapper The mapper instance
     *
     * @throws RuntimeException If OpenRegister is not available
     */
    private function getEntityRelationMapper(): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
        }

        throw new RuntimeException('OpenRegister is not available.');

    }//end getEntityRelationMapper()

    /**
     * Get the OR ObjectService from the container.
     *
     * @return \OCA\OpenRegister\Service\ObjectService The service instance
     *
     * @throws RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException('OpenRegister is not available.');

    }//end getObjectService()
}//end class
