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
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-9
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\PdfService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for GrondslagenSummaryService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GrondslagenSummaryServiceTest extends TestCase
{

    /**
     * The service under test.
     *
     * @var GrondslagenSummaryService
     */
    private GrondslagenSummaryService $service;

    /**
     * Mocked PdfService.
     *
     * @var PdfService|MockObject
     */
    private PdfService|MockObject $mockPdfService;

    /**
     * Mocked DI container.
     *
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * Mocked IAppManager.
     *
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * Mocked logger.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked IRootFolder.
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Mocked IUserSession.
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPdfService  = $this->createMock(originalClassName: PdfService::class);
        $this->mockContainer   = $this->createMock(originalClassName: ContainerInterface::class);
        $this->mockAppManager  = $this->createMock(originalClassName: IAppManager::class);
        $this->mockLogger      = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockRootFolder  = $this->createMock(originalClassName: IRootFolder::class);
        $this->mockUserSession = $this->createMock(originalClassName: IUserSession::class);

        $this->service = new GrondslagenSummaryService(
            pdfService: $this->mockPdfService,
            container: $this->mockContainer,
            appManager: $this->mockAppManager,
            logger: $this->mockLogger,
            rootFolder: $this->mockRootFolder,
            userSession: $this->mockUserSession
        );

    }//end setUp()

    /**
     * Test resolveBaseLabels returns placeholder for null input
     *
     * @return void
     */
    public function testResolveBaseLabelsNullReturnsPlaceholder(): void
    {
        $result = $this->service->resolveBaseLabels(baseUuids: null);

        $this->assertCount(expectedCount: 1, haystack: $result);
        $this->assertStringContainsString(needle: 'geen grondslag vastgelegd', haystack: $result[0]);

    }//end testResolveBaseLabelsNullReturnsPlaceholder()

    /**
     * Test resolveBaseLabels returns placeholder for empty array
     *
     * @return void
     */
    public function testResolveBaseLabelsEmptyArrayReturnsPlaceholder(): void
    {
        $result = $this->service->resolveBaseLabels(baseUuids: []);

        $this->assertCount(expectedCount: 1, haystack: $result);
        $this->assertStringContainsString(needle: 'geen grondslag vastgelegd', haystack: $result[0]);

    }//end testResolveBaseLabelsEmptyArrayReturnsPlaceholder()

    /**
     * Test resolveBaseLabels resolves UUID to name via ObjectService
     *
     * @return void
     */
    public function testResolveBaseLabelsResolvesUuid(): void
    {
        $this->mockAppManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $mockBase = $this->createMock(originalClassName: \OCA\OpenRegister\Db\ObjectEntity::class);
        $mockBase->method('getObject')->willReturn(['name' => 'persoonsgegevens']);

        $mockObjectService = $this->createMock(originalClassName: \OCA\OpenRegister\Service\ObjectService::class);
        $mockObjectService->method('find')->willReturn($mockBase);

        $this->mockContainer->method('get')
            ->willReturnMap([['OCA\OpenRegister\Service\ObjectService', $mockObjectService]]);

        $result = $this->service->resolveBaseLabels(baseUuids: ['uuid-persoonsgegevens']);

        $this->assertCount(expectedCount: 1, haystack: $result);
        $this->assertSame(expected: 'persoonsgegevens', actual: $result[0]);

    }//end testResolveBaseLabelsResolvesUuid()

    /**
     * Test resolveBaseLabels returns unresolved placeholder when UUID not found
     *
     * @return void
     */
    public function testResolveBaseLabelsUnresolvableUuidUsesPlaceholder(): void
    {
        $this->mockAppManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $mockObjectService = $this->createMock(originalClassName: \OCA\OpenRegister\Service\ObjectService::class);
        $mockObjectService->method('find')->willReturn(null);

        $this->mockContainer->method('get')
            ->willReturnMap([['OCA\OpenRegister\Service\ObjectService', $mockObjectService]]);

        $this->mockLogger->expects($this->atLeastOnce())->method('warning');

        $result = $this->service->resolveBaseLabels(baseUuids: ['uuid-deleted-base-aaaa']);

        $this->assertCount(expectedCount: 1, haystack: $result);
        $this->assertStringContainsString(needle: 'grondslag verwijderd', haystack: $result[0]);
        $this->assertStringContainsString(needle: 'uuid-del', haystack: $result[0]);

    }//end testResolveBaseLabelsUnresolvableUuidUsesPlaceholder()

    /**
     * Test loadAnonymisedEntitiesForFile filters for anonymized = true
     *
     * @return void
     */
    public function testLoadAnonymisedEntitiesForFileFiltersAnonymized(): void
    {
        $this->mockAppManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $mockRelationNotAnonymized = $this->createMockEntityRelation(
            entityId: 1,
            anonymized: false,
            anonymizedValue: ''
        );
        $mockRelationAnonymized    = $this->createMockEntityRelation(
            entityId: 2,
            anonymized: true,
            anonymizedValue: '[PERSOON]'
        );

        $mockMapper = $this->createMock(originalClassName: \OCA\OpenRegister\Db\EntityRelationMapper::class);
        $mockMapper->method('findByFileId')
            ->willReturn([$mockRelationNotAnonymized, $mockRelationAnonymized]);
        $mockMapper->method('findEntitiesForFile')
            ->willReturn(
                    [
                        ['entity_id' => 1, 'entity_type' => 'PERSON', 'entity_value' => 'Jan Janssen'],
                        ['entity_id' => 2, 'entity_type' => 'PERSON', 'entity_value' => 'Piet Pietersen'],
                    ]
                    );

        $this->mockContainer->method('get')
            ->willReturnMap([['OCA\OpenRegister\Db\EntityRelationMapper', $mockMapper]]);

        $result = $this->service->loadAnonymisedEntitiesForFile(fileId: 42);

        $this->assertCount(expectedCount: 1, haystack: $result);
        $this->assertSame(expected: 'Piet Pietersen', actual: $result[0]['entityText']);
        $this->assertSame(expected: '[PERSOON]', actual: $result[0]['anonymizedValue']);

    }//end testLoadAnonymisedEntitiesForFileFiltersAnonymized()

    /**
     * Test loadAnonymisedEntitiesForFile returns empty array when no entities
     *
     * @return void
     */
    public function testLoadAnonymisedEntitiesForFileEmptyResult(): void
    {
        $this->mockAppManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $mockMapper = $this->createMock(originalClassName: \OCA\OpenRegister\Db\EntityRelationMapper::class);
        $mockMapper->method('findByFileId')->willReturn([]);
        $mockMapper->method('findEntitiesForFile')->willReturn([]);

        $this->mockContainer->method('get')
            ->willReturnMap([['OCA\OpenRegister\Db\EntityRelationMapper', $mockMapper]]);

        $result = $this->service->loadAnonymisedEntitiesForFile(fileId: 99);

        $this->assertSame(expected: [], actual: $result);

    }//end testLoadAnonymisedEntitiesForFileEmptyResult()

    /**
     * Test renderDossierSummary throws when dossier not found
     *
     * @return void
     */
    public function testRenderDossierSummaryThrowsWhenDossierNotFound(): void
    {
        $this->expectException(exception: \RuntimeException::class);

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $mockObjectService = $this->createMock(originalClassName: \OCA\OpenRegister\Service\ObjectService::class);
        $mockObjectService->method('find')->willReturn(null);

        $this->mockContainer->method('get')
            ->willReturnMap([['OCA\OpenRegister\Service\ObjectService', $mockObjectService]]);

        $this->service->renderDossierSummary(dossierId: '999');

    }//end testRenderDossierSummaryThrowsWhenDossierNotFound()

    /**
     * Test appendSummaryAsSeparatePdf saves a separate PDF alongside the anonymised file
     *
     * @return void
     */
    public function testAppendSummaryAsSeparatePdfSavesSeparateFile(): void
    {
        $this->mockAppManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $mockMapper = $this->createMock(originalClassName: \OCA\OpenRegister\Db\EntityRelationMapper::class);
        $mockMapper->method('findByFileId')->willReturn([]);
        $mockMapper->method('findEntitiesForFile')->willReturn([]);

        $this->mockContainer->method('get')
            ->willReturnMap([['OCA\OpenRegister\Db\EntityRelationMapper', $mockMapper]]);

        $mockUser = $this->createMock(originalClassName: IUser::class);
        $mockUser->method('getUID')->willReturn('alice');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->mockPdfService->method('renderHtmlPreview')->willReturn('<html></html>');
        $this->mockPdfService->method('renderPdf')->willReturn('%PDF-1.4 fake content');

        $mockSavedFile = $this->createMock(originalClassName: File::class);
        $mockSavedFile->method('getId')->willReturn(123);
        $mockSavedFile->method('getPath')->willReturn('/admin/files/anonymised/doc_grondslagen.pdf');

        $mockParentFolder = $this->createMock(originalClassName: Folder::class);
        $mockParentFolder->method('nodeExists')->willReturn(false);
        $mockParentFolder->method('newFile')->willReturn($mockSavedFile);

        $mockNode = $this->createMock(originalClassName: File::class);
        $mockNode->method('getId')->willReturn(42);
        $mockNode->method('getName')->willReturn('doc_anonymized.docx');
        $mockNode->method('getParent')->willReturn($mockParentFolder);

        $result = $this->service->appendSummaryAsSeparatePdf(node: $mockNode);

        $this->assertIsArray(actual: $result);
        $this->assertArrayHasKey(key: 'fileId', array: $result);
        $this->assertArrayHasKey(key: 'filePath', array: $result);
        $this->assertSame(expected: 123, actual: $result['fileId']);

    }//end testAppendSummaryAsSeparatePdfSavesSeparateFile()

    /**
     * Helper: create a mock EntityRelation with the given properties.
     *
     * @param int    $entityId        Entity ID
     * @param bool   $anonymized      Anonymized flag
     * @param string $anonymizedValue The replacement placeholder
     *
     * @return object Mock entity relation
     */
    private function createMockEntityRelation(
        int $entityId,
        bool $anonymized,
        string $anonymizedValue
    ): object {
        $mock = $this->getMockBuilder(className: \stdClass::class)
            ->addMethods(['getEntityId', 'getAnonymized', 'getAnonymizedValue'])
            ->getMock();

        $mock->method('getEntityId')->willReturn($entityId);
        $mock->method('getAnonymized')->willReturn($anonymized);
        $mock->method('getAnonymizedValue')->willReturn($anonymizedValue);

        return $mock;

    }//end createMockEntityRelation()
}//end class
