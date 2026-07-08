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
        $this->assertStringContainsString('extractFile($fileId, true, $entityTypes)', $content);

    }//end testExtractAndDetectPassesEntityTypeWhitelist()


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
     * Build an AnonymizationService whose container resolves the given policy
     * matcher, with all other constructor deps mocked.
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
            $this->createMock(LoggerInterface::class),
            $container,
            $appManager,
            $this->createMock(EntityDetectionService::class),
            $this->createMock(GrondslagenSummaryService::class),
            $this->createMock(PdfConversionService::class),
            $this->createMock(EmlPdfAssemblyService::class)
        );

    }//end makeServiceWithMatcher()


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
