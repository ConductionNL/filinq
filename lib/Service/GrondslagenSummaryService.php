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
    private const TEMPLATE_DIR = '/lib/Resources/templates/grondslagen/';

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
     * Constructor.
     *
     * @param LoggerInterface    $logger      Structured logger.
     * @param PdfService         $pdfService  Twig + mPDF renderer.
     * @param IRootFolder        $rootFolder  Nextcloud file API entry point.
     * @param IUserSession       $userSession Session-user lookup for the "operator" header field.
     * @param IAppManager        $appManager  App-availability check for OpenRegister.
     * @param ContainerInterface $container   DI container for OpenRegister-side services
     *                                        (EntityRelationMapper, ObjectService).
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PdfService $pdfService,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container
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
     * @param File $anonymisedFile The anonymised PDF file (must already be a PDF).
     * @param int  $sourceFileId   The Nextcloud file ID of the original (pre-anonymisation)
     *                             source — used to read the EntityRelation rows that
     *                             record the redactions performed against it.
     *
     * @return File The same anonymised file, with the summary page appended.
     *
     * @throws RuntimeException When template rendering, PDF merging, or file write fails.
     */
    public function appendSummaryToPdf(File $anonymisedFile, int $sourceFileId): File
    {
        $summaryBytes = $this->renderPerDocumentSummary(
            anonymisedFile: $anonymisedFile,
            sourceFileId: $sourceFileId
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
     * @param File $anonymisedFile The anonymised file (any format).
     * @param int  $sourceFileId   The pre-anonymisation source file ID.
     *
     * @return File The newly-written summary PDF.
     *
     * @throws RuntimeException When rendering or write fails.
     */
    public function renderSummaryBesideFile(File $anonymisedFile, int $sourceFileId): File
    {
        $summaryBytes = $this->renderPerDocumentSummary(
            anonymisedFile: $anonymisedFile,
            sourceFileId: $sourceFileId
        );

        $parent = $anonymisedFile->getParent();
        if (($parent instanceof Folder) === false) {
            throw new RuntimeException(
                'Grondslagen summary: parent folder unavailable for '.$anonymisedFile->getPath()
            );
        }

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

        if (($newFile instanceof File) === false) {
            throw new RuntimeException(
                'Grondslagen summary: newFile() did not return a File instance for '.$summaryFileName
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
        // Phase 5 will implement: resolve dossier → folder → walk files →
        // load entities per file → aggregate → render
        // summary_per_dossier.twig → save → update dossier config.
        throw new RuntimeException('GrondslagenSummaryService::renderDossierSummary — not yet implemented (phase 5).');

    }//end renderDossierSummary()


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
        // Phase 1 stub — implementation lives with phase 2 (templates) +
        // phase 5 (dossier render). Returns the placeholder for every
        // input until then so callers can wire end-to-end.
        $labels = [];
        foreach ($baseRefs as $ref) {
            $refString = (string) $ref;
            $short     = $refString;
            if (strlen($refString) > 8) {
                $short = substr($refString, 0, 8);
            }

            $labels[$refString] = '⟨grondslag verwijderd: '.$short.'⟩';
        }

        return $labels;

    }//end resolveBaseLabels()


    /**
     * Load the EntityRelation rows that this service cares about for a file.
     *
     * Filters to relations where `anonymized = true` (the report is "what
     * was redacted under which grondslag" — non-anonymised relations are
     * out of scope) and attaches the resolved base-label list onto each
     * row for the template to render.
     *
     * @param int $fileId The Nextcloud file ID.
     *
     * @return array<int, array<string, mixed>> Rows shaped as
     *         `{relationId, entityText, entityType, anonymizedValue, bases, baseLabels}`.
     */
    private function loadAnonymisedEntitiesForFile(int $fileId): array
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

        // Build the row shape the per-doc template consumes.
        $shaped = [];
        foreach ($rawRows as $row) {
            $bases  = ($row['bases'] ?? null);
            $labels = [];
            if (is_array($bases) === true) {
                foreach ($bases as $ref) {
                    $key      = (string) $ref;
                    $labels[] = ($labelMap[$key] ?? $key);
                }
            }

            $basesOut = [];
            if (is_array($bases) === true) {
                $basesOut = array_values($bases);
            }

            $shaped[] = [
                'relationId'      => (int) ($row['relation_id'] ?? 0),
                'entityText'      => (string) ($row['entity_value'] ?? ''),
                'entityType'      => (string) ($row['entity_type'] ?? ''),
                'anonymizedValue' => (string) ($row['anonymized_value'] ?? ''),
                'bases'           => $basesOut,
                'baseLabels'      => $labels,
                'confidence'      => (float) ($row['confidence'] ?? 0.0),
            ];
        }//end foreach

        return $shaped;

    }//end loadAnonymisedEntitiesForFile()


    /**
     * Render the per-document summary template into PDF bytes.
     *
     * Shared between {@see appendSummaryToPdf} and
     * {@see renderSummaryBesideFile} — both produce the same summary
     * content; only the destination differs.
     *
     * @param File $anonymisedFile The anonymised file (for header context).
     * @param int  $sourceFileId   The pre-anonymisation source file id.
     *
     * @return string The rendered PDF (PDF/A-3b) as raw bytes.
     *
     * @throws RuntimeException When template or PDF rendering fails.
     */
    private function renderPerDocumentSummary(File $anonymisedFile, int $sourceFileId): string
    {
        $entities      = $this->loadAnonymisedEntitiesForFile(fileId: $sourceFileId);
        $distinctBases = $this->countDistinctBases(entities: $entities);

        $operator = 'system';
        $user     = $this->userSession->getUser();
        if ($user !== null) {
            $operator = $user->getDisplayName();
        }

        $data = [
            'document'           => [
                'filename'     => $anonymisedFile->getName(),
                'anonymisedAt' => date('c'),
                'operator'     => $operator,
                'tool'         => 'OpenAnonymiser via OpenRegister',
            ],
            'entities'           => $entities,
            'distinctBasesCount' => $distinctBases,
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

            // FPDI extends FPDF; Output() is inherited from FPDF and Psalm
            // lacks stubs for it. When dest is 'S' it returns the PDF bytes.
            /** @psalm-suppress UndefinedMethod */
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
