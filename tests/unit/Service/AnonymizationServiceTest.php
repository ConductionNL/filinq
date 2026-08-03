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

use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCA\OpenRegister\Db\EntityRelation;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

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
    /**
     * Check if the class can be loaded without parse errors
     *
     * @return void
     */
    private function requireClassOrSkip(): void
    {
        try {
            $file = __DIR__.'/../../../lib/Service/AnonymizationService.php';
            $code = php_strip_whitespace($file);
            if (empty($code) === true) {
                $this->markTestSkipped('AnonymizationService has parse errors.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('AnonymizationService has parse errors: '.$e->getMessage());
        }

    }//end requireClassOrSkip()

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
     * Test file contains expected methods
     *
     * @return void
     */
    public function testFileContainsExpectedMethods(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function extractAndDetectEntities', $content);
        $this->assertStringContainsString('function anonymizeDocument', $content);

    }//end testFileContainsExpectedMethods()

    /**
     * Test anonymizeDocument signature accepts appendBasisSummary and outputFormat.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testAnonymizeDocumentSignatureAcceptsNewParams(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('appendBasisSummary', $content);
        $this->assertStringContainsString('outputFormat', $content);

    }//end testAnonymizeDocumentSignatureAcceptsNewParams()


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
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('getEntityTypeWhitelist', $content);
        $this->assertStringContainsString('extractFile($fileId, $force, $entityTypes)', $content);

    }//end testExtractAndDetectPassesEntityTypeWhitelist()


    /**
     * Opening a concept resumes by default: extractAndDetectEntities defaults
     * $force to false (OpenRegister's isSourceUpToDate short-circuit returns
     * the existing relations) and only re-analyses when force is requested.
     * Guards against a regression that hardcodes force=true and re-runs
     * detection — appending duplicate relations — on every open.
     *
     * @return void
     */
    public function testExtractResumesByDefault(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('extractAndDetectEntities(int $fileId, bool $force=false)', $content);
        $this->assertStringNotContainsString('extractFile($fileId, true', $content);

    }//end testExtractResumesByDefault()


    /**
     * anonymise-pdf-only-output-mode: AnonymizationService must accept an
     * `$outputFormat` argument on anonymizeDocument so the controller can
     * pass through the per-call gate, and its default is now 'pdf-only'.
     *
     * @return void
     */
    public function testAnonymizeDocumentAcceptsOutputFormatArgument(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // Signature must include the outputFormat parameter with a
        // default of 'pdf-only' (the new privacy-correct default).
        $this->assertMatchesRegularExpression(
            '/function anonymizeDocument\([^)]*\$outputFormat\s*=\s*\'pdf-only\'[^)]*\)/s',
            $content,
            'anonymizeDocument must accept $outputFormat with a default of \'pdf-only\''
        );

    }//end testAnonymizeDocumentAcceptsOutputFormatArgument()


    /**
     * anonymise-pdf-only-output-mode: the PDF-conversion gate must fire for
     * BOTH 'pdf-only' and 'pdf' (both run the cascade), still guarded by the
     * "not already a PDF" mime check — so an already-a-PDF result is a no-op
     * for either mode (tasks 3.1 / 3.3).
     *
     * @return void
     */
    public function testConversionGateFiresForPdfOnlyAndPdf(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        $this->assertMatchesRegularExpression(
            '/in_array\(\s*\$outputFormat\s*,\s*\[\s*\'pdf-only\'\s*,\s*\'pdf\'\s*\]/s',
            $content,
            'the conversion gate must fire for both pdf-only and pdf'
        );
        $this->assertStringContainsString(
            "\$resultMime !== 'application/pdf'",
            $content,
            'the cascade must stay guarded by the not-already-a-PDF mime check (already-a-PDF no-op)'
        );

    }//end testConversionGateFiresForPdfOnlyAndPdf()


    /**
     * anonymise-pdf-only-output-mode: the native anonymised node must be
     * captured into $nativeIntermediate BEFORE $result is reassigned by
     * convertToPdf(), otherwise the reference to delete is lost (task 3.1).
     *
     * @return void
     */
    public function testNativeIntermediateCapturedBeforeReassignment(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        $capturePos = strpos($content, '$nativeIntermediate = $result;');
        $convertPos = strpos($content, '$result = $this->pdfConversion->convertToPdf($result);');

        $this->assertNotFalse($capturePos, 'native intermediate must be captured into $nativeIntermediate');
        $this->assertNotFalse($convertPos, 'convertToPdf must reassign $result');
        $this->assertLessThan(
            $convertPos,
            $capturePos,
            'native node must be captured BEFORE $result is reassigned to the converted PDF'
        );

    }//end testNativeIntermediateCapturedBeforeReassignment()


    /**
     * anonymise-pdf-only-output-mode: after a successful conversion, only
     * 'pdf-only' deletes the native intermediate, and that delete is
     * best-effort — wrapped in try/catch Throwable with a PII-free warning
     * that never re-throws (tasks 3.2 / 4.1 / 4.2). 'pdf' keeps the native
     * file (task 4.4).
     *
     * @return void
     */
    public function testPdfOnlyBestEffortDeletesNativeIntermediate(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // The delete is gated on the pdf-only mode only (pdf keeps native).
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*\$outputFormat\s*===\s*\'pdf-only\'\s*\)\s*\{\s*try\s*\{\s*\$nativeIntermediate->delete\(\);/s',
            $content,
            'only pdf-only deletes the captured native intermediate after a successful conversion'
        );

        // Best-effort: caught as Throwable and logged, never re-thrown.
        $this->assertMatchesRegularExpression(
            '/\$nativeIntermediate->delete\(\);\s*\}\s*catch\s*\(\s*Throwable\s+\$deleteError\s*\)/s',
            $content,
            'the native-intermediate delete must be best-effort (catch Throwable)'
        );
        $this->assertStringContainsString(
            'pdf-only: failed to delete native anonymised intermediate',
            $content,
            'a PII-free warning must be logged when the cleanup delete fails'
        );

    }//end testPdfOnlyBestEffortDeletesNativeIntermediate()


    /**
     * anonymise-output-as-pdf-by-default: AnonymizationService must
     * depend on PdfConversionService and catch ConversionFailedException
     * for the rollback path. Shape-level check; the actual cascade
     * behaviour is covered by PdfConversionServiceTest.
     *
     * @return void
     */
    public function testServiceWiresPdfConversionAndRollback(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        $this->assertStringContainsString(
            'PdfConversionService',
            $content,
            'AnonymizationService must wire PdfConversionService for the pdf-output path'
        );
        $this->assertStringContainsString(
            'ConversionFailedException',
            $content,
            'AnonymizationService must catch ConversionFailedException for the rollback path'
        );
        $this->assertStringContainsString(
            '$result->delete()',
            $content,
            'AnonymizationService must delete the un-converted intermediate when conversion fails'
        );

    }//end testServiceWiresPdfConversionAndRollback()


    /**
     * Test that tryAppendBasisSummary is defined as a private method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testTryAppendBasisSummaryMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString(needle: 'function attachGrondslagenSummary', haystack: $content);

    }//end testTryAppendBasisSummaryMethodExists()

    /**
     * Test the service records a structured warning field on summary failure.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testWarningFieldDefinedOnSummaryFailure(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString(needle: 'grondslagen_summary_failed', haystack: $content);
        $this->assertStringContainsString(needle: "'warning'", haystack: $content);

    }//end testWarningFieldDefinedOnSummaryFailure()

    /**
     * Test that preserve mode path sets summaryFileId and summaryFilePath fields.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testPreserveModeSetsSummaryFields(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('summaryFileId', $content);
        $this->assertStringContainsString('summaryFilePath', $content);
        $this->assertStringContainsString("'preserve'", $content);

    }//end testPreserveModeSetsSummaryFields()

    /**
     * Test #286: source code no longer derives replacementCount from
     * count($mappedEntities). The fabricated-count line must be gone.
     *
     * @return void
     *
     * @spec issue #286 — fabricated replacement count
     */
    public function testReplacementCountIsNoLongerFabricatedFromMappedEntities(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // The exact bug-line from the issue body must be gone.
        $this->assertStringNotContainsString(
            "\$resultInfo['replacementCount'] = count(\$mappedEntities)",
            $content,
            'replacementCount must not be derived from count($mappedEntities) — that is the #286 bug.'
        );

        // And the real-stats fields must now be set on $resultInfo.
        $this->assertStringContainsString("'replacementsAttempted'", $content);
        $this->assertStringContainsString("'replacementsApplied'", $content);
        $this->assertStringContainsString("'replacementsVerified'", $content);
        $this->assertStringContainsString("'unmatchedEntities'", $content);

    }//end testReplacementCountIsNoLongerFabricatedFromMappedEntities()

    /**
     * Test #286: a helper that verifies replacements + a helper that
     * safely reads node text are both present in the implementation.
     *
     * @return void
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    public function testVerificationHelpersExist(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function verifyReplacements', $content);
        $this->assertStringContainsString('function readNodeTextSafely', $content);

    }//end testVerificationHelpersExist()

    /**
     * Test #286 (behavioural): verifyReplacements correctly identifies
     * entities that are present in the source vs those that are not.
     *
     * Uses reflection to invoke the private helper without standing up
     * the full DI graph.
     *
     * @return void
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    public function testVerifyReplacementsDistinguishesAppliedFromUnmatched(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $mappedEntities = [
            ['text' => 'John Doe',     'entityType' => 'PERSON'],
            ['text' => '123456782',    'entityType' => 'BSN'],
            ['text' => 'NotInSource',  'entityType' => 'PERSON'],
            ['text' => '0612345678',   'entityType' => 'PHONE'],
        ];

        $originalText = "Mr. John Doe (BSN 123456782) lives in Amsterdam.\n"
            ."His mobile is 0612345678.";

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'verifyReplacements'
        );
        $reflection->setAccessible(true);
        $result = $reflection->invoke($service, $mappedEntities, $originalText);

        $this->assertSame(4, $result['replacementsAttempted']);
        $this->assertSame(3, $result['replacementsApplied'], 'Only entities present in source count as applied (#286).');
        $this->assertTrue($result['replacementsVerified']);
        $this->assertCount(1, $result['unmatchedEntities']);
        $this->assertSame('NotInSource', $result['unmatchedEntities'][0]['text']);
        $this->assertSame('PERSON', $result['unmatchedEntities'][0]['entityType']);

    }//end testVerifyReplacementsDistinguishesAppliedFromUnmatched()

    /**
     * Test #286: verifyReplacements is case-insensitive (mirrors OR's
     * str_ireplace semantics in DocumentProcessingHandler).
     *
     * @return void
     */
    public function testVerifyReplacementsIsCaseInsensitive(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $mappedEntities = [
            ['text' => 'JOHN DOE', 'entityType' => 'PERSON'],
        ];

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'verifyReplacements'
        );
        $reflection->setAccessible(true);

        $result = $reflection->invoke($service, $mappedEntities, 'Hi john doe.');

        $this->assertSame(1, $result['replacementsApplied']);
        $this->assertCount(0, $result['unmatchedEntities']);

    }//end testVerifyReplacementsIsCaseInsensitive()

    /**
     * Test #286: when source text is null (binary format), verification
     * is reported as impossible — `replacementsApplied` is null and
     * `replacementsVerified` is false. Callers see the truth instead of
     * a fabricated count.
     *
     * @return void
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    public function testVerifyReplacementsReportsUnverifiedForBinaryFormats(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $mappedEntities = [
            ['text' => 'John Doe',  'entityType' => 'PERSON'],
            ['text' => '123456782', 'entityType' => 'BSN'],
        ];

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'verifyReplacements'
        );
        $reflection->setAccessible(true);

        $result = $reflection->invoke($service, $mappedEntities, null);

        $this->assertSame(2, $result['replacementsAttempted']);
        $this->assertNull($result['replacementsApplied'], 'Binary source cannot be verified — applied count must be null.');
        $this->assertFalse($result['replacementsVerified']);
        $this->assertSame([], $result['unmatchedEntities']);

    }//end testVerifyReplacementsReportsUnverifiedForBinaryFormats()

    /**
     * Test #286: readNodeTextSafely returns null for binary mime types
     * (PDF, DOCX, …) so verifyReplacements correctly degrades to
     * "unverified" rather than falsely reporting full success.
     *
     * @return void
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    public function testReadNodeTextSafelyReturnsNullForBinaryMime(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'readNodeTextSafely'
        );
        $reflection->setAccessible(true);

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

        $this->assertNull($reflection->invoke($service, $binaryNode));

    }//end testReadNodeTextSafelyReturnsNullForBinaryMime()

    /**
     * Test #286: readNodeTextSafely returns content for text-like mime
     * types so verification can run.
     *
     * @return void
     */
    public function testReadNodeTextSafelyReturnsContentForTextMime(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'readNodeTextSafely'
        );
        $reflection->setAccessible(true);

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

        $this->assertSame('Hello John Doe.', $reflection->invoke($service, $textNode));

    }//end testReadNodeTextSafelyReturnsContentForTextMime()

    /**
     * Build an AnonymizationService with all constructor deps stubbed so
     * its private helpers can be called via reflection without standing
     * up the full Nextcloud / OpenRegister DI graph.
     *
     * Only the LoggerInterface is exercised (by readNodeTextSafely on
     * error paths) — a no-op NullLogger is sufficient.
     *
     * @return \OCA\DocuDesk\Service\AnonymizationService
     */
    private function buildServiceWithoutDependencies(): \OCA\DocuDesk\Service\AnonymizationService
    {
        $logger          = new \Psr\Log\NullLogger();
        $container       = $this->createMock(\Psr\Container\ContainerInterface::class);
        $appManager      = $this->createMock(\OCP\App\IAppManager::class);
        $entityDetection = $this->createMock(\OCA\DocuDesk\Service\EntityDetectionService::class);
        $appConfig       = $this->createMock(\OCP\IAppConfig::class);
        $consentCrud     = $this->createMock(originalClassName: \OCA\DocuDesk\Service\ConsentCrudService::class);
        $consentService  = $this->createMock(originalClassName: \OCA\DocuDesk\Service\ConsentService::class);

        $grondslagenSummary = $this->createMock(originalClassName: \OCA\DocuDesk\Service\GrondslagenSummaryService::class);
        $fileEntityStats    = $this->createMock(originalClassName: \OCA\DocuDesk\Service\FileEntityStatsService::class);
        $pdfConversion      = $this->createMock(originalClassName: \OCA\DocuDesk\Service\PdfConversionService::class);
        $emlAssembly        = $this->createMock(originalClassName: \OCA\DocuDesk\Service\EmlPdfAssemblyService::class);

        return new \OCA\DocuDesk\Service\AnonymizationService(
            logger: $logger,
            container: $container,
            appManager: $appManager,
            entityDetection: $entityDetection,
            appConfig: $appConfig,
            consentCrud: $consentCrud,
            consentService: $consentService,
            grondslagenSummary: $grondslagenSummary,
            fileEntityStats: $fileEntityStats,
            pdfConversion: $pdfConversion,
            emlAssembly: $emlAssembly,
            customDictionary: $this->makeNoOpCustomDictionaryService(),
            confidentialityLabel: $this->createMock(\OCA\DocuDesk\Service\ConfidentialityLabelService::class),
            customDictionaryDetection: $this->createMock(
                \OCA\DocuDesk\Service\CustomDictionaryDetectionRunner::class
            )
        );

    }//end buildServiceWithoutDependencies()

    /**
     * GIVEN an entity extraction, WHEN extractAndDetectEntities succeeds,
     * THEN the response includes a riskLevel field.
     *
     * Verifies that the riskLevel field is present in the method's return
     * structure (spec requirement: Entity Extraction includes riskLevel).
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization/spec.md
     */
    public function testExtractAndDetectEntitiesIncludesRiskLevelInResponse(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('riskLevel', $content);
        $this->assertStringContainsString("'riskLevel'", $content);
        $this->assertStringContainsString('getFileRiskLevel', $content);
        $this->assertStringContainsString('tryGetRiskLevelService', $content);

    }//end testExtractAndDetectEntitiesIncludesRiskLevelInResponse()

    /**
     * WHEN the FileEntityStatsService is injected, THEN AnonymizationService
     * accepts it in its constructor.
     *
     * @return void
     *
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization/spec.md
     */
    public function testAnonymizationServiceAcceptsFileEntityStatsService(): void
    {
        $service = $this->buildServiceWithoutDependencies();
        $this->assertInstanceOf(\OCA\DocuDesk\Service\AnonymizationService::class, $service);

    }//end testAnonymizationServiceAcceptsFileEntityStatsService()


    /**
     * Build an AnonymizationService whose container resolves the given policy
     * matcher, with all other constructor deps mocked.
     *
     * Uses named arguments so it stays correct regardless of the merged
     * constructor's parameter order (Robert's emlAssembly plus development's
     * appConfig, consent, fileEntityStats and pdfConversion deps are passed).
     *
     * @param PolicyMatchService $matcher The matcher the container returns.
     *
     * @return AnonymizationService The service under test.
     */
    private function makeServiceWithMatcher(PolicyMatchService $matcher, ?EntityRelationMapper $mapper=null): AnonymizationService
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            static function (string $id) use ($matcher, $mapper) {
                return match ($id) {
                    'OCA\DocuDesk\Service\PolicyMatchService'   => $matcher,
                    'OCA\OpenRegister\Db\EntityRelationMapper'  => $mapper,
                    default                                     => null,
                };
            }
        );

        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getInstalledApps')->willReturn(['openregister']);

        return new AnonymizationService(
            logger: $this->createMock(LoggerInterface::class),
            container: $container,
            appManager: $appManager,
            entityDetection: $this->createMock(EntityDetectionService::class),
            appConfig: $this->createMock(\OCP\IAppConfig::class),
            consentCrud: $this->createMock(\OCA\DocuDesk\Service\ConsentCrudService::class),
            consentService: $this->createMock(\OCA\DocuDesk\Service\ConsentService::class),
            grondslagenSummary: $this->createMock(GrondslagenSummaryService::class),
            fileEntityStats: $this->createMock(\OCA\DocuDesk\Service\FileEntityStatsService::class),
            pdfConversion: $this->createMock(PdfConversionService::class),
            emlAssembly: $this->createMock(EmlPdfAssemblyService::class),
            customDictionary: $this->makeNoOpCustomDictionaryService(),
            confidentialityLabel: $this->createMock(\OCA\DocuDesk\Service\ConfidentialityLabelService::class),
            customDictionaryDetection: $this->createMock(
                \OCA\DocuDesk\Service\CustomDictionaryDetectionRunner::class
            )
        );

    }//end makeServiceWithMatcher()

    /**
     * Build a CustomDictionaryService mock that reports no active
     * dictionaries, so the custom-dictionary detection pass is a no-op for
     * tests that are not exercising it directly.
     *
     * @return \OCA\DocuDesk\Service\CustomDictionaryService
     */
    private function makeNoOpCustomDictionaryService(): \OCA\DocuDesk\Service\CustomDictionaryService
    {
        $service = $this->createMock(\OCA\DocuDesk\Service\CustomDictionaryService::class);
        $service->method('listActiveDictionariesForDetection')->willReturn([]);
        return $service;

    }//end makeNoOpCustomDictionaryService()


    /**
     * The pure tier classifier: absolute at/above threshold, releasable-with-
     * force below, allow when forced sub-threshold.
     *
     * @return void
     */
    public function testClassifyProhibitionSkipTiers(): void
    {
        $this->assertSame('block_absolute', AnonymizationService::classifyProhibitionSkip(0.90, 0.85, true));
        $this->assertSame('block_absolute', AnonymizationService::classifyProhibitionSkip(0.85, 0.85, false));
        $this->assertSame('block_releasable', AnonymizationService::classifyProhibitionSkip(0.62, 0.85, false));
        $this->assertSame('allow', AnonymizationService::classifyProhibitionSkip(0.62, 0.85, true));

    }//end testClassifyProhibitionSkipTiers()


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
        $relation = new EntityRelation();

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
            ->with($this->anything(), ['skipAnonymization' => true]);
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
        $mapper   = $this->createMock(EntityRelationMapper::class);
        $mapper->method('find')->with(7)->willReturn($relation);
        $mapper->expects($this->once())->method('updateDecisionMetadata')
            ->with($relation, ['skipAnonymization' => false]);

        $result = $this->makeServiceWithMatcher($this->prohibitionMatcher(), $mapper)
            ->applyRelationSkipDecision(relationId: 7, skip: false, bases: null, force: false);

        $this->assertSame(200, $result['status']);

    }//end testIncludeDecisionIsAlwaysForwarded()


    /**
     * The policy pass flags prohibition matches (with the correct high/low
     * tier), auto-skips standing-consent matches on their relation, and leaves
     * unmatched entities untouched.
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

        $service = $this->makeServiceWithMatcher($matcher);
        $method  = new ReflectionMethod(AnonymizationService::class, 'applyPolicyDecisions');
        $method->setAccessible(true);
        $result  = $method->invoke($service, $entities, $mapper);

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


}//end class
