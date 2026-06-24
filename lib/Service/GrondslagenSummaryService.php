<?php
/**
 * Grondslagen Summary Service
 *
 * Renders per-document and per-dossier grondslagen summary PDFs. Reads
 * EntityRelation.bases (OpenRegister, Wave 1.3 — entity-relation-grondslagen)
 * and resolves the bases against the `base` schema (DocuDesk, Wave 1.1 —
 * add-dossier-schema) to produce an auditable record of "what was redacted
 * under which Woo Art. 5 grondslag" for the file or dossier.
 *
 * Two rendering surfaces:
 *
 *   - **Per-document append.** When the anonymise endpoint is called with
 *     `appendBasisSummary: true`, this service renders the summary as a single
 *     extra page and appends it to the anonymised PDF using mPDF + FPDI. When
 *     the output isn't PDF (operator opted for `outputFormat: "preserve"`),
 *     the summary is saved as a separate `_grondslagen.pdf` file in the same
 *     folder as the anonymised file.
 *
 *   - **Per-dossier on-demand.** A dedicated endpoint regenerates the
 *     per-dossier summary PDF aggregating every file under the dossier's
 *     folder. The same render also fires automatically when the dossier's
 *     `checkedOn` review timestamp is updated.
 *
 * No new persistence beyond the existing dossier object's `configuration`
 * JSON field, which records the generated file's UUID and timestamp so the
 * dossier UI can badge the report as fresh / stale.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Renderer for the per-document and per-dossier grondslagen summary PDFs.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class GrondslagenSummaryService
{

    /**
     * Relative path (from the app root) where the Twig templates live.
     */
    private const TEMPLATE_DIR = '/Resources/templates/grondslagen/';

    /**
     * Template file for the per-document summary page.
     */
    private const TEMPLATE_PER_DOC = 'summary_per_doc.twig';

    /**
     * Template file for the per-dossier summary PDF.
     */
    private const TEMPLATE_PER_DOSSIER = 'summary_per_dossier.twig';

    /**
     * Suffix applied to the source file's base name when the per-document
     * append falls back to a separate-PDF file (operator chose to preserve
     * the native output format and the anonymised file isn't a PDF).
     */
    private const SUMMARY_FILE_SUFFIX = '_grondslagen.pdf';

    /**
     * Entity-type labels localised in the placeholder, mirroring
     * OpenRegister's `DocumentProcessingHandler::LOCALIZABLE_ENTITY_TYPES`
     * (the `EntityRecognitionHandler::ENTITY_TYPE_*` values). Only these are
     * translated so the summary legend reads the same as the labels
     * OpenRegister wrote into the redacted document; an unknown type falls
     * back to its raw string. DocuDesk's `l10n/` carries the same Dutch
     * translations so the two apps resolve identically for a given language.
     *
     * @var array<int, string>
     */
    private const LOCALIZABLE_ENTITY_TYPES = [
        'PERSON',
        'ORGANIZATION',
        'LOCATION',
        'EMAIL',
        'PHONE',
        'ADDRESS',
        'DATE',
        'IBAN',
        'SSN',
        'IP_ADDRESS',
    ];


    /**
     * Constructor.
     *
     * @param LoggerInterface    $logger      Structured logger.
     * @param PdfService         $pdfService  Twig + mPDF renderer.
     * @param IRootFolder        $rootFolder  Nextcloud file API entry point.
     * @param IUserSession       $userSession Session-user lookup for the "operator" header field.
     * @param IAppManager        $appManager  App-availability check for OpenRegister.
     * @param ContainerInterface $container   DI container for OpenRegister-side services
     *                                        (EntityRelationMapper, ObjectService).
     * @param IL10N|null         $l10n        Acting-user localisation, used to translate the
     *                                        placeholder TYPE label (PERSON → PERSOON on a Dutch
     *                                        instance) so the summary legend matches the localized
     *                                        labels OpenRegister wrote into the redacted document.
     *                                        Nullable: when absent the raw English label is used.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PdfService $pdfService,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
        private readonly ?IL10N $l10n=null
    ) {

    }//end __construct()


    /**
     * Append a grondslagen summary page to an already-anonymised PDF file.
     *
     * Loads the anonymised file's entity relations from OR, resolves their
     * `bases` to human-readable `base.name` labels, renders the per-document
     * template, and appends the resulting PDF page to the anonymised file.
     *
     * The append is atomic — on any rendering or PDF-merge failure the
     * anonymised file is left untouched and the caller receives a warning
     * via the thrown exception. The caller MUST handle the failure
     * gracefully (the spec calls for returning HTTP 200 with a `warning`
     * field rather than failing the entire anonymise call).
     *
     * @param File                  $anonymisedFile The anonymised PDF file (must already be a PDF).
     * @param int                   $sourceFileId   The Nextcloud file ID of the original (pre-anonymisation)
     *                                              source — used to read the EntityRelation rows that
     *                                              record the redactions performed against it.
     * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
     *                                              map (e.g. "7" => "[PERSOON: 1]"); when set the summary renders the
     *                                              SAME placeholder the document carries instead of re-deriving it.
     *
     * @return File The same anonymised file, with the summary page appended.
     *
     * @throws RuntimeException When template rendering, PDF merging, or file write fails.
     */
    public function appendSummaryToPdf(File $anonymisedFile, int $sourceFileId, array $placeholderMap=[]): File
    {
        $summaryBytes = $this->renderPerDocumentSummary(
            anonymisedFile: $anonymisedFile,
            sourceFileId: $sourceFileId,
            placeholderMap: $placeholderMap
        );

        $combinedBytes = $this->mergeSummaryIntoPdf(
            originalPdfBytes: (string) $anonymisedFile->getContent(),
            summaryPdfBytes: $summaryBytes
        );

        try {
            $anonymisedFile->putContent($combinedBytes);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary: failed to write combined PDF to '.$anonymisedFile->getPath().': '.$e->getMessage(),
                previous: $e
            );
        }

        $this->logger->info(
            'GrondslagenSummaryService: appended summary to anonymised PDF',
            [
                'fileId'       => $anonymisedFile->getId(),
                'sourceFileId' => $sourceFileId,
            ]
        );

        return $anonymisedFile;

    }//end appendSummaryToPdf()


    /**
     * Produce a separate grondslagen-summary PDF beside the anonymised file.
     *
     * Used when the operator chose `outputFormat: "preserve"` and the
     * anonymised file is not a PDF — the summary cannot be appended in
     * place, so we write it as `<anonymised-base>_grondslagen.pdf` in the
     * same parent folder.
     *
     * @param File                  $anonymisedFile The anonymised file (any format).
     * @param int                   $sourceFileId   The pre-anonymisation source file ID.
     * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
     *                                              map so the summary renders the SAME placeholder the document carries.
     *
     * @return File The newly-written summary PDF.
     *
     * @throws RuntimeException When rendering or write fails.
     */
    public function renderSummaryBesideFile(File $anonymisedFile, int $sourceFileId, array $placeholderMap=[]): File
    {
        $summaryBytes = $this->renderPerDocumentSummary(
            anonymisedFile: $anonymisedFile,
            sourceFileId: $sourceFileId,
            placeholderMap: $placeholderMap
        );

        $parent          = $anonymisedFile->getParent();
        $baseName        = pathinfo($anonymisedFile->getName(), PATHINFO_FILENAME);
        $summaryFileName = $baseName.self::SUMMARY_FILE_SUFFIX;

        try {
            if ($parent->nodeExists($summaryFileName) === true) {
                $existing = $parent->get($summaryFileName);
                if ($existing instanceof File) {
                    $existing->putContent($summaryBytes);
                    $this->logger->info(
                        'GrondslagenSummaryService: refreshed beside-file summary',
                        ['fileId' => $existing->getId(), 'path' => $existing->getPath()]
                    );
                    return $existing;
                }
            }

            $newFile = $parent->newFile(path: $summaryFileName, content: $summaryBytes);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary write failed: '.$summaryFileName.' — '.$e->getMessage(),
                previous: $e
            );
        }

        $this->logger->info(
            'GrondslagenSummaryService: wrote beside-file summary',
            ['fileId' => $newFile->getId(), 'path' => $newFile->getPath()]
        );

        return $newFile;

    }//end renderSummaryBesideFile()


    /**
     * Render the per-dossier summary PDF for one dossier.
     *
     * Aggregates anonymisation data across every file under the dossier's
     * folder. The resulting PDF is written to a deterministic location
     * (per Wave 2's `anonymisation-output-folder-layout` when shipped:
     * `<dossier-folder>/anonymised/grondslagen.pdf`; until then:
     * `<dossier-folder>/grondslagen.pdf`).
     *
     * On success the method also updates the dossier object's
     * `configuration.grondslagen.{fileId, lastGeneratedAt}` so the dossier
     * UI can badge the summary's freshness.
     *
     * @param string $dossierUuid The OR UUID of the dossier object.
     *
     * @return File The generated summary PDF.
     *
     * @throws RuntimeException When the dossier can't be loaded, the folder
     *                          isn't accessible, or rendering fails.
     */
    public function renderDossierSummary(string $dossierUuid): File
    {
        $dossier = $this->loadDossierContext(dossierUuid: $dossierUuid);

        $folder = $this->resolveDossierFolder(folderRef: ($dossier['folderRef'] ?? null));

        // Recompute the dossier's scope-local placeholder map up-front so every
        // file's rows render the SAME scope-local number the documents carry
        // (e.g. [DATUM: 6]) instead of the global entity_id fallback (the
        // 1600+ ids). Reuses OpenRegister's deterministic dossier ranking.
        $placeholderMap = $this->computeDossierPlaceholderMap(folder: $folder);

        $perFile = $this->walkDossierFiles(folder: $folder, placeholderMap: $placeholderMap);

        // The loadAnonymisedEntitiesForFile call already resolves base labels per
        // file. aggregateForDossier just unfolds those rows across files
        // and sorts. No second label-resolution pass needed here.
        $aggregated = $this->aggregateForDossier(perFile: $perFile, labelMap: []);

        // Distinct grondslagen assigned across every file in the dossier, with
        // their Woo Art. 5 toelichting — rendered as a legend under the table.
        $allEntities = [];
        foreach ($perFile as $fileRow) {
            foreach (($fileRow['entities'] ?? []) as $entity) {
                $allEntities[] = $entity;
            }
        }

        $data = [
            'dossier'     => [
                'name'        => (string) ($dossier['name'] ?? ''),
                'description' => (string) ($dossier['description'] ?? ''),
                'checkedOn'   => (string) ($dossier['checkedOn'] ?? ''),
            ],
            'generatedAt' => date('c'),
            'rows'        => $aggregated['rows'],
            'totals'      => $aggregated['totals'],
            'bases'       => $this->collectAssignedBases(entities: $allEntities),
        ];

        $template = $this->loadTemplate(name: self::TEMPLATE_PER_DOSSIER);

        try {
            $pdfBytes = $this->pdfService->renderPdf(
                templateContent: $template,
                data: $data,
                options: ['pdfa' => true, 'title' => 'Grondslagen-rapportage']
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary: per-dossier render failed for '.$dossierUuid.': '.$e->getMessage(),
                previous: $e
            );
        }

        $summaryFile = $this->saveDossierSummary(folder: $folder, pdfBytes: $pdfBytes);

        $this->updateDossierConfiguration(
            dossierUuid: $dossierUuid,
            summaryFileId: $summaryFile->getId()
        );

        $this->logger->info(
            'GrondslagenSummaryService: rendered per-dossier summary',
            [
                'dossierUuid'   => $dossierUuid,
                'summaryFileId' => $summaryFile->getId(),
                'fileCount'     => count($perFile),
                'totalEntities' => $aggregated['totals']['entityCount'],
            ]
        );

        return $summaryFile;

    }//end renderDossierSummary()


    /**
     * Load the minimum dossier context the renderer needs.
     *
     * @param string $dossierUuid The OR object UUID.
     *
     * @return array<string, mixed> `{name, description, checkedOn, folderRef, configuration}`.
     *
     * @throws RuntimeException When the dossier cannot be resolved.
     */
    private function loadDossierContext(string $dossierUuid): array
    {
        $objectService = $this->getObjectService();
        if ($objectService === null) {
            throw new RuntimeException('Grondslagen summary: OpenRegister ObjectService unavailable.');
        }

        try {
            $object = $objectService->find(
                id: $dossierUuid,
                register: 'dossier',
                schema: 'dossier',
                _rbac: false,
                _multitenancy: false
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary: failed to load dossier '.$dossierUuid.': '.$e->getMessage(),
                previous: $e
            );
        }

        if ($object === null) {
            throw new RuntimeException('Grondslagen summary: dossier not found: '.$dossierUuid);
        }

        $payload = (array) $object;
        if (is_object($object) === true && method_exists($object, 'getObject') === true) {
            $payload = $object->getObject();
        }

        // The `@self.folder` reference is stored on the ObjectEntity's
        // `folder` column, NOT inside the schema-typed payload returned by
        // `getObject()`. OR's renderer reconstructs the `@self` block from
        // the entity's columns when serialising for the API, but in-process
        // callers must read the columns directly. Read the entity-level
        // getter first; fall back to a payload-embedded `@self.folder` for
        // future-compat in case the renderer ever inlines it.
        //
        // NOTE: `getFolder` is a magic method on Nextcloud's `Entity` base
        // class (auto-generated via `__call`, declared only as `@method`),
        // so `method_exists` returns false even when the call works. Probe
        // via `ObjectEntity` instanceof, then invoke directly.
        // OpenRegister's ObjectEntity is the expected runtime type, but
        // Psalm doesn't see OR's lib (it's an optional dep). Probe by
        // class_exists + instanceof + a runtime-safe `getFolder()` call.
        $folderRef         = null;
        $objectEntityClass = '\OCA\OpenRegister\Db\ObjectEntity';
        if (is_object($object) === true
            && class_exists($objectEntityClass) === true
            && $object instanceof $objectEntityClass
        ) {
            try {
                $folderRef = $object->getFolder();
            } catch (\Throwable $e) {
                $folderRef = null;
            }
        }

        if ($folderRef === null || $folderRef === '') {
            $self      = ($payload['@self'] ?? []);
            $folderRef = ($self['folder'] ?? null);
        }

        return [
            'name'          => (string) ($payload['name'] ?? ''),
            'description'   => (string) ($payload['description'] ?? ''),
            'checkedOn'     => (string) ($payload['checkedOn'] ?? ''),
            'folderRef'     => $folderRef,
            'configuration' => ($payload['configuration'] ?? []),
        ];

    }//end loadDossierContext()


    /**
     * Resolve the dossier's `@self.folder` reference to a Nextcloud Folder node.
     *
     * @param mixed $folderRef The raw reference value — typically a file-node id (int/string).
     *
     * @return Folder The dossier's folder.
     *
     * @throws RuntimeException When the reference cannot be resolved.
     */
    private function resolveDossierFolder(mixed $folderRef): Folder
    {
        if ($folderRef === null || $folderRef === '') {
            throw new RuntimeException('Grondslagen summary: dossier has no @self.folder reference.');
        }

        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                throw new RuntimeException('Grondslagen summary: no session user to resolve folder.');
            }

            $userFolder = $this->rootFolder->getUserFolder($user->getUID());
            $nodes      = $userFolder->getById((int) $folderRef);
            $node       = ($nodes[0] ?? null);
            if ($node === null) {
                throw new RuntimeException(
                    'Grondslagen summary: folder node id '.((string) $folderRef).' not found for user '.$user->getUID()
                );
            }
        } catch (NotFoundException $e) {
            throw new RuntimeException(
                'Grondslagen summary: dossier folder not found ('.((string) $folderRef).'): '.$e->getMessage(),
                previous: $e
            );
        }

        if (($node instanceof Folder) === false) {
            throw new RuntimeException(
                'Grondslagen summary: dossier @self.folder ('.((string) $folderRef).') is not a folder node.'
            );
        }

        return $node;

    }//end resolveDossierFolder()


    /**
     * Walk every file under the dossier folder and collect its anonymised entities.
     *
     * Folders found inside the dossier folder are recursed; the summary
     * subfolder produced by Wave 2's `anonymisation-output-folder-layout`
     * (`anonymised/`) is skipped — it contains the redacted *outputs*,
     * whereas the EntityRelation rows are keyed by the source file ids.
     *
     * @param Folder                $folder         The dossier folder.
     * @param array<string, string> $placeholderMap Dossier scope-local placeholder map
     *                                              (global entity id → "[DATUM: 6]")
     *                                              so each file's rows render the
     *                                              dossier number, not the global id.
     *
     * @return array<int, array{fileId: int, filename: string, entities: array<int, array<string, mixed>>}>
     */
    private function walkDossierFiles(Folder $folder, array $placeholderMap=[]): array
    {
        $rows = [];
        foreach ($folder->getDirectoryListing() as $node) {
            if ($node instanceof Folder) {
                if (in_array($node->getName(), ['anonymised', 'anonymized', 'redacted'], true) === true) {
                    continue;
                }

                $rows = array_merge($rows, $this->walkDossierFiles(folder: $node, placeholderMap: $placeholderMap));
                continue;
            }

            if (($node instanceof File) === false) {
                continue;
            }

            $entities = $this->loadAnonymisedEntitiesForFile(fileId: $node->getId(), placeholderMap: $placeholderMap);
            if (count($entities) === 0) {
                continue;
            }

            $rows[] = [
                'fileId'   => $node->getId(),
                'filename' => $node->getName(),
                'entities' => $entities,
            ];
        }//end foreach

        return $rows;

    }//end walkDossierFiles()


    /**
     * Recompute the dossier's scope-local placeholder map on demand.
     *
     * The per-dossier report is regenerated without a live anonymise run, so
     * there is no placeholder map from OpenRegister to reuse — without one,
     * each entity falls back to its GLOBAL entity_id (the 1600+ numbers).
     * This reproduces OpenRegister's deterministic dossier numbering for the
     * folder's files (rank distinct entity_ids by first appearance under the
     * total order file_id, position_start, entity_id) so the report shows the
     * SAME scope-local number the documents carry, and combines it with the
     * localized TYPE label → `e.id → "[DATUM: 6]"`.
     *
     * Reuses OpenRegister's own `PlaceholderIdTranslator::rankByFirstAppearance`
     * (so the ranking can never drift from the anonymise path) plus the
     * EntityRelationMapper. Returns an empty map when OpenRegister is absent or
     * too old; callers then fall back to the global-id behaviour.
     *
     * @param Folder $folder The dossier folder.
     *
     * @return array<string, string> Map of global entity id → "[<localizedTYPE>: <dossier number>]".
     */
    private function computeDossierPlaceholderMap(Folder $folder): array
    {
        $mapper = $this->getEntityRelationMapper();
        if ($mapper === null
            || method_exists($mapper, 'findEntityIdsByValueForFiles') === false
            || method_exists($mapper, 'findEntityIdsByValueForFile') === false
            || class_exists(\OCA\OpenRegister\Service\File\PlaceholderIdTranslator::class) === false
        ) {
            return [];
        }

        $fileIds = $this->collectFileIds(folder: $folder);
        if ($fileIds === []) {
            return [];
        }

        try {
            $rows = $mapper->findEntityIdsByValueForFiles(fileIds: $fileIds);
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagenSummaryService: dossier placeholder recompute failed; falling back to global ids',
                ['error' => $e->getMessage()]
            );
            return [];
        }

        // Deterministic dossier ranking — identical to the anonymise path.
        $ranks = \OCA\OpenRegister\Service\File\PlaceholderIdTranslator::rankByFirstAppearance(rows: $rows);
        if ($ranks === []) {
            return [];
        }

        // Resolve each entity id's TYPE (for the localized label) from the
        // per-file value→{id,type} maps.
        $types = [];
        foreach ($fileIds as $fileId) {
            try {
                foreach ($mapper->findEntityIdsByValueForFile($fileId) as $entry) {
                    $types[(string) ($entry['id'] ?? '')] = (string) ($entry['type'] ?? '');
                }
            } catch (Exception $e) {
                continue;
            }
        }

        $map = [];
        foreach ($ranks as $entityId => $rank) {
            $type = ($types[(string) $entityId] ?? '');
            $map[(string) $entityId] = '['.$this->localizeEntityType(entityType: $type).': '.$rank.']';
        }

        return $map;

    }//end computeDossierPlaceholderMap()


    /**
     * Collect the descendant file ids of a dossier folder (recursive), skipping
     * the redacted-output subfolders — mirrors {@see walkDossierFiles} so the
     * recompute ranks over the same source-file set the rows come from.
     *
     * @param Folder $folder The dossier folder.
     *
     * @return array<int, int> Distinct descendant source file ids.
     */
    private function collectFileIds(Folder $folder): array
    {
        $ids = [];
        try {
            foreach ($folder->getDirectoryListing() as $node) {
                if ($node instanceof Folder) {
                    if (in_array($node->getName(), ['anonymised', 'anonymized', 'redacted'], true) === true) {
                        continue;
                    }

                    foreach ($this->collectFileIds(folder: $node) as $nestedId) {
                        $ids[] = $nestedId;
                    }

                    continue;
                }

                if (($node instanceof File) === true) {
                    $ids[] = (int) $node->getId();
                }
            }
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagenSummaryService: dossier file enumeration failed',
                ['error' => $e->getMessage()]
            );
        }//end try

        return array_values(array_unique($ids));

    }//end collectFileIds()


    /**
     * Build the row set the per-dossier template renders.
     *
     * Produces ONE row per distinct entity (`entityType:entityId`). Because the
     * dossier number is consistent across the dossier's files, the same
     * person/date appears once — its occurrence `count` is summed, the files it
     * appears in are collected into a comma-joined `filename` list, and its
     * grondslagen are unioned. Rows are sorted by TYPE then NUMERIC id ascending
     * so the type blocks read 1,2,…,10,11.
     *
     * Per-file entities arrive pre-aggregated from
     * {@see loadAnonymisedEntitiesForFile}: each entry already has
     * `placeholder`, `count`, and `basesText` (Dutch labels joined).
     *
     * @param array<int, mixed>     $perFile  Per-file rows from {@see walkDossierFiles}
     *                                        — each entry shaped as `{fileId, filename,
     *                                        entities[]}` where `entities[]` is the
     *                                        per-entity-aggregated output of
     *                                        loadAnonymisedEntitiesForFile.
     * @param array<string, string> $labelMap Map of base-ref → human-readable label
     *                                        (unused here — labels are already
     *                                        resolved upstream; kept for signature
     *                                        compat).
     *
     * @return array<string, mixed> Shape:
     *                              `{ rows: array<int, {placeholder, filename,
     *                                 fileCount, count, baseLabels, basesText,
     *                                 entityType, entityId}>,
     *                                totals: { documentCount, entityCount,
     *                                  distinctEntityCount, distinctBasesCount } }`.
     */
    private function aggregateForDossier(array $perFile, array $labelMap): array
    {
        unset($labelMap);

        $grouped           = [];
        $totalOccurrences  = 0;
        $distinctBasisRefs = [];

        foreach ($perFile as $fileRow) {
            $filename = (string) ($fileRow['filename'] ?? '');

            foreach (($fileRow['entities'] ?? []) as $entity) {
                $count      = (int) ($entity['count'] ?? 0);
                $baseLabels = ($entity['baseLabels'] ?? []);
                if (is_array($baseLabels) === false) {
                    $baseLabels = [];
                }

                $totalOccurrences += $count;

                foreach (($entity['bases'] ?? []) as $ref) {
                    $distinctBasisRefs[(string) $ref] = true;
                }

                // Dedup to ONE row per distinct entity (entityType:entityId).
                // The dossier number is consistent across files, so the same
                // person/date appears once — aggregating its occurrence count,
                // the files it appears in, and the union of its grondslagen —
                // instead of repeating the same placeholder once per file.
                $entityKey = (string) ($entity['entityType'] ?? '').':'.(string) ($entity['entityId'] ?? '');
                if (isset($grouped[$entityKey]) === false) {
                    $grouped[$entityKey] = [
                        'placeholder' => (string) ($entity['placeholder'] ?? ''),
                        'entityType'  => (string) ($entity['entityType'] ?? ''),
                        'entityId'    => (int) ($entity['entityId'] ?? 0),
                        'count'       => 0,
                        'filenames'   => [],
                        'baseLabels'  => [],
                    ];
                }

                $grouped[$entityKey]['count'] += $count;
                if ($filename !== '') {
                    $grouped[$entityKey]['filenames'][$filename] = true;
                }

                foreach ($baseLabels as $label) {
                    $grouped[$entityKey]['baseLabels'][(string) $label] = true;
                }
            }//end foreach
        }//end foreach

        $rows = [];
        foreach ($grouped as $group) {
            $files = array_keys($group['filenames']);
            sort($files);
            $labels = array_keys($group['baseLabels']);

            $rows[] = [
                'placeholder' => $group['placeholder'],
                'count'       => $group['count'],
                // Joined distinct filenames — the entity may span several files
                // in the dossier; the twig renders this list in the "Bestanden"
                // column.
                'filename'    => implode(', ', $files),
                'fileCount'   => count($files),
                'baseLabels'  => $labels,
                'basesText'   => implode(', ', $labels),
                'entityType'  => $group['entityType'],
                'entityId'    => $group['entityId'],
            ];
        }

        usort(
            $rows,
            static function (array $a, array $b): int {
                // By TYPE then NUMERIC id ascending (1,2,…,10,11), then files.
                $cmp = (self::placeholderSortKey(placeholder: $a['placeholder']) <=> self::placeholderSortKey(placeholder: $b['placeholder']));
                if ($cmp !== 0) {
                    return $cmp;
                }

                return strcmp($a['filename'], $b['filename']);
            }
        );

        return [
            'rows'   => $rows,
            'totals' => [
                'documentCount'       => count($perFile),
                'entityCount'         => $totalOccurrences,
                'distinctEntityCount' => count($grouped),
                'distinctBasesCount'  => count($distinctBasisRefs),
            ],
        ];

    }//end aggregateForDossier()


    /**
     * Save the rendered per-dossier summary PDF.
     *
     * Destination convention: `<dossier-folder>/grondslagen.pdf`. Wave 2
     * (`anonymisation-output-folder-layout`) will introduce a
     * `<dossier-folder>/anonymised/` subfolder; this method will follow
     * that convention once the helper from Wave 2 lands. For v1, we use
     * the flat path inside the dossier folder.
     *
     * @param Folder $folder   The dossier folder.
     * @param string $pdfBytes The freshly-rendered PDF bytes.
     *
     * @return File The newly-written / refreshed summary file.
     *
     * @throws RuntimeException On write failure.
     */
    private function saveDossierSummary(Folder $folder, string $pdfBytes): File
    {
        $name = 'grondslagen.pdf';

        try {
            if ($folder->nodeExists($name) === true) {
                $existing = $folder->get($name);
                if ($existing instanceof File) {
                    $existing->putContent($pdfBytes);
                    return $existing;
                }
            }

            $newFile = $folder->newFile(path: $name, content: $pdfBytes);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary: failed to write '.$name.' to dossier folder: '.$e->getMessage(),
                previous: $e
            );
        }

        return $newFile;

    }//end saveDossierSummary()


    /**
     * Update the dossier object's `configuration.grondslagen.{fileId, lastGeneratedAt}`.
     *
     * Failure is logged but does not roll back the rendered file — the PDF
     * is on disk and the operator can find it; the metadata refresh is
     * convenience for the dossier UI's freshness-badge.
     *
     * @param string $dossierUuid   The OR dossier object UUID.
     * @param int    $summaryFileId The newly-written summary file's NC node id.
     *
     * @return void
     */
    private function updateDossierConfiguration(string $dossierUuid, int $summaryFileId): void
    {
        $objectService = $this->getObjectService();
        if ($objectService === null) {
            $this->logger->warning(
                'GrondslagenSummaryService: cannot update dossier configuration — ObjectService unavailable',
                ['dossierUuid' => $dossierUuid]
            );
            return;
        }

        try {
            $object = $objectService->find(
                id: $dossierUuid,
                register: 'dossier',
                schema: 'dossier',
                _rbac: false,
                _multitenancy: false
            );
            if ($object === null) {
                return;
            }

            $payload = (array) $object;
            if (is_object($object) === true && method_exists($object, 'getObject') === true) {
                $payload = $object->getObject();
            }

            $configuration = [];
            if (is_array(($payload['configuration'] ?? null)) === true) {
                $configuration = $payload['configuration'];
            }

            $grondslagen = [];
            if (is_array(($configuration['grondslagen'] ?? null)) === true) {
                $grondslagen = $configuration['grondslagen'];
            }

            $grondslagen['fileId']          = $summaryFileId;
            $grondslagen['lastGeneratedAt'] = date('c');
            $configuration['grondslagen']   = $grondslagen;
            $payload['configuration']       = $configuration;

            // Preserve the dossier's `@self.folder` across this save.
            // `getObject()` returns the schema-typed payload only — the
            // `folder` column lives on the ObjectEntity itself. Without
            // explicit re-injection, OR's save path sees no folder ref
            // on the incoming payload, hands the object to
            // `ensureObjectFolderExists`, and that helper auto-creates
            // a brand-new folder under the register's storage tree —
            // overwriting `_folder` with the auto-folder's id. Operators
            // see their original dossier folder mysteriously replaced
            // by a generated one in OR's `Open Registers` folder.
            //
            // Read the existing folder ref off the entity (the magic
            // `getFolder()` method on NC's `Entity` base class) and
            // inject it back into the payload's `@self.folder`. OR's
            // `setSelfMetadata` reads `@self.folder` and re-applies it
            // via `setFolder()` on save (per the
            // `validate-self-folder-access` change), so the original
            // folder binding is preserved.
            $objectEntityClass = '\OCA\OpenRegister\Db\ObjectEntity';
            if (is_object($object) === true
                && class_exists($objectEntityClass) === true
                && $object instanceof $objectEntityClass
            ) {
                try {
                    $existingFolder = $object->getFolder();
                    if (is_string($existingFolder) === true && $existingFolder !== '') {
                        $self = ($payload['@self'] ?? []);
                        if (is_array($self) === false) {
                            $self = [];
                        }

                        $self['folder']   = $existingFolder;
                        $payload['@self'] = $self;
                    }
                } catch (\Throwable $e) {
                    // Folder probe failure must not abort the
                    // configuration update — log and proceed with the
                    // save (the user-visible PDF is already on disk).
                    $this->logger->warning(
                        'GrondslagenSummaryService: could not read existing folder ref before save',
                        ['dossierUuid' => $dossierUuid, 'error' => $e->getMessage()]
                    );
                }
            }//end if

            $objectService->saveObject(
                object: $payload,
                register: 'dossier',
                schema: 'dossier',
                uuid: $dossierUuid,
                _rbac: false,
                _multitenancy: false
            );
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagenSummaryService: failed to update dossier configuration.grondslagen',
                ['dossierUuid' => $dossierUuid, 'error' => $e->getMessage()]
            );
        }//end try

    }//end updateDossierConfiguration()


    /**
     * Resolve a list of `base` references (slugs or UUIDs) to human-readable labels.
     *
     * Looks each reference up in the dossier register's `base` schema and
     * returns a map `{ref => name}`. Unresolved references (rule deleted,
     * malformed reference, etc.) get a placeholder of the form
     * `⟨grondslag verwijderd: <short-ref>⟩` so the rendered report flags
     * the data gap rather than silently dropping the row.
     *
     * Wave 1.1's `add-dossier-schema` ships `bases` as plain slug strings
     * (per the v1 trade-off documented in its design.md §D1), so this
     * method primarily resolves by slug; UUID fallback is supported for
     * forward-compatibility with a future `$ref` enforcement story.
     *
     * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
     *
     * @return array<string, string> Map from each reference to its display name.
     */
    private function resolveBaseLabels(array $baseRefs): array
    {
        $labels = [];
        if (count($baseRefs) === 0) {
            return $labels;
        }

        $objectService = $this->getObjectService();
        if ($objectService === null) {
            // ObjectService unavailable — best-effort: show the raw ref so
            // the operator at least sees the slug, not a dangling
            // placeholder. Better than masking the failure entirely.
            foreach ($baseRefs as $ref) {
                $labels[(string) $ref] = ['name' => (string) $ref, 'description' => ''];
            }

            return $labels;
        }

        // Pull every `base` object in one shot — the canonical set is six
        // Woo Art. 5 grondslagen plus any tenant-added entries; very small
        // cardinality. Build slug→name AND uuid→name lookups so the
        // resolver works regardless of which reference shape the `bases`
        // column carries (Wave 1.1's v1 trade-off stores slugs, but a
        // future shape might switch to UUIDs).
        //
        // searchObjectsBySlug is the path that resolves slug filters to
        // numeric IDs and reaches the magic-mapped `dossier` register;
        // findAll with slug filters returns nothing because the magic
        // tables aren't visible to the generic getHandler path.
        $slugToName = [];
        $uuidToName = [];
        $slugToDesc = [];
        $uuidToDesc = [];
        try {
            $result = $objectService->searchObjectsBySlug(
                registerSlug: 'dossier',
                schemaSlug: 'base',
                filters: [],
                _rbac: false,
                _multitenancy: false
            );

            $bases = $this->extractObjects(result: $result);
            foreach ($bases as $base) {
                $self = ($base['@self'] ?? []);
                $name = (string) ($base['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                // `description` is the Woo Art. 5 toelichting (schema `base`,
                // property "Omschrijving") — the explanatory text shown under
                // the summary table.
                $desc = (string) ($base['description'] ?? '');

                $slug = '';
                $uuid = '';
                if (is_array($self) === true) {
                    $slug = (string) ($self['slug'] ?? '');
                    $uuid = (string) ($self['id'] ?? ($self['uuid'] ?? ''));
                }

                if ($slug !== '') {
                    $slugToName[$slug] = $name;
                    $slugToDesc[$slug] = $desc;
                }

                if ($uuid !== '') {
                    $uuidToName[$uuid] = $name;
                    $uuidToDesc[$uuid] = $desc;
                }
            }//end foreach
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagenSummaryService: failed to load `base` objects for label resolution',
                ['error' => $e->getMessage()]
            );
        }//end try

        foreach ($baseRefs as $ref) {
            $refString = (string) $ref;
            if (isset($slugToName[$refString]) === true) {
                $labels[$refString] = ['name' => $slugToName[$refString], 'description' => ($slugToDesc[$refString] ?? '')];
            } else if (isset($uuidToName[$refString]) === true) {
                $labels[$refString] = ['name' => $uuidToName[$refString], 'description' => ($uuidToDesc[$refString] ?? '')];
            } else {
                $labels[$refString] = ['name' => $refString, 'description' => ''];
            }
        }

        return $labels;

    }//end resolveBaseLabels()


    /**
     * Collect the distinct grondslagen assigned across the given entities,
     * each with its name + description (the Woo Art. 5 toelichting), for the
     * explanatory legend rendered under the summary table.
     *
     * @param array<int, array{bases?: array<int, string>}> $entities Shaped entities (each carrying a `bases` ref list).
     *
     * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
     */
    private function collectAssignedBases(array $entities): array
    {
        $refs = [];
        foreach ($entities as $entity) {
            foreach (($entity['bases'] ?? []) as $ref) {
                $refs[(string) $ref] = true;
            }
        }

        if (count($refs) === 0) {
            return [];
        }

        $detail = $this->resolveBaseLabels(baseRefs: array_keys($refs));

        // Dedup by name (distinct refs may resolve to the same grondslag) and
        // drop nameless entries; keep the first non-empty description.
        $byName = [];
        foreach ($detail as $entry) {
            $name = (string) ($entry['name'] ?? '');
            if ($name === '') {
                continue;
            }

            if (isset($byName[$name]) === false || $byName[$name] === '') {
                $byName[$name] = (string) ($entry['description'] ?? '');
            }
        }

        $bases = [];
        foreach ($byName as $name => $description) {
            $bases[] = ['name' => $name, 'description' => $description];
        }

        usort($bases, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $bases;

    }//end collectAssignedBases()


    /**
     * Coerce an ObjectService findAll result into a plain array of object payloads.
     *
     * `findAll` may return ObjectEntity instances, plain associative
     * arrays, or a `{results: [...]}` envelope depending on the path that
     * served it. Normalise to a flat array of `array<string, mixed>` so
     * callers can iterate uniformly.
     *
     * @param mixed $result The raw findAll return value.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractObjects(mixed $result): array
    {
        if (is_array($result) === true && isset($result['results']) === true && is_array($result['results']) === true) {
            $result = $result['results'];
        }

        $out = [];
        if (is_iterable($result) === false) {
            return $out;
        }

        $objectEntityClass = '\OCA\OpenRegister\Db\ObjectEntity';
        foreach ($result as $item) {
            // ObjectEntity::jsonSerialize() returns a flat payload that
            // includes a synthetic `@self` block (id, slug, register,
            // schema, …) reconstructed from the entity's columns. That's
            // the shape resolveBaseLabels needs, so prefer it when the
            // item is a real ObjectEntity.
            if (is_object($item) === true
                && class_exists($objectEntityClass) === true
                && $item instanceof $objectEntityClass
            ) {
                try {
                    $payload = $item->jsonSerialize();
                    if (is_array($payload) === true) {
                        $out[] = $payload;
                        continue;
                    }
                } catch (\Throwable $e) {
                    // Fall through to other branches.
                }
            }

            if (is_array($item) === true) {
                $out[] = $item;
            }
        }//end foreach

        return $out;

    }//end extractObjects()


    /**
     * Load the EntityRelation rows that this service cares about for a file.
     *
     * Filters to relations where `anonymized = true` (the report is "what
     * was redacted under which grondslag" — non-anonymised relations are
     * out of scope) and attaches the resolved base-label list onto each
     * row for the template to render.
     *
     * @param int                   $fileId         The Nextcloud file ID.
     * @param array<string, string> $placeholderMap Optional global entity id → emitted
     *                                              placeholder map; when set, each row uses that
     *                                              placeholder (scope-local number + localized
     *                                              label) instead of re-deriving `[<TYPE>:
     *                                              <entity_id>]` from the global id.
     *
     * @return array<int, array<string, mixed>> Rows shaped as
     *         `{relationId, entityText, entityType, anonymizedValue, bases, baseLabels}`.
     */
    private function loadAnonymisedEntitiesForFile(int $fileId, array $placeholderMap=[]): array
    {
        $mapper = $this->getEntityRelationMapper();
        if ($mapper === null) {
            $this->logger->warning(
                'GrondslagenSummaryService: EntityRelationMapper unavailable; producing empty entity set',
                ['fileId' => $fileId]
            );
            return [];
        }

        try {
            $rawRows = $mapper->findAnonymisedEntitiesWithBasesForFile($fileId);
        } catch (Exception $e) {
            $this->logger->error(
                'GrondslagenSummaryService: findAnonymisedEntitiesWithBasesForFile failed',
                ['fileId' => $fileId, 'error' => $e->getMessage()]
            );
            return [];
        }

        if (is_array($rawRows) === false || count($rawRows) === 0) {
            return [];
        }

        // Collect distinct base references across all rows so we resolve
        // labels in one batch.
        $allRefs = [];
        foreach ($rawRows as $row) {
            $bases = ($row['bases'] ?? null);
            if (is_array($bases) === false) {
                continue;
            }

            foreach ($bases as $ref) {
                $allRefs[(string) $ref] = true;
            }
        }

        $labelMap = $this->resolveBaseLabels(baseRefs: array_keys($allRefs));

        // Group raw relation rows by (entity_type, entity_id) so the
        // template sees one row per entity rather than one row per
        // occurrence. Each group carries:
        // - placeholder: `[<localizedTYPE>: <entity_id>]` — the TYPE label is
        // localized to the acting user's language (PERSON → PERSOON) to match
        // the labels OR's `DocumentProcessingHandler` wrote into the redacted
        // document (anonymisation-placeholder-id-scope). NOTE: the <id> here is
        // still the global entity_id, whereas OR now emits a scope-local
        // number; making the NUMBER match too needs OR to expose its
        // per-entity placeholder map (tracked as a follow-up). The localized
        // TYPE is the part this summary owns.
        // - count: number of EntityRelation rows in the group (i.e.
        // how many times this entity got redacted in this file).
        // - bases: set-union of `bases` arrays across the group.
        // - baseLabels: bases resolved to human-readable Dutch names,
        // comma-joined for direct render.
        $grouped = [];
        foreach ($rawRows as $row) {
            $entityId   = (int) ($row['entity_id'] ?? 0);
            $entityType = (string) ($row['entity_type'] ?? '');
            $entityText = (string) ($row['entity_value'] ?? '');
            $key        = $entityType.':'.$entityId;

            if (isset($grouped[$key]) === false) {
                // Prefer the EXACT placeholder OpenRegister emitted for this
                // global entity id (carries the scope-local number + localized
                // label, so the summary legend matches the redacted document).
                // Fall back to re-deriving `[<localizedTYPE>: <entity_id>]` only
                // when no map was supplied (e.g. the on-demand per-dossier
                // report, or an older OpenRegister without getLastPlaceholderMap).
                $placeholder = ($placeholderMap[(string) $entityId] ?? '['.$this->localizeEntityType(entityType: $entityType).': '.$entityId.']');

                $grouped[$key] = [
                    'entityId'    => $entityId,
                    'entityType'  => $entityType,
                    'entityText'  => $entityText,
                    'placeholder' => $placeholder,
                    'count'       => 0,
                    'basesSet'    => [],
                ];
            }

            $grouped[$key]['count']++;

            $bases = ($row['bases'] ?? null);
            if (is_array($bases) === true) {
                foreach ($bases as $ref) {
                    $grouped[$key]['basesSet'][(string) $ref] = true;
                }
            }
        }//end foreach

        $shaped = [];
        foreach ($grouped as $group) {
            $basesRefs = array_keys($group['basesSet']);
            $labels    = [];
            foreach ($basesRefs as $ref) {
                $labels[] = ($labelMap[$ref]['name'] ?? $ref);
            }

            $shaped[] = [
                'placeholder' => $group['placeholder'],
                'entityId'    => $group['entityId'],
                'entityType'  => $group['entityType'],
                'entityText'  => $group['entityText'],
                'count'       => $group['count'],
                'bases'       => $basesRefs,
                'baseLabels'  => $labels,
                'basesText'   => implode(', ', $labels),
            ];
        }

        // Stable order — by TYPE then NUMERIC id ascending — so the blocks of
        // each type are grouped and ordered 1,2,3,…,10,11 (not the lexical
        // 1,10,11,2 a plain string sort produces). Diff-friendly across re-runs.
        usort(
            $shaped,
            static function (array $a, array $b): int {
                return (self::placeholderSortKey(placeholder: $a['placeholder']) <=> self::placeholderSortKey(placeholder: $b['placeholder']));
            }
        );

        return $shaped;

    }//end loadAnonymisedEntitiesForFile()


    /**
     * Sort key for a `[<TYPE>: <number>]` placeholder: [type, number] so a
     * spaceship compare orders by type alphabetically then by number
     * NUMERICALLY ascending (1,2,…,10,11 — not the lexical 1,10,11,2). A
     * placeholder that doesn't match the shape sorts last, by its raw string.
     *
     * @param string $placeholder The placeholder string.
     *
     * @return array{0: string, 1: int} The [type, number] sort key.
     */
    private static function placeholderSortKey(string $placeholder): array
    {
        if (preg_match('/^\[(.+):\s*(\d+)\]\s*$/u', $placeholder, $m) === 1) {
            return [$m[1], (int) $m[2]];
        }

        return [$placeholder, PHP_INT_MAX];

    }//end placeholderSortKey()


    /**
     * Localise an entity-type label for the summary placeholder so it reads
     * the same as the label OpenRegister wrote into the redacted document
     * (anonymisation-placeholder-id-scope). Only the enumerated
     * `LOCALIZABLE_ENTITY_TYPES` set is translated; an unknown / free-form type
     * is returned unchanged. When no `IL10N` is injected the raw label is
     * returned (the `en` / untranslated behaviour).
     *
     * @param string $entityType The raw entity type (e.g. 'PERSON').
     *
     * @return string The localised label (e.g. 'PERSOON' on nl), or the raw type.
     */
    private function localizeEntityType(string $entityType): string
    {
        if ($this->l10n === null
            || in_array($entityType, self::LOCALIZABLE_ENTITY_TYPES, true) === false
        ) {
            return $entityType;
        }

        return $this->l10n->t($entityType);

    }//end localizeEntityType()


    /**
     * Render the per-document summary template into PDF bytes.
     *
     * Shared between {@see appendSummaryToPdf} and
     * {@see renderSummaryBesideFile} — both produce the same summary
     * content; only the destination differs.
     *
     * @param File                  $anonymisedFile The anonymised file (for header context).
     * @param int                   $sourceFileId   The pre-anonymisation source file id.
     * @param array<string, string> $placeholderMap Optional global entity id → emitted placeholder
     *                                              map, threaded to loadAnonymisedEntitiesForFile.
     *
     * @return string The rendered PDF (PDF/A-3b) as raw bytes.
     *
     * @throws RuntimeException When template or PDF rendering fails.
     */
    private function renderPerDocumentSummary(File $anonymisedFile, int $sourceFileId, array $placeholderMap=[]): string
    {
        $entities      = $this->loadAnonymisedEntitiesForFile(fileId: $sourceFileId, placeholderMap: $placeholderMap);
        $distinctBases = $this->countDistinctBases(entities: $entities);

        $totalOccurrences = 0;
        foreach ($entities as $entity) {
            $totalOccurrences += (int) ($entity['count'] ?? 0);
        }

        $operator = 'system';
        $user     = $this->userSession->getUser();
        if ($user !== null) {
            $operator = $user->getDisplayName();
        }

        $data = [
            'document' => [
                'filename'     => $anonymisedFile->getName(),
                'anonymisedAt' => date('c'),
                'operator'     => $operator,
                'tool'         => 'OpenAnonymiser via OpenRegister',
            ],
            'entities' => $entities,
            'totals'   => [
                'entityCount'         => $totalOccurrences,
                'distinctEntityCount' => count($entities),
                'distinctBasesCount'  => $distinctBases,
            ],
            // Distinct grondslagen assigned in this document, each with its
            // Woo Art. 5 toelichting — rendered as a legend under the table.
            'bases'    => $this->collectAssignedBases(entities: $entities),
        ];

        try {
            return $this->pdfService->renderPdf(
                templateContent: $this->loadTemplate(name: self::TEMPLATE_PER_DOC),
                data: $data,
                options: ['pdfa' => true, 'title' => 'Anonimisatie-samenvatting']
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary: per-doc render failed for fileId '.$sourceFileId.': '.$e->getMessage(),
                previous: $e
            );
        }

    }//end renderPerDocumentSummary()


    /**
     * Merge an anonymised PDF + the freshly-rendered summary PDF into one PDF.
     *
     * Uses FPDI to import every page of both inputs and emit them as a
     * single combined PDF. The result is **not strictly PDF/A** (FPDI
     * doesn't enforce that on import — the upstream PDF's compliance
     * isn't guaranteed); the per-dossier render path uses pure mPDF and
     * IS PDF/A-3b. This trade-off is documented in design.md.
     *
     * @param string $originalPdfBytes Anonymised PDF bytes.
     * @param string $summaryPdfBytes  Summary PDF bytes (from `renderPerDocumentSummary`).
     *
     * @return string Combined PDF bytes.
     *
     * @throws RuntimeException When FPDI import or output fails.
     *
     * @psalm-suppress UndefinedMethod FPDI extends FPDF; Output() is inherited from FPDF
     *                                 and Psalm lacks stubs for it.
     */
    private function mergeSummaryIntoPdf(string $originalPdfBytes, string $summaryPdfBytes): string
    {
        $originalTemp = tempnam(sys_get_temp_dir(), 'grondslagen-orig-');
        $summaryTemp  = tempnam(sys_get_temp_dir(), 'grondslagen-summary-');

        if ($originalTemp === false || $summaryTemp === false) {
            throw new RuntimeException('Grondslagen summary: could not allocate temp files for FPDI merge');
        }

        try {
            file_put_contents($originalTemp, $originalPdfBytes);
            file_put_contents($summaryTemp, $summaryPdfBytes);

            $pdf = new Fpdi();
            $pdf->setSourceFile($originalTemp);
            $pageCount = $pdf->setSourceFile($originalTemp);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $pdf->importPage($i);
                $size  = $pdf->getTemplateSize($tplId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }

            $summaryPages = $pdf->setSourceFile($summaryTemp);
            for ($i = 1; $i <= $summaryPages; $i++) {
                $tplId = $pdf->importPage($i);
                $size  = $pdf->getTemplateSize($tplId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }

            // FPDI inherits Output() from FPDF. Calling 'S' returns the PDF bytes.
            // @phpstan-ignore-next-line method.notFound (FPDF stubs are not loaded for static analysis).
            return (string) $pdf->Output('S');
        } catch (Exception $e) {
            throw new RuntimeException(
                'Grondslagen summary: FPDI merge failed: '.$e->getMessage(),
                previous: $e
            );
        } finally {
            if (file_exists($originalTemp) === true) {
                unlink($originalTemp);
            }

            if (file_exists($summaryTemp) === true) {
                unlink($summaryTemp);
            }
        }//end try

    }//end mergeSummaryIntoPdf()


    /**
     * Count distinct base references across a set of shaped entity rows.
     *
     * Used by the per-doc template's footer total.
     *
     * @param array<int, array<string, mixed>> $entities Output of {@see loadAnonymisedEntitiesForFile}.
     *
     * @return int Distinct base count.
     */
    private function countDistinctBases(array $entities): int
    {
        $seen = [];
        foreach ($entities as $entity) {
            $bases = ($entity['bases'] ?? []);
            if (is_array($bases) === false) {
                continue;
            }

            foreach ($bases as $ref) {
                $seen[(string) $ref] = true;
            }
        }

        return count($seen);

    }//end countDistinctBases()


    /**
     * Load a Twig template's source from disk.
     *
     * The templates live under `lib/Resources/templates/grondslagen/`. This
     * helper reads the file as a string so it can be passed to
     * `PdfService::renderPdf($templateContent, ...)`. Throws if the file
     * is missing — every release MUST ship both templates.
     *
     * @param string $name The template file name (e.g. `summary_per_doc.twig`).
     *
     * @return string The template's UTF-8 source.
     *
     * @throws RuntimeException When the template file is missing or unreadable.
     */
    private function loadTemplate(string $name): string
    {
        $path     = __DIR__.'/..'.self::TEMPLATE_DIR.$name;
        $resolved = realpath($path);
        if ($resolved === false || is_readable($resolved) === false) {
            throw new RuntimeException(
                sprintf('Grondslagen summary template not found or unreadable: %s', $path)
            );
        }

        $contents = file_get_contents($resolved);
        if ($contents === false) {
            throw new RuntimeException(
                sprintf('Grondslagen summary template read failed: %s', $resolved)
            );
        }

        return $contents;

    }//end loadTemplate()


    /**
     * Get the OpenRegister EntityRelationMapper, or null when unavailable.
     *
     * @return \OCA\OpenRegister\Db\EntityRelationMapper|null
     */
    private function getEntityRelationMapper(): ?object
    {
        if ($this->isOpenRegisterAvailable() === false) {
            return null;
        }

        try {
            return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagenSummaryService: EntityRelationMapper unavailable',
                ['error' => $e->getMessage()]
            );
            return null;
        }

    }//end getEntityRelationMapper()


    /**
     * Get the OpenRegister ObjectService, or null when unavailable.
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null
     */
    private function getObjectService(): ?object
    {
        if ($this->isOpenRegisterAvailable() === false) {
            return null;
        }

        try {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagenSummaryService: ObjectService unavailable',
                ['error' => $e->getMessage()]
            );
            return null;
        }

    }//end getObjectService()


    /**
     * True when the OpenRegister app is installed and enabled.
     *
     * @return bool
     */
    private function isOpenRegisterAvailable(): bool
    {
        return in_array('openregister', $this->appManager->getInstalledApps(), true);

    }//end isOpenRegisterAvailable()


}//end class
