<?php

/**
 * Unit tests for BasesResolverService
 *
 * Covers the four resolver edge cases defined in the spec:
 *   - Dossier-bound batch  → union of dossier bases
 *   - Orphan batch         → []
 *   - Multi-dossier batch  → union (not intersection), deduplicated
 *   - Empty-dossier-bases  → []
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
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BasesResolverService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BasesResolverService.
 *
 * Uses a mock ObjectService that returns configurable dossier payloads
 * so all four edge cases (dossier-bound, orphan, multi-dossier, empty-bases)
 * can be exercised without hitting a real OpenRegister instance.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
 */
class BasesResolverServiceTest extends TestCase
{

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;

    /**
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $rootFolder;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $userSession;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $appManager;

    /**
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $container;

    /**
     * Set up mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->rootFolder  = $this->createMock(IRootFolder::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->appManager  = $this->createMock(IAppManager::class);
        $this->container   = $this->createMock(ContainerInterface::class);

        $this->appManager->method('getInstalledApps')->willReturn(['openregister']);

    }//end setUp()

    /**
     * Build a resolver with a fake ObjectService that returns given dossier objects.
     *
     * Uses an anonymous class instead of a PHPUnit mock so that named-argument
     * calls (config:, _rbac:) match the declared parameter names exactly.
     *
     * @param array<int, array<string, mixed>> $dossiers Dossier payloads to return from findAll.
     *
     * @return BasesResolverService
     */
    private function buildResolver(array $dossiers=[]): BasesResolverService
    {
        $fakeObjectService = new class ($dossiers) {
            /**
             * @param array<int, array<string, mixed>> $dossiers
             */
            public function __construct(private readonly array $dossiers)
            {
            }//end __construct()

            /**
             * @return array<string, mixed>
             */
            // phpcs:disable CustomSn.Functions.NamedParameters
            public function findAll(array $config=[], bool $_rbac=true): array
            {
                // phpcs:enable
                return ['results' => $this->dossiers];
            }//end findAll()
        };

        $this->container->method('get')->willReturn($fakeObjectService);

        return new BasesResolverService(
            logger: $this->logger,
            rootFolder: $this->rootFolder,
            userSession: $this->userSession,
            appManager: $this->appManager,
            container: $this->container
        );

    }//end buildResolver()

    /**
     * Dossier-bound batch: files all in the same folder, dossier has non-empty bases.
     * Expected: suggestedBases = the dossier's bases array.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
     */
    public function testDossierBoundBatchReturnsDossierBases(): void
    {
        $resolver = $this->buildResolver(
                dossiers: [
                    ['bases' => ['uuid-base-a', 'uuid-base-b']],
                ]
                );

        $batch = [
            'folderId' => 42,
            'files'    => [
                ['fileId' => 1, 'status' => 'extracted'],
            ],
        ];

        $result = $resolver->resolveBasesForBatch(batch: $batch);

        sort($result);
        $this->assertSame(['uuid-base-a', 'uuid-base-b'], $result);

    }//end testDossierBoundBatchReturnsDossierBases()

    /**
     * Orphan batch: no folderId and no user session → empty suggestedBases.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
     */
    public function testOrphanBatchReturnsEmptyBases(): void
    {
        $resolver = $this->buildResolver(dossiers: []);

        $this->userSession->method('getUser')->willReturn(null);

        $batch = [
            'files' => [
                ['fileId' => 1, 'status' => 'extracted'],
            ],
        ];

        $result = $resolver->resolveBasesForBatch(batch: $batch);

        $this->assertSame([], $result);

    }//end testOrphanBatchReturnsEmptyBases()

    /**
     * Multi-dossier batch: two folders → two dossiers with different bases.
     * Expected: union (not intersection), deduplicated.
     *
     * Simulated by using a folder batch with two different folderId calls.
     * Here we test the single-folder path (multi-folder path uses IRootFolder
     * which requires heavier mocking; covered by the integration test).
     * We verify union semantics by configuring the mock to return two dossiers.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
     */
    public function testMultiDossierUnionIsDeduplicatedAndComplete(): void
    {
        // Two dossiers share "A"; dossier 2 also has "B" and "C".
        $resolver = $this->buildResolver(
                dossiers: [
                    ['bases' => ['A']],
                    ['bases' => ['B', 'C', 'A']],
                ]
                );

        $batch = [
            'folderId' => 99,
            'files'    => [],
        ];

        $result = $resolver->resolveBasesForBatch(batch: $batch);
        sort($result);

        // Union = {A, B, C} — deduplicated.
        $this->assertSame(['A', 'B', 'C'], $result);

    }//end testMultiDossierUnionIsDeduplicatedAndComplete()

    /**
     * Empty-dossier-bases batch: dossier exists but bases = [].
     * Expected: suggestedBases = [].
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
     */
    public function testEmptyDossierBasesReturnsEmptyArray(): void
    {
        $resolver = $this->buildResolver(
                dossiers: [
                    ['bases' => []],
                ]
                );

        $batch = [
            'folderId' => 7,
            'files'    => [],
        ];

        $result = $resolver->resolveBasesForBatch(batch: $batch);

        $this->assertSame([], $result);

    }//end testEmptyDossierBasesReturnsEmptyArray()

    /**
     * OpenRegister unavailable: ObjectService cannot be resolved.
     * Expected: suggestedBases = [] (graceful degradation).
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-5
     */
    public function testOpenRegisterUnavailableReturnsEmptyBases(): void
    {
        $this->appManager = $this->createMock(IAppManager::class);
        $this->appManager->method('getInstalledApps')->willReturn([]);

        $resolver = new BasesResolverService(
            logger: $this->logger,
            rootFolder: $this->rootFolder,
            userSession: $this->userSession,
            appManager: $this->appManager,
            container: $this->container
        );

        $batch = [
            'folderId' => 1,
            'files'    => [],
        ];

        $result = $resolver->resolveBasesForBatch(batch: $batch);

        $this->assertSame([], $result);

    }//end testOpenRegisterUnavailableReturnsEmptyBases()
}//end class
