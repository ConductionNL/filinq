<?php

/**
 * Unit tests for GrondslagenSummaryService
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\PdfService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Cover the deterministic helpers of GrondslagenSummaryService.
 *
 * The public methods touch OpenRegister via the container and the
 * Nextcloud filesystem, which are out of scope for unit tests; Newman
 * integration tests in phase 9 cover the end-to-end happy paths. This
 * suite exercises the pure-data helpers (`resolveBaseLabels`)
 * through reflection plus the construction smoke test.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GrondslagenSummaryServiceTest extends TestCase
{

    /**
     * Service under test.
     *
     * @var GrondslagenSummaryService
     */
    private GrondslagenSummaryService $service;

    /**
     * Mock logger.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mock PdfService.
     *
     * @var PdfService|MockObject
     */
    private PdfService|MockObject $mockPdfService;

    /**
     * Mock root folder.
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Mock user session.
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Mock app manager.
     *
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * Mock DI container.
     *
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger      = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockPdfService  = $this->createMock(originalClassName: PdfService::class);
        $this->mockRootFolder  = $this->createMock(originalClassName: IRootFolder::class);
        $this->mockUserSession = $this->createMock(originalClassName: IUserSession::class);
        $this->mockAppManager  = $this->createMock(originalClassName: IAppManager::class);
        $this->mockContainer   = $this->createMock(originalClassName: ContainerInterface::class);

        // OpenRegister not installed — resolveBaseLabels falls back to placeholders.
        $this->mockAppManager->method('getInstalledApps')->willReturn([]);

        $this->service = new GrondslagenSummaryService(
            logger: $this->mockLogger,
            pdfService: $this->mockPdfService,
            rootFolder: $this->mockRootFolder,
            userSession: $this->mockUserSession,
            appManager: $this->mockAppManager,
            container: $this->mockContainer
        );

    }//end setUp()

    /**
     * Service instantiates with all six DI dependencies.
     *
     * @return void
     */
    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            expected: GrondslagenSummaryService::class,
            actual: $this->service
        );

    }//end testServiceCanBeInstantiated()

    /**
     * `resolveBaseLabels` produces a placeholder entry for every input ref.
     *
     * Phase 1 stub behaviour — the dossier register's `base` schema lookup
     * lives in a follow-up (the dossier-side resolution path runs through
     * `getObjectService`). For now every input maps to a "⟨grondslag
     * verwijderd: …⟩" placeholder. This test pins that contract so the
     * follow-up replacement is a clean diff.
     *
     * @return void
     */
    public function testResolveBaseLabelsProducesPlaceholders(): void
    {
        $method = new ReflectionMethod(
            objectOrMethod: GrondslagenSummaryService::class,
            method: 'resolveBaseLabels'
        );
        $method->setAccessible(accessible: true);

        $result = $method->invoke($this->service, ['persoonsgegevens', 'long-uuid-12345']);

        $this->assertCount(expectedCount: 2, haystack: $result);
        $this->assertArrayHasKey(key: 'persoonsgegevens', array: $result);
        $this->assertArrayHasKey(key: 'long-uuid-12345', array: $result);
        // When ObjectService is unavailable each ref gets a placeholder label.
        $this->assertSame(expected: '⟨grondslag verwijderd: persoonsgegevens⟩', actual: $result['persoonsgegevens']);

    }//end testResolveBaseLabelsProducesPlaceholders()

    /**
     * Find a per-basis row by its `ref`. Returns null when missing.
     *
     * @param array<int, array<string, mixed>> $rows Per-basis rows.
     * @param string                           $ref  Basis ref to locate.
     *
     * @return array<string, mixed>|null
     */
    private function findBasisRow(array $rows, string $ref): ?array
    {
        foreach ($rows as $row) {
            if (($row['ref'] ?? null) === $ref) {
                return $row;
            }
        }

        return null;

    }//end findBasisRow()
}//end class
