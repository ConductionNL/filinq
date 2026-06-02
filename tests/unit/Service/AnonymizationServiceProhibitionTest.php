<?php

/**
 * Unit tests for AnonymizationService prohibition-match behaviour
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\OcrService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for AnonymizationService's prohibition-match attachment
 *
 * Uses a partial-mock strategy: container returns a fake PolicyMatchService
 * so the private attachProhibitionMatches / computeProhibitionMatch logic
 * is exercised via extractAndDetectEntities().
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceProhibitionTest extends TestCase
{

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * @var EntityDetectionService
     */
    private EntityDetectionService $entityDetection;

    /**
     * @var OcrService|MockObject
     */
    private OcrService|MockObject $mockOcrService;

    /**
     * @var GrondslagenSummaryService|MockObject
     */
    private GrondslagenSummaryService|MockObject $mockGrondslagenSummary;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger             = $this->createMock(LoggerInterface::class);
        $this->mockContainer          = $this->createMock(ContainerInterface::class);
        $this->mockAppManager         = $this->createMock(IAppManager::class);
        $this->mockAppConfig          = $this->createMock(IAppConfig::class);
        $this->mockOcrService         = $this->createMock(OcrService::class);
        $this->mockGrondslagenSummary = $this->createMock(GrondslagenSummaryService::class);

        $this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

        $this->entityDetection = new EntityDetectionService(
            new AnonymizationResultParser()
        );

    }//end setUp()


    /**
     * Build the service under test
     *
     * @return AnonymizationService
     */
    private function makeService(): AnonymizationService
    {
        $this->mockOcrService->method('processFile')
            ->willReturn([
                'text'         => '',
                'confidence'   => 0.0,
                'ocrProcessed' => false,
            ]);

        return new AnonymizationService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            entityDetection: $this->entityDetection,
            appConfig: $this->mockAppConfig,
            ocrService: $this->mockOcrService,
            grondslagenSummary: $this->mockGrondslagenSummary
        );

    }//end makeService()


    /**
     * Stub the container to return mock OR services + an optional PolicyMatchService
     *
     * @param mixed $policyService PolicyMatchService mock or null (not registered)
     * @param array $rawEntities   Entities returned by EntityRelationMapper
     *
     * @return void
     */
    private function stubContainerForExtract(mixed $policyService, array $rawEntities): void
    {
        $mockExtractor = $this->createMock(\OCA\OpenRegister\Service\TextExtractionService::class);
        $mockMapper    = $this->createMock(\OCA\OpenRegister\Db\EntityRelationMapper::class);
        $mockMapper->method('findEntitiesForFile')->willReturn($rawEntities);

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($mockExtractor, $mockMapper, $policyService) {
                if ($class === 'OCA\OpenRegister\Service\TextExtractionService') {
                    return $mockExtractor;
                }

                if ($class === 'OCA\OpenRegister\Db\EntityRelationMapper') {
                    return $mockMapper;
                }

                if ($class === 'OCA\DocuDesk\Service\PolicyMatchService') {
                    if ($policyService === null) {
                        throw new \Exception('PolicyMatchService not registered');
                    }

                    return $policyService;
                }

                throw new \Exception("Unknown service: $class");
            }
        );

    }//end stubContainerForExtract()


    /**
     * When PolicyMatchService is not available, all prohibitionMatch fields are null
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
     */
    public function testProhibitionMatchIsNullWhenServiceUnavailable(): void
    {
        $rawEntities = [
            ['entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen', 'confidence' => 0.95],
        ];

        $this->stubContainerForExtract(policyService: null, rawEntities: $rawEntities);
        $this->mockAppConfig->method('getValueFloat')->willReturn(0.85);

        $service = $this->makeService();
        $result  = $service->extractAndDetectEntities(fileId: 1);

        $this->assertCount(1, $result['entities']);
        $this->assertNull($result['entities'][0]['prohibitionMatch']);

    }//end testProhibitionMatchIsNullWhenServiceUnavailable()


    /**
     * When a prohibition rule matches and confidence is above threshold, highConfidence is true
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
     */
    public function testProhibitionMatchHighConfidenceWhenAboveThreshold(): void
    {
        $mockPolicyService = $this->createMock(FakePolicyMatchService::class);
        $mockPolicyService->method('matchProhibition')->willReturn(
            ['ruleId' => 'R-X', 'ruleName' => 'Beschermde Getuige A']
        );

        $rawEntities = [
            ['entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen', 'confidence' => 0.96],
        ];

        $this->stubContainerForExtract(policyService: $mockPolicyService, rawEntities: $rawEntities);
        $this->mockAppConfig->method('getValueFloat')->willReturn(0.85);

        $service = $this->makeService();
        $result  = $service->extractAndDetectEntities(fileId: 1);

        $match = $result['entities'][0]['prohibitionMatch'];
        $this->assertNotNull($match);
        $this->assertSame('R-X', $match['ruleId']);
        $this->assertSame('Beschermde Getuige A', $match['ruleName']);
        $this->assertTrue($match['highConfidence']);

    }//end testProhibitionMatchHighConfidenceWhenAboveThreshold()


    /**
     * When confidence is below threshold, highConfidence is false
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
     */
    public function testProhibitionMatchLowConfidenceWhenBelowThreshold(): void
    {
        $mockPolicyService = $this->createMock(FakePolicyMatchService::class);
        $mockPolicyService->method('matchProhibition')->willReturn(
            ['ruleId' => 'R-Y', 'ruleName' => 'Rule Y']
        );

        $rawEntities = [
            ['entity_type' => 'PERSON', 'entity_value' => 'Jane Doe', 'confidence' => 0.62],
        ];

        $this->stubContainerForExtract(policyService: $mockPolicyService, rawEntities: $rawEntities);
        $this->mockAppConfig->method('getValueFloat')->willReturn(0.85);

        $service = $this->makeService();
        $result  = $service->extractAndDetectEntities(fileId: 1);

        $match = $result['entities'][0]['prohibitionMatch'];
        $this->assertNotNull($match);
        $this->assertFalse($match['highConfidence']);

    }//end testProhibitionMatchLowConfidenceWhenBelowThreshold()


    /**
     * Threshold is inclusive: confidence exactly at threshold → highConfidence true
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
     */
    public function testProhibitionMatchThresholdIsInclusive(): void
    {
        $mockPolicyService = $this->createMock(FakePolicyMatchService::class);
        $mockPolicyService->method('matchProhibition')->willReturn(
            ['ruleId' => 'R-Z', 'ruleName' => 'Rule Z']
        );

        $rawEntities = [
            ['entity_type' => 'PERSON', 'entity_value' => 'Exact Threshold', 'confidence' => 0.85],
        ];

        $this->stubContainerForExtract(policyService: $mockPolicyService, rawEntities: $rawEntities);
        $this->mockAppConfig->method('getValueFloat')->willReturn(0.85);

        $service = $this->makeService();
        $result  = $service->extractAndDetectEntities(fileId: 1);

        $match = $result['entities'][0]['prohibitionMatch'];
        $this->assertNotNull($match);
        $this->assertTrue($match['highConfidence']);

    }//end testProhibitionMatchThresholdIsInclusive()


    /**
     * When no rule matches, prohibitionMatch is null even when service is available
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-8
     */
    public function testProhibitionMatchNullWhenNoRuleMatches(): void
    {
        $mockPolicyService = $this->createMock(FakePolicyMatchService::class);
        $mockPolicyService->method('matchProhibition')->willReturn(null);

        $rawEntities = [
            ['entity_type' => 'LOCATION', 'entity_value' => 'Amsterdam', 'confidence' => 0.99],
        ];

        $this->stubContainerForExtract(policyService: $mockPolicyService, rawEntities: $rawEntities);
        $this->mockAppConfig->method('getValueFloat')->willReturn(0.85);

        $service = $this->makeService();
        $result  = $service->extractAndDetectEntities(fileId: 1);

        $this->assertNull($result['entities'][0]['prohibitionMatch']);

    }//end testProhibitionMatchNullWhenNoRuleMatches()


    /**
     * extractAndDetectEntities reads high_confidence_threshold from IAppConfig
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
     */
    public function testThresholdReadFromAppConfig(): void
    {
        $mockPolicyService = $this->createMock(FakePolicyMatchService::class);
        $mockPolicyService->method('matchProhibition')->willReturn(
            ['ruleId' => 'R-1', 'ruleName' => 'Rule 1']
        );

        $rawEntities = [
            ['entity_type' => 'PERSON', 'entity_value' => 'Test Person', 'confidence' => 0.70],
        ];

        $this->stubContainerForExtract(policyService: $mockPolicyService, rawEntities: $rawEntities);

        // Use a custom threshold of 0.60 — confidence 0.70 should be high-confidence.
        $this->mockAppConfig->expects($this->atLeastOnce())
            ->method('getValueFloat')
            ->with('docudesk', 'prohibition.high_confidence_threshold', 0.85)
            ->willReturn(0.60);

        $service = $this->makeService();
        $result  = $service->extractAndDetectEntities(fileId: 1);

        $this->assertTrue($result['entities'][0]['prohibitionMatch']['highConfidence']);

    }//end testThresholdReadFromAppConfig()


}//end class


/**
 * Fake PolicyMatchService interface stub for mocking
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 */
class FakePolicyMatchService
{


    /**
     * Match a prohibition rule for the given entity
     *
     * @param string $entityType  The entity type
     * @param string $entityValue The entity value
     *
     * @return array<string, string>|null Match or null
     */
    public function matchProhibition(string $entityType, string $entityValue): ?array
    {
        return null;

    }//end matchProhibition()


}//end class
