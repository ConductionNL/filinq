<?php

/**
 * Unit tests for AnonymizationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymisedPdfOutputService;
use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\AnonymizationPersistenceService;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\DocumentAnonymizeRunner;
use OCA\DocuDesk\Service\EmlAnonymizationService;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\GrondslagenSummaryAttacher;
use OCA\DocuDesk\Service\LegalBasesSummaryService;
use OCA\DocuDesk\Service\OpenRegisterServiceLocator;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCA\DocuDesk\Service\ProhibitionGateService;
use OCA\DocuDesk\Service\ProhibitionPolicyService;
use OCA\DocuDesk\Service\ProhibitionSkipTier;
use OCA\DocuDesk\Service\RelationSkipDecisionService;
use OCA\DocuDesk\Service\ReplacementVerificationService;
use OCA\OpenRegister\Db\EntityRelation;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for AnonymizationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceTest extends TestCase
{
    use BuildsAnonymizationService;

    /**
     * The Nextcloud file id carried by the relation fixture — the acting user
     * CAN reach this one.
     */
    private const RELATION_FILE_ID = 4242;

    /**
     * A file id the acting user can NOT reach; used to prove the relation
     * ownership guard denies rather than merely existing.
     */
    private const FOREIGN_FILE_ID = 9999;

    /**
     * Test that the source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__.'/../../../lib/Service/AnonymizationService.php'
        );

    }//end testSourceFileExists()

    /**
     * Test file contains expected class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('class AnonymizationService', $content);

    }//end testFileContainsClassDeclaration()

    /**
     * Test the public contract still exposes both pipeline entry points.
     *
     * @return void
     */
    public function testFileContainsExpectedMethods(): void
    {
        foreach (['extractAndDetectEntities', 'reExtractAndDetectEntities', 'anonymizeDocument'] as $method) {
            $this->assertTrue(
                method_exists(AnonymizationService::class, $method),
                'AnonymizationService must expose '.$method.'().'
            );
        }

    }//end testFileContainsExpectedMethods()

    /**
     * Test the anonymise entry points expose the documented options.
     *
     * The grondslagen summary is no longer a boolean flag: it is selected by
     * calling the dedicated `anonymizeDocumentWithBasisSummary()` entry point.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testAnonymizeDocumentSignatureAcceptsNewParams(): void
    {
        $this->assertTrue(
            method_exists(AnonymizationService::class, 'anonymizeDocumentWithBasisSummary'),
            'The grondslagen-summary variant must be an explicit entry point.'
        );

        $names = $this->parameterNames(AnonymizationService::class, 'anonymizeDocument');
        $this->assertContains('outputFormat', $names);
        $this->assertNotContains(
            'appendBasisSummary',
            $names,
            'anonymizeDocument() must not carry a summary flag argument.'
        );

    }//end testAnonymizeDocumentSignatureAcceptsNewParams()

    /**
     * anonymise-pdf-only-output-mode: both anonymise entry points accept an
     * `$outputFormat` argument, and its default is the privacy-correct
     * 'pdf-only'.
     *
     * @return void
     */
    public function testAnonymizeDocumentAcceptsOutputFormatArgument(): void
    {
        foreach (['anonymizeDocument', 'anonymizeDocumentWithBasisSummary'] as $entryPoint) {
            $params = (new \ReflectionMethod(AnonymizationService::class, $entryPoint))->getParameters();
            $found  = null;
            foreach ($params as $param) {
                if ($param->getName() === 'outputFormat') {
                    $found = $param;
                }
            }

            $this->assertNotNull($found, $entryPoint.'() must accept $outputFormat.');
            $this->assertTrue($found->isDefaultValueAvailable());
            $this->assertSame(
                'pdf-only',
                $found->getDefaultValue(),
                $entryPoint.'() must default $outputFormat to \'pdf-only\''
            );
        }

    }//end testAnonymizeDocumentAcceptsOutputFormatArgument()

    /**
     * The analysis call scopes automatic detection to the operator's enabled
     * entity types by passing the resolved whitelist to OpenRegister's
     * extractFile(). Guards against a regression that drops the whitelist
     * argument and silently reverts to detecting every type.
     *
     * @return void
     */
    public function testExtractAndDetectPassesEntityTypeWhitelist(): void
    {
        $extractor = $this->recordingExtractor();
        $service   = $this->serviceWithExtractor($extractor, ['PERSON', 'BSN']);

        $service->extractAndDetectEntities(fileId: 1);

        $this->assertSame(
            [['fileId' => 1, 'force' => false, 'types' => ['PERSON', 'BSN']]],
            $extractor->calls,
            'the resolved entity-type whitelist must be forwarded to extractFile()'
        );

    }//end testExtractAndDetectPassesEntityTypeWhitelist()

    /**
     * Opening a concept resumes by default: extractAndDetectEntities asks
     * OpenRegister for a cached (force=false) extraction so the
     * isSourceUpToDate short-circuit returns the existing relations, and only
     * reExtractAndDetectEntities() forces a fresh run.
     *
     * @return void
     */
    public function testExtractResumesByDefault(): void
    {
        $cached = $this->recordingExtractor();
        $this->serviceWithExtractor($cached, null)->extractAndDetectEntities(fileId: 1);
        $this->assertFalse($cached->calls[0]['force'], 'the default path must resume from cache');

        $forced = $this->recordingExtractor();
        $this->serviceWithExtractor($forced, null)->reExtractAndDetectEntities(fileId: 1);
        $this->assertTrue($forced->calls[0]['force'], 're-extraction must bypass the up-to-date check');

    }//end testExtractResumesByDefault()

    /**
     * The response carries the risk level resolved through FileEntityStatsService.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization/spec.md
     */
    public function testExtractAndDetectEntitiesIncludesRiskLevelInResponse(): void
    {
        $stats = $this->createMock(\OCA\DocuDesk\Service\FileEntityStatsService::class);
        $stats->method('tryGetRiskLevelService')->willReturn(null);
        $stats->method('getFileRiskLevel')->willReturn('high');

        $result = $this->serviceWithExtractor($this->recordingExtractor(), null, ['fileEntityStats' => $stats])
            ->extractAndDetectEntities(fileId: 1);

        $this->assertArrayHasKey('riskLevel', $result);
        $this->assertSame('high', $result['riskLevel']);

    }//end testExtractAndDetectEntitiesIncludesRiskLevelInResponse()

    /**
     * The service is constructible from its collaborators.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization/spec.md
     */
    public function testAnonymizationServiceAcceptsFileEntityStatsService(): void
    {
        $this->assertInstanceOf(AnonymizationService::class, $this->makeAnonymizationServiceFrom());

    }//end testAnonymizationServiceAcceptsFileEntityStatsService()

    /**
     * The PDF-conversion gate fires for BOTH 'pdf-only' and 'pdf', and is a
     * no-op when the anonymised result is already a PDF.
     *
     * @return void
     */
    public function testConversionGateFiresForPdfOnlyAndPdf(): void
    {
        foreach (['pdf-only', 'pdf'] as $format) {
            $cascade = $this->createMock(PdfConversionService::class);
            $cascade->expects($this->once())->method('convertToPdf')
                ->willReturn($this->fileNode('application/pdf'));

            $this->pdfGate($cascade)->convertResultToPdf(
                result: $this->fileNode('application/msword'),
                outputFormat: $format,
                fileId: 1
            );
        }

        $noop = $this->createMock(PdfConversionService::class);
        $noop->expects($this->never())->method('convertToPdf');
        $alreadyPdf = $this->fileNode('application/pdf');
        $this->assertSame(
            $alreadyPdf,
            $this->pdfGate($noop)->convertResultToPdf($alreadyPdf, 'pdf', 1),
            'an already-PDF result must be a no-op'
        );

    }//end testConversionGateFiresForPdfOnlyAndPdf()

    /**
     * The NATIVE intermediate — not the converted PDF — is the node deleted by
     * the pdf-only cleanup, i.e. the reference is captured before the cascade
     * reassigns the result.
     *
     * @return void
     */
    public function testNativeIntermediateCapturedBeforeReassignment(): void
    {
        $native    = $this->fileNode('application/msword');
        $converted = $this->fileNode('application/pdf');
        $native->expects($this->once())->method('delete');
        $converted->expects($this->never())->method('delete');

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')->willReturn($converted);

        $result = $this->pdfGate($cascade)->convertResultToPdf($native, 'pdf-only', 1);
        $this->assertSame($converted, $result);

    }//end testNativeIntermediateCapturedBeforeReassignment()

    /**
     * Only 'pdf-only' deletes the native intermediate after a successful
     * conversion ('pdf' keeps it), and that delete is best-effort — a throwing
     * delete never fails the run.
     *
     * @return void
     */
    public function testPdfOnlyBestEffortDeletesNativeIntermediate(): void
    {
        // 'pdf' keeps the native intermediate.
        $kept = $this->fileNode('application/msword');
        $kept->expects($this->never())->method('delete');

        $keepCascade = $this->createMock(PdfConversionService::class);
        $keepCascade->method('convertToPdf')->willReturn($this->fileNode('application/pdf'));
        $this->pdfGate($keepCascade)->convertResultToPdf($kept, 'pdf', 1);

        // 'pdf-only' cleanup failure is swallowed.
        $failing = $this->fileNode('application/msword');
        $failing->method('delete')->willThrowException(new \RuntimeException('locked'));

        $converted    = $this->fileNode('application/pdf');
        $okCascade    = $this->createMock(PdfConversionService::class);
        $okCascade->method('convertToPdf')->willReturn($converted);

        $this->assertSame(
            $converted,
            $this->pdfGate($okCascade)->convertResultToPdf($failing, 'pdf-only', 1),
            'a failing pdf-only cleanup must not fail an otherwise-successful run'
        );

    }//end testPdfOnlyBestEffortDeletesNativeIntermediate()

    /**
     * When the cascade is exhausted the un-converted intermediate is deleted and
     * the typed exception propagates for the controller's 422.
     *
     * @return void
     */
    public function testServiceWiresPdfConversionAndRollback(): void
    {
        $native = $this->fileNode('application/msword');
        $native->expects($this->once())->method('delete');

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')
            ->willThrowException(new \OCA\DocuDesk\Exception\ConversionFailedException());

        $this->expectException(\OCA\DocuDesk\Exception\ConversionFailedException::class);
        $this->pdfGate($cascade)->convertResultToPdf($native, 'pdf', 1);

    }//end testServiceWiresPdfConversionAndRollback()

    /**
     * The grondslagen summary attach step is owned by GrondslagenSummaryAttacher.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testTryAppendBasisSummaryMethodExists(): void
    {
        $this->assertTrue(
            method_exists(GrondslagenSummaryAttacher::class, 'attachGrondslagenSummary'),
            'GrondslagenSummaryAttacher must own the summary attach step.'
        );

    }//end testTryAppendBasisSummaryMethodExists()

    /**
     * A summary failure is non-fatal: the result gains a structured `warning`
     * field rather than aborting the anonymisation.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testWarningFieldDefinedOnSummaryFailure(): void
    {
        $summary = $this->createMock(LegalBasesSummaryService::class);
        $summary->method('appendSummaryToPdf')->willThrowException(new \Exception('renderer down'));

        $result = $this->summaryAttacher($summary)->attachGrondslagenSummary(
            anonymisedNode: $this->fileNode('application/pdf'),
            sourceFileId: 1,
            resultInfo: ['anonymizedFileId' => 9]
        );

        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('grondslagen_summary_failed', $result['warning']);
        $this->assertSame(9, $result['anonymizedFileId'], 'the anonymised file must be preserved');

    }//end testWarningFieldDefinedOnSummaryFailure()

    /**
     * A non-PDF anonymised file gets a summary written beside it, and the result
     * carries summaryFileId / summaryFilePath.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testPreserveModeSetsSummaryFields(): void
    {
        $summaryFile = $this->createMock(File::class);
        $summaryFile->method('getId')->willReturn(321);
        $summaryFile->method('getPath')->willReturn('/u/doc_grondslagen.pdf');

        $summary = $this->createMock(LegalBasesSummaryService::class);
        $summary->method('renderSummaryBesideFile')->willReturn($summaryFile);

        $result = $this->summaryAttacher($summary)->attachGrondslagenSummary(
            anonymisedNode: $this->fileNode('application/vnd.oasis.opendocument.text'),
            sourceFileId: 1,
            resultInfo: []
        );

        $this->assertFalse($result['summaryAppended']);
        $this->assertSame(321, $result['summaryFileId']);
        $this->assertSame('/u/doc_grondslagen.pdf', $result['summaryFilePath']);

    }//end testPreserveModeSetsSummaryFields()

    /**
     * Test #286: replacementCount reflects what was ACTUALLY replaced, not
     * count($mappedEntities). Three entities are forwarded but only two occur
     * in the source, so the response must report 2.
     *
     * @return void
     *
     * @spec issue #286 — fabricated replacement count
     */
    public function testReplacementCountIsNoLongerFabricatedFromMappedEntities(): void
    {
        $result = $this->runAnonymisePipeline();

        $this->assertSame(3, $result['replacementsAttempted']);
        $this->assertSame(2, $result['replacementsApplied']);
        $this->assertTrue($result['replacementsVerified']);
        $this->assertSame(
            2,
            $result['replacementCount'],
            'replacementCount must be the APPLIED count, never count($mappedEntities) — that is #286.'
        );
        $this->assertCount(1, $result['unmatchedEntities']);
        $this->assertSame('Carol', $result['unmatchedEntities'][0]['text']);

    }//end testReplacementCountIsNoLongerFabricatedFromMappedEntities()

    /**
     * Test #286: the verification helpers live on ReplacementVerificationService.
     *
     * @return void
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    public function testVerificationHelpersExist(): void
    {
        $this->assertTrue(method_exists(ReplacementVerificationService::class, 'verify'));
        $this->assertTrue(method_exists(ReplacementVerificationService::class, 'readNodeText'));

    }//end testVerificationHelpersExist()

    /**
     * Test #286 (behavioural): verify() correctly identifies entities that are
     * present in the source vs those that are not.
     *
     * @return void
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    public function testVerifyReplacementsDistinguishesAppliedFromUnmatched(): void
    {
        $mappedEntities = [
            ['text' => 'John Doe',     'entityType' => 'PERSON'],
            ['text' => '123456782',    'entityType' => 'BSN'],
            ['text' => 'NotInSource',  'entityType' => 'PERSON'],
            ['text' => '0612345678',   'entityType' => 'PHONE'],
        ];

        $originalText = "Mr. John Doe (BSN 123456782) lives in Amsterdam.\n"
            ."His mobile is 0612345678.";

        $result = $this->verifier()->verify(mappedEntities: $mappedEntities, originalText: $originalText);

        $this->assertSame(4, $result['replacementsAttempted']);
        $this->assertSame(3, $result['replacementsApplied'], 'Only entities present in source count as applied (#286).');
        $this->assertTrue($result['replacementsVerified']);
        $this->assertCount(1, $result['unmatchedEntities']);
        $this->assertSame('NotInSource', $result['unmatchedEntities'][0]['text']);
        $this->assertSame('PERSON', $result['unmatchedEntities'][0]['entityType']);

    }//end testVerifyReplacementsDistinguishesAppliedFromUnmatched()

    /**
     * Test #286: verification is case-insensitive (mirrors OR's str_ireplace
     * semantics in DocumentProcessingHandler).
     *
     * @return void
     */
    public function testVerifyReplacementsIsCaseInsensitive(): void
    {
        $result = $this->verifier()->verify(
            mappedEntities: [['text' => 'JOHN DOE', 'entityType' => 'PERSON']],
            originalText: 'Hi john doe.'
        );

        $this->assertSame(1, $result['replacementsApplied']);
        $this->assertCount(0, $result['unmatchedEntities']);

    }//end testVerifyReplacementsIsCaseInsensitive()

    /**
     * Test #286: when source text is null (binary format), verification is
     * reported as impossible — `replacementsApplied` is null and
     * `replacementsVerified` is false.
     *
     * @return void
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    public function testVerifyReplacementsReportsUnverifiedForBinaryFormats(): void
    {
        $result = $this->verifier()->verify(
            mappedEntities: [
                ['text' => 'John Doe',  'entityType' => 'PERSON'],
                ['text' => '123456782', 'entityType' => 'BSN'],
            ],
            originalText: null
        );

        $this->assertSame(2, $result['replacementsAttempted']);
        $this->assertNull($result['replacementsApplied'], 'Binary source cannot be verified — applied count must be null.');
        $this->assertFalse($result['replacementsVerified']);
        $this->assertSame([], $result['unmatchedEntities']);

    }//end testVerifyReplacementsReportsUnverifiedForBinaryFormats()

    /**
     * Test #286: readNodeText returns null for binary mime types (PDF, DOCX, …)
     * so verify() correctly degrades to "unverified".
     *
     * @return void
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    public function testReadNodeTextSafelyReturnsNullForBinaryMime(): void
    {
        $binaryNode = new class {
            public function getMimeType(): string
            {
                return 'application/pdf';
            }//end getMimeType()

            public function getContent(): string
            {
                return "%PDF-1.4\n...binary...";
            }//end getContent()
        };

        $this->assertNull($this->verifier()->readNodeText(node: $binaryNode));

    }//end testReadNodeTextSafelyReturnsNullForBinaryMime()

    /**
     * Test #286: readNodeText returns content for text-like mime types so
     * verification can run.
     *
     * @return void
     */
    public function testReadNodeTextSafelyReturnsContentForTextMime(): void
    {
        $textNode = new class {
            public function getMimeType(): string
            {
                return 'text/plain';
            }//end getMimeType()

            public function getContent(): string
            {
                return 'Hello John Doe.';
            }//end getContent()
        };

        $this->assertSame('Hello John Doe.', $this->verifier()->readNodeText(node: $textNode));

    }//end testReadNodeTextSafelyReturnsContentForTextMime()

    /**
     * The pure tier classifier: absolute at/above threshold, releasable-with-
     * force below, allow when forced sub-threshold.
     *
     * @return void
     */
    public function testClassifyProhibitionSkipTiers(): void
    {
        $tier = new ProhibitionSkipTier();

        $this->assertSame('block_absolute', $tier->classify(0.90, 0.85, true));
        $this->assertSame('block_absolute', $tier->classify(0.85, 0.85, false));
        $this->assertSame('block_releasable', $tier->classify(0.62, 0.85, false));
        $this->assertSame('allow', $tier->classify(0.62, 0.85, true));

    }//end testClassifyProhibitionSkipTiers()

    /**
     * Skipping a high-confidence prohibited entity is rejected 422 (absolute),
     * even with force, and performs no OpenRegister write.
     *
     * @return void
     */
    public function testSkipHighConfidenceProhibitedIsBlockedEvenWithForce(): void
    {
        $mapper = $this->mapperWithProhibitedRelation(0.97);
        $mapper->expects($this->never())->method('updateDecisionMetadata');

        $result = $this->makeServiceWithMatcher($this->prohibitionMatcher(), $mapper)
            ->applyRelationSkipDecision(relationId: 7, skip: true, bases: null, force: true);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($result['body']['prohibitionMatch']['absolute']);
        $this->assertSame('Jansen', $result['body']['prohibitionMatch']['entityName']);
        $this->assertSame('R-X', $result['body']['prohibitionMatch']['ruleId']);

    }//end testSkipHighConfidenceProhibitedIsBlockedEvenWithForce()

    /**
     * A sub-threshold prohibited skip is 422 without force and allowed with it.
     *
     * @return void
     */
    public function testSkipSubThresholdProhibitedNeedsForce(): void
    {
        $blocked = $this->makeServiceWithMatcher($this->prohibitionMatcher(), $this->mapperWithProhibitedRelation(0.62))
            ->applyRelationSkipDecision(relationId: 7, skip: true, bases: null, force: false);
        $this->assertSame(422, $blocked['status']);
        $this->assertFalse($blocked['body']['prohibitionMatch']['absolute']);

        $mapper = $this->mapperWithProhibitedRelation(0.62);
        $mapper->expects($this->once())->method('updateDecisionMetadata')
            ->with($this->anything(), ['skipAnonymization' => true], $this->anything());
        $allowed = $this->makeServiceWithMatcher($this->prohibitionMatcher(), $mapper)
            ->applyRelationSkipDecision(relationId: 7, skip: true, bases: null, force: true);
        $this->assertSame(200, $allowed['status']);

    }//end testSkipSubThresholdProhibitedNeedsForce()

    /**
     * Including an entity (skip=false) is always allowed and forwarded, with no
     * prohibition match lookup.
     *
     * @return void
     */
    public function testIncludeDecisionIsAlwaysForwarded(): void
    {
        $relation = new EntityRelation();
        $relation->setFileId(self::RELATION_FILE_ID);
        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('find')->with(7)->willReturn($relation);
        // The acting user is forwarded as the audit actor: OpenRegister records
        // every decision flip, and an entry with no actor is an audit gap.
        $mapper->expects($this->once())->method('updateDecisionMetadata')
            ->with($relation, ['skipAnonymization' => false], $this->anything());

        $result = $this->makeServiceWithMatcher($this->prohibitionMatcher(), $mapper)
            ->applyRelationSkipDecision(relationId: 7, skip: false, bases: null, force: false);

        $this->assertSame(200, $result['status']);

    }//end testIncludeDecisionIsAlwaysForwarded()

    /**
     * A relation whose document is NOT in the acting user's file tree is
     * refused with the same 404 an unknown relation gets — and NOTHING is
     * written.
     *
     * `EntityRelationMapper::find()` is an unscoped primary-key lookup, so
     * without this guard the endpoint let any authenticated user flip
     * `skipAnonymization` on someone else's document and leave its PII
     * un-redacted (OWASP A01:2021, IDOR).
     *
     * @return void
     */
    public function testRelationOnAForeignDocumentIsRefusedAndWritesNothing(): void
    {
        $relation = new EntityRelation();
        $relation->setFileId(self::FOREIGN_FILE_ID);

        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('find')->with(7)->willReturn($relation);
        $mapper->expects($this->never())->method('updateDecisionMetadata');

        $service = $this->makeAnonymizationServiceFrom(
            [
                'logger'      => $this->createMock(LoggerInterface::class),
                'container'   => $this->matcherContainer($this->prohibitionMatcher(), $mapper),
                'appManager'  => $this->openRegisterAppManager(),
                'userSession' => $this->grantingSession(),
                // Resolves only RELATION_FILE_ID, so FOREIGN_FILE_ID is out of reach.
                'rootFolder'  => $this->rootFolderResolving(self::RELATION_FILE_ID),
            ]
        );

        $result = $service->applyRelationSkipDecision(relationId: 7, skip: false, bases: null, force: false);

        $this->assertSame(404, $result['status']);

    }//end testRelationOnAForeignDocumentIsRefusedAndWritesNothing()

    /**
     * With no signed-in user there is nobody to scope the document to, so the
     * decision is refused 401 before the relation is even loaded.
     *
     * @return void
     */
    public function testRelationDecisionWithoutASessionIsRefused(): void
    {
        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->expects($this->never())->method('updateDecisionMetadata');

        $service = $this->makeAnonymizationServiceFrom(
            [
                'logger'     => $this->createMock(LoggerInterface::class),
                'container'  => $this->matcherContainer($this->prohibitionMatcher(), $mapper),
                'appManager' => $this->openRegisterAppManager(),
                // Default IUserSession mock: getUser() returns null.
            ]
        );

        $result = $service->applyRelationSkipDecision(relationId: 7, skip: false, bases: null, force: false);

        $this->assertSame(401, $result['status']);

    }//end testRelationDecisionWithoutASessionIsRefused()

    /**
     * The policy pass flags prohibition matches (with the correct high/low
     * tier), auto-skips standing-consent matches on their relation, and leaves
     * unmatched entities untouched.
     *
     * The pass is owned by ProhibitionPolicyService, so it is exercised there.
     *
     * @return void
     */
    public function testApplyPolicyDecisionsFlagsProhibitionsAndSkipsStandingConsents(): void
    {
        $matcher = $this->createMock(PolicyMatchService::class);
        $matcher->method('highConfidenceThreshold')->willReturn(0.85);
        $matcher->method('match')->willReturnCallback(
            static function (string $text, string $type): ?array {
                return match ($text) {
                    'Jansen' => ['uuid' => 'R-P', 'kind' => PolicyMatchService::KIND_PROHIBITION, 'entityType' => $type, 'primaryName' => 'Undercover'],
                    'LowP'   => ['uuid' => 'R-L', 'kind' => PolicyMatchService::KIND_PROHIBITION, 'entityType' => $type, 'primaryName' => 'Maybe'],
                    'Kuiper' => ['uuid' => 'R-SC', 'kind' => PolicyMatchService::KIND_STANDING_CONSENT, 'entityType' => $type, 'primaryName' => 'Woordvoerder'],
                    default  => null,
                };
            }
        );

        // Standing-consent match must auto-skip exactly relation 8.
        $relation = $this->createMock(EntityRelation::class);
        $mapper   = $this->createMock(EntityRelationMapper::class);
        $mapper->expects($this->once())->method('find')->with(8)->willReturn($relation);
        $mapper->expects($this->once())->method('updateDecisionMetadata')
            ->with($relation, ['skipAnonymization' => true])->willReturn($relation);

        $entities = [
            ['type' => 'PERSON', 'value' => 'Jansen', 'confidence' => 0.97, 'relationId' => 7, 'skipAnonymization' => false],
            ['type' => 'PERSON', 'value' => 'Kuiper', 'confidence' => 0.90, 'relationId' => 8, 'skipAnonymization' => false],
            ['type' => 'PERSON', 'value' => 'LowP',   'confidence' => 0.62, 'relationId' => 9, 'skipAnonymization' => false],
            ['type' => 'PERSON', 'value' => 'De Vries','confidence' => 0.50, 'relationId' => 10, 'skipAnonymization' => false],
        ];

        $result = $this->policyWithMatcher($matcher)->applyPolicyDecisions(
            entities: $entities,
            entityRelationMapper: $mapper
        );

        // High-confidence prohibition: flagged, absolute, not skipped.
        $this->assertSame(
            ['ruleId' => 'R-P', 'ruleName' => 'Undercover', 'highConfidence' => true],
            $result[0]['prohibitionMatch']
        );
        $this->assertFalse($result[0]['skipAnonymization']);

        // Standing consent: not flagged, auto-skipped.
        $this->assertNull($result[1]['prohibitionMatch']);
        $this->assertTrue($result[1]['skipAnonymization']);

        // Sub-threshold prohibition: flagged with highConfidence false.
        $this->assertFalse($result[2]['prohibitionMatch']['highConfidence']);

        // No match: null, untouched.
        $this->assertNull($result[3]['prohibitionMatch']);
        $this->assertFalse($result[3]['skipAnonymization']);

    }//end testApplyPolicyDecisionsFlagsProhibitionsAndSkipsStandingConsents()

    /**
     * The backstop reports an absolute prohibition match that was left
     * un-redacted (skipped), and reports nothing when it is being redacted.
     *
     * @return void
     */
    public function testAbsoluteProhibitionBackstop(): void
    {
        $matcher = $this->createMock(PolicyMatchService::class);
        $matcher->method('highConfidenceThreshold')->willReturn(0.85);
        $matcher->method('matchProhibition')->willReturnCallback(
            static function (string $text, string $type): ?array {
                return $text === 'Jansen'
                    ? ['uuid' => 'R-X', 'kind' => PolicyMatchService::KIND_PROHIBITION, 'entityType' => $type, 'primaryName' => 'Undercover']
                    : null;
            }
        );

        $all = [
            ['relation_id' => 7, 'entity_id' => 42, 'entity_value' => 'Jansen', 'entity_type' => 'PERSON', 'confidence' => 0.97],
            ['relation_id' => 8, 'entity_id' => 43, 'entity_value' => 'Bob', 'entity_type' => 'PERSON', 'confidence' => 0.90],
        ];

        // Only relation 8 is being redacted → relation 7 (Jansen) is skipped.
        $skipMapper = $this->createMock(EntityRelationMapper::class);
        $skipMapper->method('findEntitiesForFile')->willReturn($all);
        $skipMapper->method('findEntitiesForAnonymization')->willReturn([['relation_id' => 8]]);
        $violations = $this->makeServiceWithMatcher($matcher, $skipMapper)->absoluteProhibitionViolations(100);
        $this->assertCount(1, $violations);
        $this->assertSame('Jansen', $violations[0]['entityName']);
        $this->assertTrue($violations[0]['absolute']);

        // Jansen is in the redaction set → no violation.
        $okMapper = $this->createMock(EntityRelationMapper::class);
        $okMapper->method('findEntitiesForFile')->willReturn($all);
        $okMapper->method('findEntitiesForAnonymization')->willReturn([['relation_id' => 7], ['relation_id' => 8]]);
        $this->assertSame([], $this->makeServiceWithMatcher($matcher, $okMapper)->absoluteProhibitionViolations(100));

    }//end testAbsoluteProhibitionBackstop()

    /*
     * ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------
     */

    /**
     * Parameter names of a method, in declaration order.
     *
     * @param string $class  Fully qualified class name.
     * @param string $method Method name.
     *
     * @return array<int, string> The parameter names.
     */
    private function parameterNames(string $class, string $method): array
    {
        return array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            (new \ReflectionMethod($class, $method))->getParameters()
        );

    }//end parameterNames()

    /**
     * A TextExtractionService double that records every extractFile() call.
     *
     * @return object The recording extractor.
     */
    private function recordingExtractor(): object
    {
        return new class {
            /**
             * Recorded calls: fileId, force and the entity-type whitelist.
             *
             * @var array<int, array<string, mixed>>
             */
            public array $calls = [];

            /**
             * Record an extraction request.
             *
             * @param int                     $fileId      The file id.
             * @param bool                    $force       Whether a fresh run was requested.
             * @param array<int, string>|null $entityTypes The enabled entity types.
             *
             * @return void
             */
            public function extractFile(int $fileId, bool $force=false, ?array $entityTypes=null): void
            {
                $this->calls[] = ['fileId' => $fileId, 'force' => $force, 'types' => $entityTypes];

            }//end extractFile()
        };

    }//end recordingExtractor()

    /**
     * Build a service whose container resolves the given extractor.
     *
     * @param object                  $extractor The TextExtractionService double.
     * @param array<int, string>|null $whitelist The enabled entity types.
     * @param array<string, object>   $extra     Extra dependency overrides.
     *
     * @return AnonymizationService The service under test.
     */
    private function serviceWithExtractor(object $extractor, ?array $whitelist, array $extra=[]): AnonymizationService
    {
        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('findEntitiesForFile')->willReturn([]);

        $grondslag = $this->createMock(\OCA\DocuDesk\Service\LegalBasisProposalService::class);
        $grondslag->method('getEntityTypeWhitelist')->willReturn($whitelist);
        $grondslag->method('enrichEntitiesWithBases')->willReturnArgument(0);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            static function (string $class) use ($extractor, $mapper, $grondslag) {
                return match ($class) {
                    'OCA\OpenRegister\Service\TextExtractionService' => $extractor,
                    'OCA\OpenRegister\Db\EntityRelationMapper'       => $mapper,
                    'OCA\DocuDesk\Service\LegalBasisProposalService'  => $grondslag,
                    default => throw new \RuntimeException('Unknown service: '.$class),
                };
            }
        );

        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getInstalledApps')->willReturn(['openregister']);

        return $this->makeAnonymizationServiceFrom(
            array_merge(['container' => $container, 'appManager' => $appManager], $extra)
        );

    }//end serviceWithExtractor()

    /**
     * Build the PDF-output gate around a cascade double.
     *
     * @param PdfConversionService $cascade The cascade double.
     *
     * @return AnonymisedPdfOutputService The gate under test.
     */
    private function pdfGate(PdfConversionService $cascade): AnonymisedPdfOutputService
    {
        return new AnonymisedPdfOutputService(logger: new NullLogger(), pdfConversion: $cascade);

    }//end pdfGate()

    /**
     * Build the grondslagen summary attacher around a renderer double.
     *
     * @param LegalBasesSummaryService $summary The renderer double.
     *
     * @return GrondslagenSummaryAttacher The attacher under test.
     */
    private function summaryAttacher(LegalBasesSummaryService $summary): GrondslagenSummaryAttacher
    {
        return new GrondslagenSummaryAttacher(logger: new NullLogger(), grondslagenSummary: $summary);

    }//end summaryAttacher()

    /**
     * The replacement-statistics collaborator.
     *
     * @return ReplacementVerificationService The verifier.
     */
    private function verifier(): ReplacementVerificationService
    {
        return new ReplacementVerificationService(logger: new NullLogger());

    }//end verifier()

    /**
     * A File double reporting the given mime type.
     *
     * @param string $mime The mime type.
     *
     * @return File The node double.
     */
    private function fileNode(string $mime): File
    {
        $node = $this->createMock(File::class);
        $node->method('getMimeType')->willReturn($mime);

        return $node;

    }//end fileNode()

    /**
     * Run the real anonymise pipeline against fake OpenRegister collaborators.
     *
     * Three entities are forwarded; only two of them occur in the source text.
     *
     * @return array<string, mixed> The anonymisation result.
     */
    private function runAnonymisePipeline(): array
    {
        $sourceNode = new class {
            public function getMimeType(): string
            {
                return 'text/plain';
            }//end getMimeType()

            public function getName(): string
            {
                return 'doc.txt';
            }//end getName()

            public function getContent(): string
            {
                return 'Alice met Bob in Amsterdam.';
            }//end getContent()
        };

        $anonymisedNode = new class {
            public function getId(): int
            {
                return 99;
            }//end getId()

            public function getName(): string
            {
                return 'doc_anonymized.txt';
            }//end getName()

            public function getPath(): string
            {
                return '/u/doc_anonymized.txt';
            }//end getPath()

            public function getMimeType(): string
            {
                return 'text/plain';
            }//end getMimeType()
        };

        $fileService = new class($sourceNode, $anonymisedNode) {
            /**
             * @param object $source     The pre-anonymisation node.
             * @param object $anonymised The node OR hands back.
             */
            public function __construct(private object $source, private object $anonymised)
            {
            }//end __construct()

            /**
             * @param int $fileId The file id.
             *
             * @return object The source node.
             */
            public function getFileById(int $fileId): object
            {
                return $this->source;
            }//end getFileById()

            /**
             * @param object                          $node       The source node.
             * @param array<int, array<string,mixed>> $entities   Mapped entities.
             * @param string                          $scope      Numbering scope.
             * @param string|null                     $dossierKey Dossier folder id.
             *
             * @return object The anonymised node.
             */
            public function anonymizeDocument(object $node, array $entities, string $scope, ?string $dossierKey): object
            {
                return $this->anonymised;
            }//end anonymizeDocument()
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            static function (string $class) use ($fileService) {
                if ($class === 'OCA\OpenRegister\Service\FileService') {
                    return $fileService;
                }

                throw new \RuntimeException('Unknown service: '.$class);
            }
        );

        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getInstalledApps')->willReturn(['openregister']);

        $logger          = new NullLogger();
        $locator         = new OpenRegisterServiceLocator($appManager, $container);
        $entityDetection = new EntityDetectionService(new AnonymizationResultParser());

        $runner = new DocumentAnonymizeRunner(
            logger: $logger,
            locator: $locator,
            entityDetection: $entityDetection,
            emlAnonymizer: new EmlAnonymizationService(
                logger: $logger,
                entityDetection: $entityDetection,
                emlAssembly: $this->createMock(EmlPdfAssemblyService::class)
            ),
            pdfOutput: new AnonymisedPdfOutputService(
                logger: $logger,
                pdfConversion: $this->createMock(PdfConversionService::class)
            ),
            replacementVerifier: new ReplacementVerificationService(logger: $logger),
            persistence: new AnonymizationPersistenceService(
                logger: $logger,
                locator: $locator,
                consentCrud: $this->createMock(\OCA\DocuDesk\Service\ConsentCrudService::class),
                consentService: $this->createMock(\OCA\DocuDesk\Service\ConsentService::class)
            ),
            summaryAttacher: new GrondslagenSummaryAttacher(
                logger: $logger,
                grondslagenSummary: $this->createMock(LegalBasesSummaryService::class)
            )
        );

        return $runner->run(
            fileId: 1,
            entities: [
                ['value' => 'Alice', 'type' => 'PERSON'],
                ['value' => 'Bob',   'type' => 'PERSON'],
                ['value' => 'Carol', 'type' => 'PERSON'],
            ],
            options: [
                'appendBasisSummary' => false,
                'outputFormat'       => 'preserve',
                'unredactedEntities' => [],
                'scope'              => 'document',
                'dossierKey'         => null,
            ]
        );

    }//end runAnonymisePipeline()

    /**
     * Build an AnonymizationService whose container resolves the given policy
     * matcher, with all other collaborators mocked.
     *
     * @param PolicyMatchService        $matcher The matcher the container returns.
     * @param EntityRelationMapper|null $mapper  Optional mapper the container returns.
     *
     * @return AnonymizationService The service under test.
     */
    private function makeServiceWithMatcher(PolicyMatchService $matcher, ?EntityRelationMapper $mapper=null): AnonymizationService
    {
        return $this->makeAnonymizationServiceFrom(
            [
                'logger'      => $this->createMock(LoggerInterface::class),
                'container'   => $this->matcherContainer($matcher, $mapper),
                'appManager'  => $this->openRegisterAppManager(),
                'userSession' => $this->grantingSession(),
                'rootFolder'  => $this->rootFolderResolving(self::RELATION_FILE_ID),
            ]
        );

    }//end makeServiceWithMatcher()

    /**
     * A session whose acting user is `alice`.
     *
     * @return IUserSession The session double.
     */
    private function grantingSession(): IUserSession
    {
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');

        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        return $session;

    }//end grantingSession()

    /**
     * A root folder whose user folder resolves EXACTLY the given file id — any
     * other id resolves to nothing, which is what "the file is not in this
     * user's tree" looks like to the ownership guard.
     *
     * @param int $resolvableFileId The only file id this user can reach.
     *
     * @return IRootFolder The root-folder double.
     */
    private function rootFolderResolving(int $resolvableFileId): IRootFolder
    {
        $node = $this->createMock(File::class);

        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('getById')->willReturnCallback(
            static function (int $fileId) use ($resolvableFileId, $node) {
                if ($fileId === $resolvableFileId) {
                    return [$node];
                }

                return [];
            }
        );

        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willReturn($userFolder);

        return $rootFolder;

    }//end rootFolderResolving()

    /**
     * Build a ProhibitionPolicyService whose container resolves the matcher.
     *
     * @param PolicyMatchService $matcher The matcher the container returns.
     *
     * @return ProhibitionPolicyService The policy service under test.
     */
    private function policyWithMatcher(PolicyMatchService $matcher): ProhibitionPolicyService
    {
        $logger     = $this->createMock(LoggerInterface::class);
        $container  = $this->matcherContainer($matcher, null);
        $appManager = $this->openRegisterAppManager();
        $locator    = new OpenRegisterServiceLocator($appManager, $container);

        return new ProhibitionPolicyService(
            logger: $logger,
            container: $container,
            locator: $locator,
            gate: new ProhibitionGateService(
                logger: $logger,
                appConfig: $this->createMock(\OCP\IAppConfig::class),
                container: $container,
                locator: $locator
            ),
            skipDecisions: new RelationSkipDecisionService(
                logger: $logger,
                container: $container,
                locator: $locator,
                userSession: $this->grantingSession(),
                rootFolder: $this->rootFolderResolving(self::RELATION_FILE_ID)
            )
        );

    }//end policyWithMatcher()

    /**
     * A container resolving PolicyMatchService (and optionally EntityRelationMapper).
     *
     * @param PolicyMatchService        $matcher The matcher.
     * @param EntityRelationMapper|null $mapper  Optional mapper.
     *
     * @return ContainerInterface The container double.
     */
    private function matcherContainer(PolicyMatchService $matcher, ?EntityRelationMapper $mapper): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            static function (string $id) use ($matcher, $mapper) {
                return match ($id) {
                    'OCA\DocuDesk\Service\PolicyMatchService'  => $matcher,
                    'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
                    default                                    => null,
                };
            }
        );

        return $container;

    }//end matcherContainer()

    /**
     * An app manager reporting OpenRegister as installed.
     *
     * @return IAppManager The app manager double.
     */
    private function openRegisterAppManager(): IAppManager
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getInstalledApps')->willReturn(['openregister']);

        return $appManager;

    }//end openRegisterAppManager()

    /**
     * Build a mapper whose find()/findEntitiesForFile() resolve one prohibited
     * occurrence with the given confidence.
     *
     * @param float $confidence Detection confidence for the occurrence.
     *
     * @return EntityRelationMapper The mock mapper.
     */
    private function mapperWithProhibitedRelation(float $confidence): EntityRelationMapper
    {
        // EntityRelation uses magic getters (can't be mocked); use a real one.
        // The file id is what the ownership guard resolves against, so it must
        // be set — and must DIFFER from the "foreign" id used by the denial
        // tests, otherwise granting and denying look identical.
        $relation = new EntityRelation();
        $relation->setFileId(self::RELATION_FILE_ID);

        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('find')->with(7)->willReturn($relation);
        $mapper->method('findEntitiesForFile')->willReturn(
            [['relation_id' => 7, 'entity_id' => 42, 'entity_value' => 'Jansen', 'entity_type' => 'PERSON', 'confidence' => $confidence]]
        );

        return $mapper;

    }//end mapperWithProhibitedRelation()

    /**
     * A prohibition matcher that flags any entity as a prohibition.
     *
     * @return PolicyMatchService The mock matcher.
     */
    private function prohibitionMatcher(): PolicyMatchService
    {
        $matcher = $this->createMock(PolicyMatchService::class);
        $matcher->method('highConfidenceThreshold')->willReturn(0.85);
        $matcher->method('matchProhibition')->willReturn(
            ['uuid' => 'R-X', 'kind' => PolicyMatchService::KIND_PROHIBITION, 'entityType' => 'PERSON', 'primaryName' => 'Undercover']
        );

        return $matcher;

    }//end prohibitionMatcher()
}//end class
