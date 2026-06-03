<?php

/**
 * Unit tests for PolicyMatchService — prohibition gate focus
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
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\PolicyMatchService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PolicyMatchService (prohibition-gate scope).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
 */
class PolicyMatchServiceTest extends TestCase
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
     * Service under test.
     *
     * @var PolicyMatchService
     */
    private PolicyMatchService $service;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger     = $this->createMock(LoggerInterface::class);
        $this->mockContainer  = $this->createMock(ContainerInterface::class);
        $this->mockAppManager = $this->createMock(IAppManager::class);

        $this->service = new PolicyMatchService(
            logger: $this->mockLogger,
            container: $this->mockContainer,
            appManager: $this->mockAppManager
        );

    }//end setUp()

    /**
     * Helper: build a fake ObjectService that returns the given prohibition records.
     *
     * @param array<int, array<string, mixed>> $prohibitions Prohibition records to return.
     *
     * @return object Fake ObjectService.
     */
    private function makeFakeObjectService(array $prohibitions): object
    {
        return new class($prohibitions) {

            private array $prohibitions;

            public function __construct(array $prohibitions)
            {
                $this->prohibitions = $prohibitions;
            }//end __construct()

            public function findAll(array $config=[], bool $_rbac=true): array
            {
                $filters = $config['filters'] ?? [];
                if (($filters['schema'] ?? '') === 'publicationProhibition') {
                    return ['results' => $this->prohibitions];
                }

                return ['results' => []];
            }//end findAll()
        };

    }//end makeFakeObjectService()

    /**
     * A high-confidence prohibition match is returned with ruleId and ruleName.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionHighConfidenceReturnsMatch(): void
    {
        $prohibitions = [
            [
                '@self'       => ['id' => 'rule-uuid-1'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Beschermde Getuige A',
                'matchRules'  => [['type' => 'exact', 'value' => 'Pieter Jansen']],
            ],
        ];

        $objectService = $this->makeFakeObjectService(prohibitions: $prohibitions);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Pieter Jansen'
        );

        $this->assertNotNull($result);
        $this->assertSame('rule-uuid-1', $result['ruleId']);
        $this->assertSame('Beschermde Getuige A', $result['ruleName']);

    }//end testMatchProhibitionHighConfidenceReturnsMatch()

    /**
     * A low-confidence entity that matches still returns the same match data
     * (confidence handling is in the gate, not in the matcher).
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionLowConfidenceMatchStillReturns(): void
    {
        $prohibitions = [
            [
                '@self'       => ['id' => 'rule-uuid-2'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Getuige B',
                'matchRules'  => [['type' => 'exact', 'value' => 'Jan de Vries']],
            ],
        ];

        $objectService = $this->makeFakeObjectService(prohibitions: $prohibitions);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Jan de Vries'
        );

        // Match is returned; confidence evaluation is the gate's job.
        $this->assertNotNull($result);
        $this->assertSame('rule-uuid-2', $result['ruleId']);

    }//end testMatchProhibitionLowConfidenceMatchStillReturns()

    /**
     * No match when no prohibition records exist.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionNoMatchWhenNoProhibitions(): void
    {
        $objectService = $this->makeFakeObjectService(prohibitions: []);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Onbekende Persoon'
        );

        $this->assertNull($result);

    }//end testMatchProhibitionNoMatchWhenNoProhibitions()

    /**
     * No match when entity text does not match any rule.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionNoMatchWhenEntityTextMisses(): void
    {
        $prohibitions = [
            [
                '@self'       => ['id' => 'rule-uuid-3'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Andere Persoon',
                'matchRules'  => [['type' => 'exact', 'value' => 'Pieter Jansen']],
            ],
        ];

        $objectService = $this->makeFakeObjectService(prohibitions: $prohibitions);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Maria Bakker'
        );

        $this->assertNull($result);

    }//end testMatchProhibitionNoMatchWhenEntityTextMisses()

    /**
     * Multi-rule deterministic precedence: lowest UUID wins.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionDeterministicPrecedenceLexicographicUuid(): void
    {
        $prohibitions = [
            [
                '@self'       => ['id' => 'z-uuid'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Rule Z',
                'matchRules'  => [['type' => 'exact', 'value' => 'Pieter Jansen']],
            ],
            [
                '@self'       => ['id' => 'a-uuid'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Rule A',
                'matchRules'  => [['type' => 'exact', 'value' => 'Pieter Jansen']],
            ],
        ];

        $objectService = $this->makeFakeObjectService(prohibitions: $prohibitions);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Pieter Jansen'
        );

        // Lowest UUID lexicographically ('a-uuid' < 'z-uuid') should win.
        $this->assertNotNull($result);
        $this->assertSame('a-uuid', $result['ruleId']);
        $this->assertSame('Rule A', $result['ruleName']);

    }//end testMatchProhibitionDeterministicPrecedenceLexicographicUuid()

    /**
     * Time-bounded rules outside their validity window are not matched.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionTimeBoundedRuleExpiredIsIgnored(): void
    {
        $prohibitions = [
            [
                '@self'       => ['id' => 'expired-rule'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Expired Rule',
                'matchRules'  => [['type' => 'exact', 'value' => 'Pieter Jansen']],
                'validUntil'  => '2000-01-01T00:00:00+00:00',
            ],
        ];

        $objectService = $this->makeFakeObjectService(prohibitions: $prohibitions);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Pieter Jansen'
        );

        $this->assertNull($result);

    }//end testMatchProhibitionTimeBoundedRuleExpiredIsIgnored()

    /**
     * A future-dated validFrom rule is not yet active and should not match.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testMatchProhibitionFutureValidFromRuleIsIgnored(): void
    {
        $prohibitions = [
            [
                '@self'       => ['id' => 'future-rule'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Future Rule',
                'matchRules'  => [['type' => 'exact', 'value' => 'Pieter Jansen']],
                'validFrom'   => '2099-01-01T00:00:00+00:00',
            ],
        ];

        $objectService = $this->makeFakeObjectService(prohibitions: $prohibitions);
        $this->mockContainer->method('get')->willReturn($objectService);

        $result = $this->service->matchProhibition(
            entityType: 'PERSON',
            entityValue: 'Pieter Jansen'
        );

        $this->assertNull($result);

    }//end testMatchProhibitionFutureValidFromRuleIsIgnored()

    /**
     * Prohibition cache is populated correctly — invalidateCache() forces reload.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-2
     */
    public function testInvalidateCacheCausesRuleReload(): void
    {
        $prohibitionsV1 = [
            [
                '@self'       => ['id' => 'rule-v1'],
                'active'      => true,
                'entityType'  => 'PERSON',
                'primaryName' => 'Rule V1',
                'matchRules'  => [['type' => 'exact', 'value' => 'Alpha']],
            ],
        ];

        $callCount     = 0;
        $objectService = new class($prohibitionsV1, $callCount) {

            public int $callCount = 0;

            private array $prohibitions;

            public function __construct(array $p, int $c)
            {
                $this->prohibitions = $p;
                $this->callCount    = $c;
            }//end __construct()

            public function findAll(array $config=[], bool $_rbac=true): array
            {
                $this->callCount++;
                $filters = $config['filters'] ?? [];
                if (($filters['schema'] ?? '') === 'publicationProhibition') {
                    return ['results' => $this->prohibitions];
                }

                return ['results' => []];
            }//end findAll()
        };

        $this->mockContainer->method('get')->willReturn($objectService);

        // First call — populates cache.
        $this->service->matchProhibition(entityType: 'PERSON', entityValue: 'Alpha');
        $countAfterFirst = $objectService->callCount;

        // Second call — should use cache, no extra findAll.
        $this->service->matchProhibition(entityType: 'PERSON', entityValue: 'Alpha');
        $this->assertSame($countAfterFirst, $objectService->callCount, 'Cache should prevent reload.');

        // After invalidation — reload must happen.
        $this->service->invalidateCache();
        $this->service->matchProhibition(entityType: 'PERSON', entityValue: 'Alpha');
        $this->assertGreaterThan($countAfterFirst, $objectService->callCount, 'Post-invalidation reload expected.');

    }//end testInvalidateCacheCausesRuleReload()
}//end class
