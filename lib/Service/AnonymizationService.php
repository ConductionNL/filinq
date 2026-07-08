<?php
/**
 * Anonymization Service
 *
 * Service for orchestrating the document anonymization pipeline:
 * text extraction with entity detection, and anonymization.
 * Uses OpenRegister services for text extraction and entity recognition.
 * Delegates entity detection logic to EntityDetectionService.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use RuntimeException;
use Throwable;
use OCP\App\IAppManager;
use OCP\Files\File;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for orchestrating the document anonymization pipeline
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationService
{


    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface           $logger             Logger for error reporting
     * @param ContainerInterface        $container          Container for dependency injection
     * @param IAppManager               $appManager         App manager interface
     * @param EntityDetectionService    $entityDetection    Entity detection and mapping service
     * @param GrondslagenSummaryService $grondslagenSummary Renderer for the per-document grondslagen
     *                                                      summary page (Wave 4a — opt-in via
     *                                                      `appendBasisSummary: true` on the request).
     * @param PdfConversionService      $pdfConversion      Cascade orchestrator that converts the
     *                                                      anonymised intermediate to PDF when
     *                                                      `outputFormat: "pdf"` is in effect.
     * @param EmlPdfAssemblyService     $emlAssembly        Assembles OR's redacted anonymise-EML
     *                                                      result into a PDF/A-3b. EML inputs are
     *                                                      routed here directly because OR's
     *                                                      `anonymizeDocument()` throws on
     *                                                      `message/rfc822`.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly EntityDetectionService $entityDetection,
        private readonly GrondslagenSummaryService $grondslagenSummary,
        private readonly PdfConversionService $pdfConversion,
        private readonly EmlPdfAssemblyService $emlAssembly
    ) {

    }//end __construct()


    /**
     * Get an OpenRegister service or mapper by class name
     *
     * @param string $className The fully qualified class name
     *
     * @return mixed The service instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getOpenRegisterService(string $className): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get($className);
        }

        throw new RuntimeException($className.' is not available.');

    }//end getOpenRegisterService()


    /**
     * Extract text from a file and detect entities
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount
     *
     * @throws Exception If extraction or detection fails
     */
    public function extractAndDetectEntities(int $fileId): array
    {
        try {
            $textExtractor = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\TextExtractionService'
            );

            // Resolve DocuDesk's grondslag service up front (via the container,
            // string class name, to keep this class's coupling in check). It
            // also owns the operator's enabled-entity-type selection, used to
            // scope automatic detection just below.
            $grondslagProposal = $this->container->get('OCA\DocuDesk\Service\GrondslagProposalService');

            // Scope automatic detection to the enabled entity types (null = all
            // types). Manual entities are added through a separate path, so a
            // manually-added type is still anonymised even when its automatic
            // detection is disabled here.
            $entityTypes = $grondslagProposal->getEntityTypeWhitelist();
            $textExtractor->extractFile($fileId, true, $entityTypes);

            $this->logger->debug(
                'Text extracted from file',
                ['fileId' => $fileId, 'entityTypes' => $entityTypes]
            );

            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
            $entities = $entityRelationMapper->findEntitiesForFile($fileId);

            // Pre-fill a proposed grondslag per entity type onto the
            // freshly-detected relations (fill-only-when-empty), then enrich
            // the returned rows with their current bases so the review UI can
            // show the proposal. Both calls are internally best-effort and
            // never block detection.
            $grondslagProposal->applyProposals(fileId: $fileId);
            $entities = $grondslagProposal->enrichEntitiesWithBases(entities: $entities, fileId: $fileId);

            return [
                'entities'    => $this->entityDetection->normalizeEntities($entities),
                'entityCount' => count($entities),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract and detect entities: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
            throw new Exception(
                'Failed to extract and detect entities: '.$e->getMessage(),
                0,
                $e
            );
        }//end try

    }//end extractAndDetectEntities()


    /**
     * Anonymize entities in a document
     *
     * @param int                         $fileId             The Nextcloud file ID
     * @param array<array<string, mixed>> $entities           The entities to anonymize
     * @param bool                        $appendBasisSummary When true, after the anonymise pipeline
     *                                                        completes the per-document grondslagen
     *                                                        summary is rendered and either appended
     *                                                        to the resulting PDF (when the output
     *                                                        is a PDF) or saved as a separate
     *                                                        `<base>_grondslagen.pdf` file beside
     *                                                        it. Summary-step failure does NOT fail
     *                                                        the anonymise — a structured
     *                                                        `warning` is attached to the result
     *                                                        instead.
     * @param string                      $outputFormat       Output format gate. `"pdf-only"`
     *                                                        (default) and `"pdf"` both run the
     *                                                        post-anonymise PDF conversion cascade
     *                                                        and roll back the intermediate if
     *                                                        conversion fails (re-throws
     *                                                        ConversionFailedException for the
     *                                                        controller to surface as HTTP 422).
     *                                                        `"pdf-only"` additionally best-effort
     *                                                        deletes the native anonymised
     *                                                        intermediate after a successful
     *                                                        conversion so only the PDF remains;
     *                                                        `"pdf"` keeps the native intermediate.
     *                                                        `"preserve"` skips conversion and
     *                                                        returns the anonymised file in its
     *                                                        native format.
     * @param string                      $scope              Placeholder-numbering scope forwarded to
     *                                                        OpenRegister: `"document"` (default) or
     *                                                        `"dossier"` (consistent numbering across
     *                                                        the dossier folder's files).
     * @param string|null                 $dossierKey         Stable folder id for the dossier when
     *                                                        $scope='dossier'; null lets OpenRegister
     *                                                        fall back to the file's parent folder.
     *
     * @return array<string, mixed> Anonymization result. Adds the optional `warning` field when
     *                              the grondslagen-summary step failed but the anonymise itself
     *                              succeeded; adds `summaryFileId` when a separate summary PDF
     *                              was written (preserve-mode fallback).
     *
     * @throws Exception                  If anonymization fails.
     * @throws ConversionFailedException  When `$outputFormat === "pdf"` and the cascade could not
     *                                    convert the anonymised intermediate. The intermediate
     *                                    is deleted (best-effort) before the exception propagates.
     */
    public function anonymizeDocument(
        int $fileId,
        array $entities,
        bool $appendBasisSummary=false,
        string $outputFormat='pdf-only',
        string $scope='document',
        ?string $dossierKey=null
    ): array {
        try {
            $fileService    = $this->getOpenRegisterService(className: 'OCA\OpenRegister\Service\FileService');
            $node           = $fileService->getFileById($fileId);
            $mappedEntities = $this->entityDetection->mapEntitiesForAnonymization($entities);

            // EML branch (eml-pdf-assembly): OR's anonymizeDocument() THROWS on
            // message/rfc822 (it no longer leaks a raw-text body). EML inputs
            // are therefore routed to OR's dedicated anonymise-EML API
            // (anonymizeEmlStructured) and assembled into a PDF/A-3b here,
            // BEFORE the standard anonymizeDocument + convertToPdf path. This
            // REPLACES that path for EML. `outputFormat: "preserve"` is
            // silently overridden to PDF for EML (design D8) — handled inside
            // anonymizeEmlToPdf because EML has no reliably-redacted native
            // form to preserve.
            if ($this->isEmlInput(node: $node) === true) {
                return $this->anonymizeEmlToPdf(
                    fileId: $fileId,
                    node: $node,
                    fileService: $fileService,
                    mappedEntities: $mappedEntities,
                    appendBasisSummary: $appendBasisSummary,
                    scope: $scope,
                    dossierKey: $dossierKey
                );
            }

            // Placeholder-numbering scope (anonymisation-placeholder-id-scope):
            // 'document' (default) numbers entities locally to this file;
            // 'dossier' makes the number consistent across the dossier folder's
            // files. OpenRegister derives the dossier from $dossierKey (a stable
            // folder id) or falls back to the file's parent folder when null —
            // so a folder anonymise only needs to signal scope=dossier. Passed
            // positionally for compatibility with the reflectively-resolved
            // OpenRegister FileService.
            $result = $fileService->anonymizeDocument($node, $mappedEntities, $scope, $dossierKey);

            // Best-effort policy: OpenRegister now produces the anonymised file
            // even when some entity text could not be removed (e.g. the ExApp
            // NER over-captured a span across table cells, so the value is not
            // contiguous in the document). Pull the residual list so the
            // operator can be warned and iterate (manual entities, skip
            // unselected occurrences). Defensive method_exists() guard for
            // older OpenRegister versions without the best-effort API.
            $residualEntities = [];
            if (method_exists($fileService, 'getLastResidualEntities') === true) {
                $residualEntities = $fileService->getLastResidualEntities();
            }

            // Per-entity placeholder map (anonymisation-placeholder-id-scope):
            // the EXACT placeholder OpenRegister emitted per global entity id
            // (e.g. `"7" => "[PERSOON: 1]"`), so the grondslagen-summary renders
            // the same scope-local number + localized label the document carries
            // instead of re-deriving `[<TYPE>: <entity_id>]`. Defensive
            // method_exists() for older OpenRegister versions (summary then
            // falls back to the global id).
            $placeholderMap = [];
            if (method_exists($fileService, 'getLastPlaceholderMap') === true) {
                $placeholderMap = $fileService->getLastPlaceholderMap();
            }

            $this->logger->info(
                'Document anonymized',
                [
                    'fileId'        => $fileId,
                    'entityCount'   => count($mappedEntities),
                    // PII-free: count only, never the residual text.
                    'residualCount' => count($residualEntities),
                ]
            );

            // PDF conversion gate: when outputFormat requests a PDF
            // ('pdf-only' or 'pdf') AND the anonymised result is not
            // already a PDF, run the cascade.
            // On failure: delete the un-converted intermediate (the
            // operator must NOT see a half-finished native-format
            // output when they asked for PDF) and re-throw the typed
            // exception so the controller maps it to 422.
            // On success in 'pdf-only' mode: best-effort delete the native
            // anonymised intermediate so only the PDF remains.
            if (in_array($outputFormat, ['pdf-only', 'pdf'], true) === true && $result instanceof File === true) {
                $resultMime = (string) $result->getMimeType();
                if ($resultMime !== 'application/pdf') {
                    // Capture the native anonymised node BEFORE $result is
                    // reassigned to the converted PDF — 'pdf-only' deletes it
                    // after a successful conversion. When the result is
                    // already a PDF the cascade is skipped, so there is no
                    // native intermediate to delete and 'pdf-only' behaves
                    // identically to 'pdf'.
                    $nativeIntermediate = $result;
                    try {
                        $result = $this->pdfConversion->convertToPdf($result);
                    } catch (ConversionFailedException $e) {
                        $this->logger->warning(
                            'PDF conversion failed; rolling back anonymised intermediate.',
                            [
                                'fileId'   => $fileId,
                                'attempts' => $e->getAttempts(),
                            ]
                        );
                        // Best-effort rollback. If delete fails, log
                        // and continue — re-throwing is more important
                        // than leaving the operator in a partial state
                        // that they CAN inspect (they sent a PDF
                        // outputFormat and got 422, so the expectation
                        // is "no file written"). $result still points at
                        // the un-converted native intermediate here, as
                        // the reassignment above only runs on success.
                        try {
                            $result->delete();
                        } catch (Throwable $deleteError) {
                            $this->logger->warning(
                                'Rollback delete failed; orphaned anonymised file remains.',
                                [
                                    'fileId'    => $fileId,
                                    'exception' => get_class($deleteError),
                                    'message'   => $deleteError->getMessage(),
                                ]
                            );
                        }

                        throw $e;
                    }//end try

                    // 'pdf-only': the conversion succeeded and the PDF is the
                    // referenced output, so the native intermediate is now
                    // un-redactable leftover. Best-effort delete it; a failure
                    // here MUST NOT fail an otherwise-successful run (mirrors
                    // the rollback above). PII-free log (file id + exception
                    // metadata only).
                    if ($outputFormat === 'pdf-only') {
                        try {
                            $nativeIntermediate->delete();
                        } catch (Throwable $deleteError) {
                            $this->logger->warning(
                                'pdf-only: failed to delete native anonymised intermediate; orphaned file remains.',
                                [
                                    'fileId'    => $fileId,
                                    'exception' => get_class($deleteError),
                                    'message'   => $deleteError->getMessage(),
                                ]
                            );
                        }
                    }//end if
                }//end if
            }//end if

            $resultInfo = $this->entityDetection->parseAnonymizationResult($result);
            $resultInfo['replacementCount'] = count($mappedEntities);

            // Surface best-effort residuals so the UI can warn that the file was
            // produced but some entities could not be fully removed, and let the
            // operator refine them. `complete` drives the warning banner.
            $resultInfo['complete']         = (count($residualEntities) === 0);
            $resultInfo['residualCount']    = count($residualEntities);
            $resultInfo['residualEntities'] = $residualEntities;

            if ($appendBasisSummary === true) {
                $resultInfo = $this->attachGrondslagenSummary(
                    anonymisedNode: $result,
                    sourceFileId: $fileId,
                    resultInfo: $resultInfo,
                    placeholderMap: $placeholderMap
                );
            }

            // Persist / update the source↔anonymised file mapping so the
            // relationship is queryable via OR's search API in both
            // directions and re-anonymisation overwrites a single record.
            // Success path only: guard on a known anonymised file id.
            if (empty($resultInfo['anonymizedFileId']) === false) {
                $resultInfo = $this->recordAnonymizationLink(
                    fileId: $fileId,
                    sourceNode: $node,
                    resultInfo: $resultInfo
                );
            }

            return $resultInfo;
        } catch (ConversionFailedException $e) {
            // Surface unchanged so the controller can build the 422 body.
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
            throw new Exception('Failed to anonymize document: '.$e->getMessage(), 0, $e);
        }//end try

    }//end anonymizeDocument()


    /**
     * Whether a file node is an EML (email) input.
     *
     * Detected by MIME `message/rfc822` or a `.eml` extension. EML inputs are
     * routed to the dedicated anonymise-EML + assembly path.
     *
     * @param mixed $node The source file node.
     *
     * @return bool True when the node is an EML message.
     */
    private function isEmlInput(mixed $node): bool
    {
        if (is_object($node) === false) {
            return false;
        }

        if (method_exists($node, 'getMimeType') === true
            && (string) $node->getMimeType() === 'message/rfc822'
        ) {
            return true;
        }

        if (method_exists($node, 'getName') === true) {
            $name = (string) $node->getName();
            $dot  = strrpos($name, '.');
            if ($dot !== false && strtolower(substr($name, ($dot + 1))) === 'eml') {
                return true;
            }
        }

        return false;

    }//end isEmlInput()


    /**
     * Anonymise an EML input via OR's anonymise-EML API and assemble the
     * redacted result into a PDF/A-3b written beside the source.
     *
     * This is the EML replacement for the standard anonymizeDocument +
     * convertToPdf path. EML always produces a PDF — `outputFormat:
     * "preserve"` is silently overridden here (the caller is never told;
     * design D8), because OR redacts components, not a re-serialised native
     * `.eml`, so there is no native intermediate to keep. On OR API failure a
     * `ConversionFailedException` is raised with NO raw-parse fallback (design
     * D9), so the controller maps it to HTTP 422 and no un-redacted content is
     * ever written.
     *
     * @param int          $fileId             Source Nextcloud file ID.
     * @param mixed        $node               Source EML file node.
     * @param mixed        $fileService        OR FileService (resolved reflectively).
     * @param array<int, array<string,mixed>> $mappedEntities Entities to redact.
     * @param bool         $appendBasisSummary Append the grondslagen summary to the PDF.
     * @param string       $scope              Placeholder-numbering scope.
     * @param string|null  $dossierKey         Stable dossier folder id, or null.
     *
     * @return array<string, mixed> The anonymisation result info (same shape the
     *                              controller expects: anonymizedFileId/Name/Path,
     *                              replacementCount, complete, residualEntities).
     *
     * @throws ConversionFailedException On OR API failure or assembly failure.
     */
    private function anonymizeEmlToPdf(
        int $fileId,
        mixed $node,
        mixed $fileService,
        array $mappedEntities,
        bool $appendBasisSummary,
        string $scope,
        ?string $dossierKey
    ): array {
        if (method_exists($fileService, 'anonymizeEmlStructured') === false) {
            throw new ConversionFailedException(
                message: 'OpenRegister does not expose the anonymise-EML API; cannot anonymise EML input.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => false,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured not present on OpenRegister FileService',
                    ],
                ]
            );
        }

        try {
            $structure = $fileService->anonymizeEmlStructured($node, $mappedEntities, $scope, $dossierKey);
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            // NO raw-parse fallback — leaking un-redacted EML is the worse
            // failure. Surface as a typed conversion failure (HTTP 422).
            $this->logger->warning(
                'EML anonymise-API failed; no raw-parse fallback.',
                ['fileId' => $fileId, 'exception' => get_class($e), 'message' => $e->getMessage()]
            );
            throw new ConversionFailedException(
                message: 'OpenRegister anonymise-EML API failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured threw: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        if (is_object($structure) === false) {
            throw new ConversionFailedException(
                message: 'OpenRegister anonymise-EML API returned no structure.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured returned non-object',
                    ],
                ]
            );
        }

        $sourceName = '';
        if (method_exists($node, 'getName') === true) {
            $sourceName = (string) $node->getName();
        }

        // assemble() throws ConversionFailedException on unrecoverable
        // failure; let it propagate so the controller surfaces 422.
        $pdfBytes = $this->emlAssembly->assemble(result: $structure, sourceFilename: $sourceName);

        $parent     = $node->getParent();
        $baseName   = $this->stripExtension(name: $sourceName === '' ? 'email' : $sourceName);
        $outputName = $baseName.'_anonymized.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        $pdfNode = $parent->newFile($outputName, $pdfBytes);

        $this->logger->info(
            'EML anonymised and assembled to PDF',
            [
                'fileId'      => $fileId,
                'entityCount' => count($mappedEntities),
            ]
        );

        $resultInfo = $this->entityDetection->parseAnonymizationResult($pdfNode);
        $resultInfo['replacementCount'] = count($mappedEntities);
        // OR's anonymise-EML path does not surface a residual list; the
        // assembled PDF is the authoritative redacted output.
        $resultInfo['complete']         = true;
        $resultInfo['residualCount']    = 0;
        $resultInfo['residualEntities'] = [];

        if ($appendBasisSummary === true) {
            $placeholderMap = [];
            if (method_exists($fileService, 'getLastPlaceholderMap') === true) {
                $placeholderMap = $fileService->getLastPlaceholderMap();
            }

            $resultInfo = $this->attachGrondslagenSummary(
                anonymisedNode: $pdfNode,
                sourceFileId: $fileId,
                resultInfo: $resultInfo,
                placeholderMap: $placeholderMap
            );
        }

        if (empty($resultInfo['anonymizedFileId']) === false) {
            $resultInfo = $this->recordAnonymizationLink(
                fileId: $fileId,
                sourceNode: $node,
                resultInfo: $resultInfo
            );
        }

        return $resultInfo;

    }//end anonymizeEmlToPdf()


    /**
     * Return $name without its trailing `.ext`.
     *
     * @param string $name File name with extension.
     *
     * @return string Name without extension.
     */
    private function stripExtension(string $name): string
    {
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            return $name;
        }

        return substr($name, 0, $dotPos);

    }//end stripExtension()


    /**
     * Persist or update the mapping between a source file and its anonymised counterpart.
     *
     * Idempotent UPSERT keyed on `sourceFileId`: the first successful
     * anonymisation of a file creates an `anonymizationLink` object in the
     * `document` register; every subsequent re-anonymisation of the same
     * source file updates that same record (preserving its `@self`, which
     * triggers OpenRegister's update path) and increments `runCount`. Both
     * `sourceFileId` and `anonymizedFileId` are facetable on the schema so
     * OR's search API resolves the link in both directions.
     *
     * Best-effort: the anonymised file already exists and the run has
     * succeeded, so a persistence failure here MUST NOT abort or alter the
     * response. Failures are caught, logged at warning level, and the
     * unmodified `$resultInfo` is returned (without an `anonymizationLinkId`
     * key). This mirrors attachGrondslagenSummary().
     *
     * @param int                  $fileId     The source (unanonymised) Nextcloud file ID.
     * @param mixed                $sourceNode The source file node (used for name/path/owner metadata).
     * @param array<string, mixed> $resultInfo Current result; carries anonymizedFileId/Name/Path + replacementCount.
     *
     * @return array<string, mixed> The `$resultInfo`, enriched with `anonymizationLinkId` on success.
     */
    private function recordAnonymizationLink(int $fileId, mixed $sourceNode, array $resultInfo): array
    {
        try {
            $objectService = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\ObjectService'
            );

            $results = $objectService->searchObjects(
                query: [
                    '@self'        => [
                        'register' => 'document',
                        'schema'   => 'anonymizationLink',
                    ],
                    'sourceFileId' => $fileId,
                ]
            );

            $existing = [];
            if (is_array($results) === true && empty($results) === false) {
                $existing = $this->extractLinkObjectData(candidate: $results[0]);
            }

            if (empty($existing) === false) {
                $object = $existing;
                $object['runCount'] = ((int) ($existing['runCount'] ?? 0) + 1);
            } else {
                $object = [
                    '@self'        => [
                        'register' => 'document',
                        'schema'   => 'anonymizationLink',
                    ],
                    'sourceFileId' => $fileId,
                    'runCount'     => 1,
                ];
            }

            $object = $this->applySourceNodeMetadata(object: $object, sourceNode: $sourceNode);

            // Anonymised-side metadata + run stats. Only successful runs
            // reach this method, so status is always 'anonymized'.
            $anonymizedName = (string) ($resultInfo['anonymizedFileName'] ?? '');
            $object['anonymizedFileId']   = (int) $resultInfo['anonymizedFileId'];
            $object['anonymizedFileName'] = $anonymizedName;
            $object['anonymizedFilePath'] = (string) ($resultInfo['anonymizedFilePath'] ?? '');
            $object['status']           = 'anonymized';
            $object['replacementCount'] = (int) ($resultInfo['replacementCount'] ?? 0);
            $object['anonymizedAt']     = date(format: 'c');

            $extension = strtolower(pathinfo($anonymizedName, PATHINFO_EXTENSION));
            if (in_array($extension, ['pdf', 'docx', 'odt', 'txt', 'html'], true) === true) {
                $object['outputFormat'] = $extension;
            }

            $saved  = $objectService->saveObject(
                object: $object,
                register: 'document',
                schema: 'anonymizationLink'
            );
            $linkId = $this->extractSavedObjectId(saved: $saved);
            if ($linkId !== null) {
                $resultInfo['anonymizationLinkId'] = $linkId;
            }

            $this->logger->info(
                'Anonymisation link recorded',
                [
                    'sourceFileId'     => $fileId,
                    'anonymizedFileId' => $object['anonymizedFileId'],
                    'runCount'         => $object['runCount'],
                ]
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                'recordAnonymizationLink failed; anonymisation result is unaffected: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
        }//end try

        return $resultInfo;

    }//end recordAnonymizationLink()


    /**
     * Apply best-effort source-node metadata (name, path, owner) to a link object.
     *
     * Each accessor is guarded with method_exists so the method tolerates any
     * file-node-like object (and mocks in unit tests) without fataling.
     *
     * @param array<string, mixed> $object     The link object being built.
     * @param mixed                $sourceNode The source file node.
     *
     * @return array<string, mixed> The object with any resolvable source metadata applied.
     */
    private function applySourceNodeMetadata(array $object, mixed $sourceNode): array
    {
        if (is_object($sourceNode) === false) {
            return $object;
        }

        if (method_exists(object_or_class: $sourceNode, method: 'getName') === true) {
            $object['sourceFileName'] = (string) $sourceNode->getName();
        }

        if (method_exists(object_or_class: $sourceNode, method: 'getPath') === true) {
            $object['sourceFilePath'] = (string) $sourceNode->getPath();
        }

        $owner = null;
        if (method_exists(object_or_class: $sourceNode, method: 'getOwner') === true) {
            $owner = $sourceNode->getOwner();
        }

        if ($owner !== null && method_exists(object_or_class: $owner, method: 'getUID') === true) {
            $object['anonymizedBy'] = (string) $owner->getUID();
        }

        return $object;

    }//end applySourceNodeMetadata()


    /**
     * Normalise a searchObjects() candidate to a plain array including its `@self`.
     *
     * @param mixed $candidate A search result entry (array, or an OR entity object).
     *
     * @return array<string, mixed> The object data, or an empty array if it could not be read.
     */
    private function extractLinkObjectData(mixed $candidate): array
    {
        if (is_array($candidate) === true) {
            return $candidate;
        }

        if (is_object($candidate) === true) {
            if (method_exists(object_or_class: $candidate, method: 'getObject') === true) {
                $payload = $candidate->getObject();
                if (is_array($payload) === true) {
                    if (isset($payload['@self']) === false
                        && method_exists(object_or_class: $candidate, method: 'getUuid') === true
                    ) {
                        $uuid = $candidate->getUuid();
                        if ($uuid !== null) {
                            $payload['@self'] = ['id' => $uuid];
                        }
                    }

                    return $payload;
                }
            }

            if (method_exists(object_or_class: $candidate, method: 'jsonSerialize') === true) {
                $payload = $candidate->jsonSerialize();
                if (is_array($payload) === true) {
                    return $payload;
                }
            }
        }//end if

        return [];

    }//end extractLinkObjectData()


    /**
     * Extract the persisted object's identifier from a saveObject() return value.
     *
     * @param mixed $saved The value returned by ObjectService::saveObject.
     *
     * @return string|null The object id/uuid, or null when it cannot be determined.
     */
    private function extractSavedObjectId(mixed $saved): ?string
    {
        if (is_object($saved) === true) {
            if (method_exists(object_or_class: $saved, method: 'getUuid') === true) {
                $uuid = $saved->getUuid();
                if (empty($uuid) === false) {
                    return (string) $uuid;
                }
            }

            if (method_exists(object_or_class: $saved, method: 'getId') === true) {
                $id = $saved->getId();
                if (empty($id) === false) {
                    return (string) $id;
                }
            }

            if (method_exists(object_or_class: $saved, method: 'jsonSerialize') === true) {
                $saved = $saved->jsonSerialize();
            }
        }

        if (is_array($saved) === true) {
            $self = ($saved['@self'] ?? []);
            $id   = ($self['id'] ?? ($self['uuid'] ?? ($saved['id'] ?? null)));
            if (empty($id) === false) {
                return (string) $id;
            }
        }

        return null;

    }//end extractSavedObjectId()


    /**
     * Render and attach the grondslagen summary to a freshly-anonymised file.
     *
     * If the anonymised file is a PDF, the summary is appended to it in place
     * (one extra page). For other formats, the summary is saved as a separate
     * `<base>_grondslagen.pdf` file beside the anonymised file.
     *
     * Summary-step failure is **non-fatal**: the anonymise call still
     * returns success; the result gets a structured `warning` field so the
     * caller can surface the issue to the operator without rolling back the
     * anonymisation.
     *
     * @param mixed                $anonymisedNode The Node/File returned by OR's anonymizeDocument.
     * @param int                  $sourceFileId   The pre-anonymisation source file id (used to look
     *                                             up the EntityRelation rows that carry the bases).
     * @param array<string, mixed>  $resultInfo     The current result info — extended with the
     *                                              summary's `summaryFileId` / `warning` fields and
     *                                              returned.
     * @param array<string, string> $placeholderMap OpenRegister's per-entity placeholder map
     *                                              (global entity id → emitted placeholder, e.g.
     *                                              `"7" => "[PERSOON: 1]"`) so the summary renders
     *                                              the SAME placeholder the document carries. Empty
     *                                              → summary falls back to `[<TYPE>: <entity_id>]`.
     *
     * @return array<string, mixed> The (possibly-extended) result info.
     */
    private function attachGrondslagenSummary(mixed $anonymisedNode, int $sourceFileId, array $resultInfo, array $placeholderMap=[]): array
    {
        if (($anonymisedNode instanceof \OCP\Files\File) === false) {
            $resultInfo['warning'] = 'grondslagen_summary_skipped: anonymised result is not a File node';
            return $resultInfo;
        }

        $mime  = $anonymisedNode->getMimeType();
        $isPdf = ($mime === 'application/pdf');

        try {
            if ($isPdf === true) {
                $this->grondslagenSummary->appendSummaryToPdf(
                    anonymisedFile: $anonymisedNode,
                    sourceFileId: $sourceFileId,
                    placeholderMap: $placeholderMap
                );
                $resultInfo['summaryAppended'] = true;
            } else {
                $summaryFile = $this->grondslagenSummary->renderSummaryBesideFile(
                    anonymisedFile: $anonymisedNode,
                    sourceFileId: $sourceFileId,
                    placeholderMap: $placeholderMap
                );
                $resultInfo['summaryAppended'] = false;
                $resultInfo['summaryFileId']   = $summaryFile->getId();
                $resultInfo['summaryFilePath'] = $summaryFile->getPath();
            }
        } catch (Exception $e) {
            $this->logger->warning(
                'Grondslagen summary attach failed',
                [
                    'fileId'       => $anonymisedNode->getId(),
                    'sourceFileId' => $sourceFileId,
                    'isPdf'        => $isPdf,
                    'error'        => $e->getMessage(),
                ]
            );
            $resultInfo['warning'] = 'grondslagen_summary_failed: '.$e->getMessage();
        }//end try

        return $resultInfo;

    }//end attachGrondslagenSummary()


}//end class
