<?php

/**
 * Unit tests for AnonymizationService — prohibition gate
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Exception\ProhibitionGateException;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\FileEntityStatsService;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the prohibition gate logic inside AnonymizationService.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
 */
class AnonymizationServiceGateTest extends TestCase
{

    /**
     * Mock logger.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mock DI container.
     *
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * Mock IAppManager.
     *
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * Mock EntityDetectionService.
     *
     * @var EntityDetectionService|MockObject
     */
    private EntityDetectionService|MockObject $mockEntityDetection;

    /**
     * Mock IAppConfig.
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * Mock PolicyMatchService.
     *
     * @var PolicyMatchService|MockObject
     */
    private PolicyMatchService|MockObject $mockPolicyMatch;

    /**
     * Service under test.
     *
     * @var AnonymizationService
     */
    private AnonymizationService $service;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger          = $this->createMock(LoggerInterface::class);
        $this->mockContainer       = $this->createMock(ContainerInterface::class);
        $this->mockAppManager      = $this->createMock(IAppManager::class);
        $this->mockEntityDetection = $this->createMock(EntityDetectionService::class);
        $this->mockAppConfig       = $this->createMock(IAppConfig::class);
        $this->mockPolicyMatch     = $this->createMock(PolicyMatchService::class);

        // Make openregister appear installed so getOpenRegisterService() resolves.
        $this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

        // Default: threshold is 0.85.
        $this->mockAppConfig->method('getValueFloat')
            ->willReturn(0.85);

