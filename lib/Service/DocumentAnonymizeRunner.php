<?php
/**
 * Document Anonymize Runner
 *
 * Owns the per-document anonymise pipeline behind AnonymizationService:
 * the EML branch, the standard OpenRegister anonymise call, the replacement
 * statistics, the PDF-output gate, the optional grondslagen summary and the
 * post-run persistence (anonymisation link + publication consents).
 *
 * Extracted from AnonymizationService as a pure refactor so that class stays a
 * thin orchestrator over the detection and anonymise pipelines.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\ConversionFailedException;
use Psr\Log\LoggerInterface;

/**
 * Runs the per-document anonymise pipeline.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class DocumentAnonymizeRunner
{
    /**
     * Constructor for DocumentAnonymizeRunner
     *
     * @param LoggerInterface                 $logger              Logger for error reporting.
     * @param OpenRegisterServiceLocator      $locator             Resolver for OpenRegister services and mappers.
     * @param EntityDetectionService          $entityDetection     Entity mapping / result parsing service.
     * @param EmlAnonymizationService         $emlAnonymizer       The EML anonymise + PDF/A-3b assembly path.
     * @param AnonymisedPdfOutputService      $pdfOutput           The PDF-conversion gate on the anonymised
     *                                                             intermediate (cascade + rollback + pdf-only
     *                                                             cleanup).
     * @param ReplacementVerificationService  $replacementVerifier Replacement statistics for a run.
     * @param AnonymizationPersistenceService $persistence         Post-run persistence: anonymisation link +
     *                                                             publication consents.
     * @param GrondslagenSummaryAttacher      $summaryAttacher     Renders and attaches the per-document
     *                                                             grondslagen summary.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly OpenRegisterServiceLocator $locator,
        private readonly EntityDetectionService $entityDetection,
        private readonly EmlAnonymizationService $emlAnonymizer,
        private readonly AnonymisedPdfOutputService $pdfOutput,
        private readonly ReplacementVerificationService $replacementVerifier,
        private readonly AnonymizationPersistenceService $persistence,
        private readonly GrondslagenSummaryAttacher $summaryAttacher
    ) {

    }//end __construct()

    /**
     * Anonymize entities in a document.
     *
     * When `$options['appendBasisSummary']` is true, invokes the summary attacher
     * after the anonymised file has been written. For PDF output the summary is
     * appended as an extra page; otherwise a separate `<base>_grondslagen.pdf` is
     * written alongside. Summary failure is non-fatal: the anonymised file is
     * always preserved and a `warning` field is added to the response instead.
     *
     * When `$options['outputFormat']` is "pdf-only" (default) or "pdf", the
     * anonymised intermediate is run through the PdfConversionService cascade and
     * replaced with the PDF; on cascade failure the intermediate is rolled back
     * (best-effort) and a ConversionFailedException is thrown for the controller
     * to surface as HTTP 422. "pdf-only" additionally best-effort deletes the
     * native anonymised intermediate after a successful conversion so only the PDF
     * remains; "pdf" keeps it too; "preserve" skips conversion entirely.
     *
     * EML inputs are routed to OpenRegister's dedicated anonymise-EML API and
     * assembled into a PDF/A-3b by EmlPdfAssemblyService (OR's anonymizeDocument
     * throws on message/rfc822); "preserve" is overridden to PDF for EML.
     *
     * When `$options['unredactedEntities']` is non-empty, a publicationConsent
     * record is created for each entry AFTER the anonymise pipeline succeeds.
     *
     * @param int                         $fileId   The Nextcloud file ID.
     * @param array<array<string, mixed>> $entities The entities to anonymize.
     * @param array<string, mixed>        $options  Run options: appendBasisSummary (bool),
     *                                              outputFormat (string), unredactedEntities
     *                                              (array), scope (string) and dossierKey
     *                                              (string|null).
     *
     * @return array<string, mixed> Anonymization result with optional
     *                              warning/summaryFileId/createdConsents fields.
     *
     * @throws Exception                 If anonymization fails.
     * @throws ConversionFailedException When the cascade could not convert the anonymised
     *                                   intermediate. The intermediate is deleted (best-effort)
     *                                   before the exception propagates.
     *
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     */
    public function run(int $fileId, array $entities, array $options): array
    {
        try {
            $fileService    = $this->locator->get(className: 'OCA\OpenRegister\Service\FileService');
            $node           = $fileService->getFileById($fileId);
            $mappedEntities = $this->entityDetection->mapEntitiesForAnonymization($entities);

            $context = [
                'appendBasisSummary' => $options['appendBasisSummary'],
                'sourceNode'         => $node,
                'fileId'             => $fileId,
            ];

            // EML branch (eml-pdf-assembly): OR's anonymizeDocument() THROWS on
            // message/rfc822, so EML is routed to OR's dedicated anonymise-EML
            // API and assembled into a PDF/A-3b instead of taking the standard
            // path below. See EmlAnonymizationService.
            if ($this->emlAnonymizer->isEmlInput(node: $node) === true) {
                $eml = $this->emlAnonymizer->anonymize(
                    fileId: $fileId,
                    node: $node,
                    fileService: $fileService,
                    mappedEntities: $mappedEntities,
                    scope: $options['scope'],
                    dossierKey: $options['dossierKey']
                );

                $context['anonymisedNode'] = $eml['node'];
                $context['placeholderMap'] = $eml['placeholderMap'];

                return $this->finaliseResult(resultInfo: $eml['resultInfo'], context: $context);
            }

            return $this->anonymizeStandardDocument(
                fileService: $fileService,
                mappedEntities: $mappedEntities,
                context: $context,
                options: $options
            );
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

    }//end run()

    /**
     * Run the standard (non-EML) anonymise path for one document.
     *
     * Captures a textual projection of the ORIGINAL document BEFORE anonymization
     * so the replacement statistics reflect which mapped entity values were
     * actually present (and therefore eligible for str_ireplace inside
     * OpenRegister's DocumentProcessingHandler) instead of a fabricated count —
     * closes #286.
     *
     * Scope and dossierKey are passed positionally to OpenRegister's
     * reflectively-resolved FileService; a null dossierKey lets OpenRegister fall
     * back to the file's parent folder.
     *
     * @param mixed                           $fileService    OpenRegister FileService.
     * @param array<int, array<string,mixed>> $mappedEntities Entities forwarded to OpenRegister.
     * @param array<string, mixed>            $context        Run context (see finaliseResult).
     * @param array<string, mixed>            $options        outputFormat, scope, dossierKey and
     *                                                        unredactedEntities for this run.
     *
     * @return array<string, mixed> The anonymisation result.
     *
     * @throws ConversionFailedException When the PDF cascade is exhausted.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function anonymizeStandardDocument(
        mixed $fileService,
        array $mappedEntities,
        array $context,
        array $options
    ): array {
        $node         = $context['sourceNode'];
        $fileId       = $context['fileId'];
        $originalText = $this->replacementVerifier->readNodeText(node: $node);

        $result = $fileService->anonymizeDocument(
            $node,
            $mappedEntities,
            $options['scope'],
            $options['dossierKey']
        );

        $residualEntities          = $this->locator->lastResidualEntities(fileService: $fileService);
        $context['placeholderMap'] = $this->locator->lastPlaceholderMap(fileService: $fileService);

        $verification = $this->replacementVerifier->verify(
            mappedEntities: $mappedEntities,
            originalText: $originalText
        );
        $this->replacementVerifier->logStats(
            fileId: $fileId,
            verification: $verification,
            residualCount: count($residualEntities)
        );

        $result = $this->pdfOutput->convertResultToPdf(
            result: $result,
            outputFormat: $options['outputFormat'],
            fileId: $fileId
        );
        $context['anonymisedNode'] = $result;

        $resultInfo = $this->buildResultInfo(
            result: $result,
            verification: $verification,
            residualEntities: $residualEntities
        );

        if (empty($options['unredactedEntities']) === false) {
            $resultInfo = $this->persistence->createConsentsForUnredactedEntities(
                resultInfo: $resultInfo,
                unredactedEntities: $options['unredactedEntities']
            );
        }

        return $this->finaliseResult(resultInfo: $resultInfo, context: $context);

    }//end anonymizeStandardDocument()

    /**
     * Assemble the anonymise response payload.
     *
     * Surfaces the truth: `replacementsAttempted` is how many entities we forwarded
     * to OR; `replacementsApplied` is how many of those actually appeared (and were
     * therefore replaced) in the source text; `replacementsVerified` signals whether
     * the source could be read as text at all (binary formats cannot be verified at
     * this layer — see ReplacementVerificationService::readNodeText()). When
     * verified is false, `replacementsApplied` is null and `replacementCount` falls
     * back to attempted.
     *
     * @param mixed                $result           The anonymised node.
     * @param array<string, mixed> $verification     The verify() outcome.
     * @param array<int, mixed>    $residualEntities OpenRegister's best-effort residual list.
     *
     * @return array<string, mixed> The result info.
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function buildResultInfo(mixed $result, array $verification, array $residualEntities): array
    {
        $resultInfo = $this->entityDetection->parseAnonymizationResult($result);

        $resultInfo['replacementsAttempted'] = $verification['replacementsAttempted'];
        $resultInfo['replacementsApplied']   = $verification['replacementsApplied'];
        $resultInfo['replacementsVerified']  = $verification['replacementsVerified'];
        $resultInfo['unmatchedEntities']     = $verification['unmatchedEntities'];

        // Legacy field for backwards compatibility. When we could verify, this
        // now reflects what was ACTUALLY replaced (no longer the fabricated
        // count that #286 flagged).
        $resultInfo['replacementCount'] = ($verification['replacementsApplied'] ?? $verification['replacementsAttempted']);

        // Surface best-effort residuals so the UI can warn that the file was
        // produced but some entities could not be fully removed, and let the
        // operator refine them. `complete` drives the warning banner.
        $resultInfo['complete']         = (count($residualEntities) === 0);
        $resultInfo['residualCount']    = count($residualEntities);
        $resultInfo['residualEntities'] = $residualEntities;

        return $resultInfo;

    }//end buildResultInfo()

    /**
     * Apply the optional grondslagen summary and persist the anonymisation link.
     *
     * Shared tail of both the standard and the EML anonymise paths. The link is
     * recorded on the success path only, guarded on a known anonymised file id.
     *
     * @param array<string, mixed> $resultInfo The result assembled so far.
     * @param array<string, mixed> $context    Run context: appendBasisSummary, anonymisedNode,
     *                                         sourceNode, fileId, placeholderMap.
     *
     * @return array<string, mixed> The finalised result info.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
     */
    private function finaliseResult(array $resultInfo, array $context): array
    {
        if ($context['appendBasisSummary'] === true) {
            $resultInfo = $this->summaryAttacher->attachGrondslagenSummary(
                anonymisedNode: $context['anonymisedNode'],
                sourceFileId: $context['fileId'],
                resultInfo: $resultInfo,
                placeholderMap: $context['placeholderMap']
            );
        }

        if (empty($resultInfo['anonymizedFileId']) === false) {
            $resultInfo = $this->persistence->recordAnonymizationLink(
                fileId: $context['fileId'],
                sourceNode: $context['sourceNode'],
                resultInfo: $resultInfo
            );
        }

        return $resultInfo;

    }//end finaliseResult()
}//end class
