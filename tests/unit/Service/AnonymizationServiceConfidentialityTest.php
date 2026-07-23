<?php

/**
 * Unit tests for AnonymizationService's confidentiality-signal merge
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationResultParser;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\ConfidentialityLabel;
use OCA\DocuDesk\Service\ConfidentialityLabelService;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\CustomDictionaryMatchService;
use OCA\DocuDesk\Service\CustomDictionaryService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests that `extractAndDetectEntities()` merges the confidentiality signal
 * additively: present when ConfidentialityLabelService resolves a label,
 * omitted otherwise (files-confidential-labels, design.md D2).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceConfidentialityTest extends TestCase
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
     * @var ConfidentialityLabelService|MockObject
     */
    private ConfidentialityLabelService|MockObject $mockConfidentialityLabel;

    /**
     * @var EntityDetectionService
     */
    private EntityDetectionService $entityDetection;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger               = $this->createMock(LoggerInterface::class);
        $this->mockContainer            = $this->createMock(ContainerInterface::class);
        $this->mockAppManager           = $this->createMock(IAppManager::class);
        $this->mockAppConfig            = $this->createMock(IAppConfig::class);
        $this->mockConfidentialityLabel = $this->createMock(ConfidentialityLabelService::class);

        $this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

        $this->entityDetection = new EntityDetectionService(new AnonymizationResultParser());

        $mockExtractor = $this->createMock(\OCA\OpenRegister\Service\TextExtractionService::class);
        $mockMapper    = $this->createMock(\OCA\OpenRegister\Db\EntityRelationMapper::class);
        $mockMapper->method('findEntitiesForFile')->willReturn(
            [['entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen', 'confidence' => 0.95]]
        );

        $mockGrondslag = $this->createMock(\OCA\DocuDesk\Service\GrondslagProposalService::class);
        $mockGrondslag->method('getEntityTypeWhitelist')->willReturn(null);
        $mockGrondslag->method('enrichEntitiesWithBases')->willReturnArgument(0);

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($mockExtractor, $mockMapper, $mockGrondslag) {
                return match ($class) {
                    'OCA\OpenRegister\Service\TextExtractionService' => $mockExtractor,
                    'OCA\OpenRegister\Db\EntityRelationMapper'       => $mockMapper,
                    'OCA\DocuDesk\Service\GrondslagProposalService'  => $mockGrondslag,
                    'OCA\DocuDesk\Service\PolicyMatchService'        => throw new \Exception('PolicyMatchService not registered'),
                    default                                          => throw new \Exception("Unknown service: $class"),
                };
            }
        );

    }//end setUp()

    /**
     * Build the service under test.
     *
     * @return AnonymizationService
     */
    private function makeService(): AnonymizationService
    {
        $customDictionary = $this->createMock(CustomDictionaryService::class);
        $customDictionary->method('listActiveDictionariesForDetection')->willReturn([]);

        return new AnonymizationService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            entityDetection: $this->entityDetection,
            appConfig: $this->mockAppConfig,
            consentCrud: $this->createMock(ConsentCrudService::class),
            consentService: $this->createMock(ConsentService::class),
            grondslagenSummary: $this->createMock(GrondslagenSummaryService::class),
            fileEntityStats: $this->createMock(\OCA\DocuDesk\Service\FileEntityStatsService::class),
            pdfConversion: $this->createMock(\OCA\DocuDesk\Service\PdfConversionService::class),
            emlAssembly: $this->createMock(\OCA\DocuDesk\Service\EmlPdfAssemblyService::class),
            customDictionary: $customDictionary,
            customDictionaryMatch: new CustomDictionaryMatchService(),
            confidentialityLabel: $this->mockConfidentialityLabel
        );

    }//end makeService()

    /**
     * WHEN ConfidentialityLabelService resolves a label, THEN the result
     * carries `confidentialityLabel`/`confidentialityLevel` alongside
     * entities and risk.
     *
     * @return void
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-review-context-shows-the-confidentiality-chip
     */
    public function testResultIncludesConfidentialityFieldsWhenLabelResolves(): void
    {
        $this->mockConfidentialityLabel->method('getLabelForFile')
            ->with(1)
            ->willReturn(new ConfidentialityLabel('Confidential', 2));

        $result = $this->makeService()->extractAndDetectEntities(fileId: 1);

        $this->assertArrayHasKey('confidentialityLabel', $result);
        $this->assertArrayHasKey('confidentialityLevel', $result);
        $this->assertSame('Confidential', $result['confidentialityLabel']);
        $this->assertSame(2, $result['confidentialityLevel']);
        // Additive: existing fields stay present and unaffected.
        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('riskLevel', $result);

    }//end testResultIncludesConfidentialityFieldsWhenLabelResolves()

    /**
     * WHEN ConfidentialityLabelService resolves no label (files_confidential
     * absent, file untagged, or no vocabulary match), THEN both
     * confidentiality fields are omitted from the result.
     *
     * @return void
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-unlabelled-file-shows-no-chip-and-no-fields
     */
    public function testResultOmitsConfidentialityFieldsWhenNoLabelResolves(): void
    {
        $this->mockConfidentialityLabel->method('getLabelForFile')->willReturn(null);

        $result = $this->makeService()->extractAndDetectEntities(fileId: 1);

        $this->assertArrayNotHasKey('confidentialityLabel', $result);
        $this->assertArrayNotHasKey('confidentialityLevel', $result);
        $this->assertArrayHasKey('entities', $result);

    }//end testResultOmitsConfidentialityFieldsWhenNoLabelResolves()
}//end class