        $this->service = new AnonymizationService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            entityDetection: $this->mockEntityDetection,
            appConfig: $this->mockAppConfig,
            consentCrud: $this->createMock(ConsentCrudService::class),
            consentService: $this->createMock(ConsentService::class),
            grondslagenSummary: $this->createMock(GrondslagenSummaryService::class),
            fileEntityStats: $this->createMock(FileEntityStatsService::class),
            pdfConversion: $this->createMock(PdfConversionService::class)
        );

    }//end setUp()

    /**
     * Build a fake EntityRelationMapper that returns the given raw entities.
     *
     * @param array<int, array<string, mixed>> $rawEntities Entities to return from findEntitiesForFile.
     *
     * @return object Fake EntityRelationMapper.
     */
    private function makeFakeEntityRelationMapper(array $rawEntities): object
    {
        return new class($rawEntities) {

            private array $rawEntities;

            public function __construct(array $rawEntities)
            {
                $this->rawEntities = $rawEntities;
            }//end __construct()

            public function findEntitiesForFile(int $fileId): array
            {
                return $this->rawEntities;
            }//end findEntitiesForFile()

            public function updateDecisionMetadata(int $relationId, array $metadata): void
            {
            }//end updateDecisionMetadata()
        };

    }//end makeFakeEntityRelationMapper()

    /**
     * Build a fake ObjectService for audit entries.
     *
     * @return object Fake ObjectService with a tracked saveObject call count.
     */
    private function makeFakeObjectService(): object
    {
        return new class() {

            public int $saveObjectCallCount = 0;

            public array $lastSavedObject = [];

            public function saveObject(array $object=[], string $register='', string $schema=''): array
            {
                $this->saveObjectCallCount++;
                $this->lastSavedObject = $object;
                return $object;
            }//end saveObject()

            public function find(string $id='', string $register='', string $schema=''): mixed
            {
                return null;
            }//end find()
        };

    }//end makeFakeObjectService()

    /**
     * Gate is a no-op when PolicyMatchService returns no matches.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testGatePassesWhenNoProhibitionMatchesFound(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Maria Bakker',
                'confidence'  => 0.95,
                'entityId'    => 1,
                'relationId'  => 10,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(null);

        // Gate should pass silently — no exception thrown.
        $this->service->runProhibitionGate(
            fileId: 1,
            requestEntities: [['value' => 'Maria Bakker', 'type' => 'PERSON']],
            acknowledgedOverrides: [],
            userId: 'user1'
        );

        // If we reach here without an exception, the gate passed.
        $this->assertTrue(true);

    }//end testGatePassesWhenNoProhibitionMatchesFound()

    /**
     * Gate fires 422 when a high-confidence prohibition match is missing from entities[].
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testGateFires422WhenHighConfidenceMatchMissingFromEntities(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Pieter Jansen',
                'confidence'  => 0.97,
                'entityId'    => 42,
                'relationId'  => 100,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-R-PROHIBIT-1',
                    'ruleName' => 'Beschermde Getuige A',
                ]
                );

        $this->expectException(ProhibitionGateException::class);

        $this->service->runProhibitionGate(
            fileId: 1,
            // Entities list does NOT include 'Pieter Jansen'.
            requestEntities: [['value' => 'Other Entity', 'type' => 'LOCATION']],
            acknowledgedOverrides: [],
            userId: 'user1'
        );

    }//end testGateFires422WhenHighConfidenceMatchMissingFromEntities()

    /**
     * Gate passes when all high-confidence prohibition matches are in entities[].
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testGatePassesWhenHighConfidenceMatchPresentInEntities(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Pieter Jansen',
                'confidence'  => 0.97,
                'entityId'    => 42,
                'relationId'  => 100,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-R-PROHIBIT-1',
                    'ruleName' => 'Beschermde Getuige A',
                ]
                );

        // Gate should pass — 'Pieter Jansen' is in entities[].
        $this->service->runProhibitionGate(
            fileId: 1,
            requestEntities: [['value' => 'Pieter Jansen', 'type' => 'PERSON']],
            acknowledgedOverrides: [],
            userId: 'user1'
        );

        $this->assertTrue(true);

    }//end testGatePassesWhenHighConfidenceMatchPresentInEntities()

    /**
     * Gate ignores low-confidence matches by default (no override needed).
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testGateIgnoresLowConfidenceMatchByDefault(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Jan de Vries',
                'confidence'  => 0.60,
                'entityId'    => 7,
                'relationId'  => 50,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-R-X',
                    'ruleName' => 'Low Confidence Rule',
                ]
                );

        // Low-confidence (0.60 < 0.85): gate passes even without an override.
        $this->service->runProhibitionGate(
            fileId: 1,
            requestEntities: [],
            acknowledgedOverrides: [],
            userId: 'user1'
        );

        $this->assertTrue(true);

    }//end testGateIgnoresLowConfidenceMatchByDefault()

    /**
     * Override releases a low-confidence match.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testOverrideReleasesLowConfidenceMatch(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Jan de Vries',
                'confidence'  => 0.62,
                'entityId'    => 7,
                'relationId'  => 50,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-R-X',
                    'ruleName' => 'Low Confidence Rule',
                ]
                );

        // Override for the low-confidence match — gate should pass.
        $this->service->runProhibitionGate(
            fileId: 1,
            requestEntities: [],
            acknowledgedOverrides: [
                ['ruleId' => 'rule-R-X', 'entityId' => 7, 'reason' => 'false positive'],
            ],
            userId: 'user1'
        );

        $this->assertTrue(true);

    }//end testOverrideReleasesLowConfidenceMatch()

    /**
     * Override for a high-confidence match is rejected with 422.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testOverrideForHighConfidenceMatchIsRejected(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Pieter Jansen',
                'confidence'  => 0.94,
                'entityId'    => 7,
                'relationId'  => 100,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-R-X',
                    'ruleName' => 'High Confidence Rule',
                ]
                );

        $this->expectException(ProhibitionGateException::class);

        // Override for high-confidence (0.94 >= 0.85): rejected.
        $this->service->runProhibitionGate(
            fileId: 1,
            requestEntities: [],
            acknowledgedOverrides: [
                ['ruleId' => 'rule-R-X', 'entityId' => 7],
            ],
            userId: 'user1'
        );

    }//end testOverrideForHighConfidenceMatchIsRejected()

    /**
     * The 422 exception carries the correct missing prohibition match data.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testExceptionCarriesMissingProhibitionMatchData(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Pieter Jansen',
                'confidence'  => 0.91,
                'entityId'    => 42,
                'relationId'  => 100,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'R-PROHIBIT-1',
                    'ruleName' => 'Politiemedewerker undercover (Jansen)',
                ]
                );

        try {
            $this->service->runProhibitionGate(
                fileId: 1,
                requestEntities: [['value' => 'Other', 'type' => 'PERSON']],
                acknowledgedOverrides: [],
                userId: 'user1'
            );
            $this->fail('Expected ProhibitionGateException was not thrown.');
        } catch (ProhibitionGateException $e) {
            $missing = $e->getMissingProhibitionMatches();
            $this->assertCount(1, $missing);
            $this->assertSame(42, $missing[0]['entityId']);
            $this->assertSame('R-PROHIBIT-1', $missing[0]['ruleId']);
            $this->assertSame('Politiemedewerker undercover (Jansen)', $missing[0]['ruleName']);
            $this->assertEqualsWithDelta(0.91, $missing[0]['confidence'], 0.001);
        }

    }//end testExceptionCarriesMissingProhibitionMatchData()

    /**
     * Gate fires with all missing matches when multiple are absent.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testGateListsAllMissingHighConfidenceMatches(): void
    {
        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Entity A',
                'confidence'  => 0.96,
                'entityId'    => 1,
                'relationId'  => 10,
            ],
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Entity B',
                'confidence'  => 0.91,
                'entityId'    => 2,
                'relationId'  => 20,
            ],
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Entity C',
                'confidence'  => 0.88,
                'entityId'    => 3,
                'relationId'  => 30,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-ALL',
                    'ruleName' => 'All Match Rule',
                ]
                );

        try {
            $this->service->runProhibitionGate(
                fileId: 1,
                // Only Entity A is in the set — B and C are missing.
                requestEntities: [['value' => 'Entity A', 'type' => 'PERSON']],
                acknowledgedOverrides: [],
                userId: 'user1'
            );
            $this->fail('Expected ProhibitionGateException was not thrown.');
        } catch (ProhibitionGateException $e) {
            $missing = $e->getMissingProhibitionMatches();
            // Entity B and Entity C are missing; Entity A is present.
            $this->assertCount(2, $missing);
            $missingEntityIds = array_column($missing, 'entityId');
            $this->assertContains(2, $missingEntityIds);
            $this->assertContains(3, $missingEntityIds);
            $this->assertNotContains(1, $missingEntityIds);
        }

    }//end testGateListsAllMissingHighConfidenceMatches()

    /**
     * Configurable threshold at 0.90 treats a 0.87 match as low-confidence.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-10
     */
    public function testCustomThresholdTreatsMatchAsBelowThreshold(): void
    {
        // Override threshold to 0.90.
        $mockAppConfig = $this->createMock(IAppConfig::class);
        $mockAppConfig->method('getValueFloat')->willReturn(0.90);

        $mockAppManagerLocal = $this->createMock(IAppManager::class);
        $mockAppManagerLocal->method('getInstalledApps')->willReturn(['openregister']);

        $service = new AnonymizationService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $mockAppManagerLocal,
            entityDetection: $this->mockEntityDetection,
            appConfig: $mockAppConfig,
            consentCrud: $this->createMock(ConsentCrudService::class),
            consentService: $this->createMock(ConsentService::class),
            grondslagenSummary: $this->createMock(GrondslagenSummaryService::class),
            fileEntityStats: $this->createMock(FileEntityStatsService::class),
            pdfConversion: $this->createMock(PdfConversionService::class)
        );

        $rawEntities = [
            [
                'entityType'  => 'PERSON',
                'entityValue' => 'Pieter Jansen',
                'confidence'  => 0.87,
                'entityId'    => 42,
                'relationId'  => 100,
            ],
        ];

        $fakeMapper = $this->makeFakeEntityRelationMapper(rawEntities: $rawEntities);
        $fakeOs     = $this->makeFakeObjectService();

        $this->mockContainer->method('get')->willReturnCallback(
            function (string $class) use ($fakeMapper, $fakeOs) {
                if (str_ends_with($class, 'EntityRelationMapper')) {
                    return $fakeMapper;
                }

                if (str_ends_with($class, 'PolicyMatchService')) {
                    return $this->mockPolicyMatch;
                }

                return $fakeOs;
            }
        );

        $this->mockPolicyMatch->method('matchProhibition')->willReturn(
                [
                    'ruleId'   => 'rule-R-X',
                    'ruleName' => 'Some Rule',
                ]
                );

        // At threshold 0.90, confidence 0.87 is below threshold → gate passes.
        $service->runProhibitionGate(
            fileId: 1,
            requestEntities: [],
            acknowledgedOverrides: [],
            userId: 'user1'
        );

        $this->assertTrue(true);

    }//end testCustomThresholdTreatsMatchAsBelowThreshold()
}//end class
