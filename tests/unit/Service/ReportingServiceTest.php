<?php

/**
 * Unit tests for ReportingService
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

use OCA\DocuDesk\Service\ReportingService;
use OCP\Files\Node;
use OCP\Files\FileInfo;
use Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use OCP\IConfig;
use OCA\OpenRegister\Service\ObjectService;
use OCA\DocuDesk\Service\ExtractionService;
use OCP\Files\IRootFolder;
use OCA\DocuDesk\Service\AnonymizationService;
use OCP\IAppConfig;
use OCA\DocuDesk\Service\EntityService;

/**
 * Unit tests for ReportingService
 *
 * This test class provides comprehensive testing for the ReportingService functionality
 * including report creation, processing, and performance optimizations.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class ReportingServiceTest extends TestCase
{

    /**
     * The ReportingService instance being tested
     *
     * @var ReportingService
     */
    private ReportingService $reportingService;

    /**
     * Mocked LoggerInterface for logging operations
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked IConfig for configuration operations
     *
     * @var IConfig|MockObject
     */
    private IConfig|MockObject $mockConfig;

    /**
     * Mocked ObjectService for object operations
     *
     * @var ObjectService|MockObject
     */
    private ObjectService|MockObject $mockObjectService;

    /**
     * Mocked ExtractionService for text extraction
     *
     * @var ExtractionService|MockObject
     */
    private ExtractionService|MockObject $mockExtractionService;

    /**
     * Mocked IRootFolder for file operations
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Mocked AnonymizationService for anonymization operations
     *
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $mockAnonymizationService;

    /**
     * Mocked IAppConfig for app configuration
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * Mocked EntityService for entity operations
     *
     * @var EntityService|MockObject
     */
    private EntityService|MockObject $mockEntityService;


    /**
     * Set up test environment before each test
     *
     * This method initializes the test environment by creating mock objects
     * and setting up the ReportingService instance for testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockConfig = $this->createMock(IConfig::class);
        $this->mockObjectService = $this->createMock(ObjectService::class);
        $this->mockExtractionService = $this->createMock(ExtractionService::class);
        $this->mockRootFolder = $this->createMock(IRootFolder::class);
        $this->mockAnonymizationService = $this->createMock(AnonymizationService::class);
        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->mockEntityService = $this->createMock(EntityService::class);

        // Configure default app config values
        $this->mockAppConfig->expects($this->any())
            ->method('getValueString')
            ->willReturnMap([
                ['docudesk', 'report_register', 'document', 'document'],
                ['docudesk', 'report_schema', 'report', 'report'],
            ]);

        // Configure default system config values
        $this->mockConfig->expects($this->any())
            ->method('getSystemValue')
            ->willReturnMap([
                ['docudesk_enable_reporting', true, true],
            ]);

        // Initialize ReportingService with mocked dependencies
        $this->reportingService = new ReportingService(
            $this->mockLogger,
            $this->mockConfig,
            $this->mockObjectService,
            $this->mockExtractionService,
            $this->mockRootFolder,
            $this->mockAnonymizationService,
            $this->mockAppConfig,
            $this->mockEntityService
        );

    }//end setUp()


    /**
     * Clean up after each test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

    }//end tearDown()


    /**
     * Test that createReport skips anonymized files
     *
     * This test verifies that the createReport method correctly skips files
     * that end with '_anonymized' to optimize performance.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testCreateReportSkipsAnonymizedFiles(): void
    {
        // Arrange: Create a mock node for an anonymized file
        $mockNode = $this->createMock(Node::class);
        
        // Configure the mock node to represent an anonymized file
        $mockNode->expects($this->once())
            ->method('getType')
            ->willReturn(FileInfo::TYPE_FILE);
            
        $mockNode->expects($this->once())
            ->method('getName')
            ->willReturn('document_anonymized.docx');

        $mockNode->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $mockNode->expects($this->once())
            ->method('getPath')
            ->willReturn('/user/files/document_anonymized.docx');

        // Expect logger to be called with skip message
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Skipping report creation for anonymized file'),
                $this->arrayHasKey('nodeId')
            );

        // Act: Call the method under test
        $result = $this->reportingService->createReport($mockNode);

        // Assert: Verify that null is returned (no report created)
        $this->assertNull($result);

    }//end testCreateReportSkipsAnonymizedFiles()


    /**
     * Test that createReport processes normal files
     *
     * This test verifies that the createReport method processes files
     * that do not end with '_anonymized' normally.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testCreateReportProcessesNormalFiles(): void
    {
        // Arrange: Create a mock node for a normal file
        $mockNode = $this->createMock(Node::class);
        
        // Configure the mock node to represent a normal file
        $mockNode->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn(FileInfo::TYPE_FILE);
            
        $mockNode->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('document.docx');

        $mockNode->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(456);

        $mockNode->expects($this->atLeastOnce())
            ->method('getPath')
            ->willReturn('/user/files/document.docx');

        $mockNode->expects($this->atLeastOnce())
            ->method('getMimetype')
            ->willReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $mockNode->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(1024);

        $mockNode->expects($this->atLeastOnce())
            ->method('getEtag')
            ->willReturn('abc123');

        // Mock getReport to return null (no existing report)
        $this->mockObjectService->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        // Mock saveObject to return a mock entity
        $mockEntity = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $mockEntity->expects($this->any())
            ->method('jsonSerialize')
            ->willReturn([
                'id' => '789',
                'nodeId' => 456,
                'fileName' => 'document.docx',
                'status' => 'pending',
            ]);

        $this->mockObjectService->expects($this->once())
            ->method('saveObject')
            ->willReturn($mockEntity);

        // Expect logger NOT to be called with skip message
        $this->mockLogger->expects($this->never())
            ->method('info')
            ->with($this->stringContains('Skipping report creation for anonymized file'));

        // Act: Call the method under test (this will also trigger processReport)
        $result = $this->reportingService->createReport($mockNode);

        // Assert: Verify that a report array is returned
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('789', $result['id']);

    }//end testCreateReportProcessesNormalFiles()


    /**
     * Test various anonymized filename patterns
     *
     * This test verifies that different patterns of anonymized filenames
     * are correctly detected and skipped.
     *
     * @dataProvider anonymizedFilenameProvider
     *
     * @param string $filename    The filename to test
     * @param bool   $shouldSkip  Whether the file should be skipped
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testAnonymizedFilenamePatterns(string $filename, bool $shouldSkip): void
    {
        // Arrange: Create a mock node with the test filename
        $mockNode = $this->createMock(Node::class);
        
        $mockNode->expects($this->once())
            ->method('getType')
            ->willReturn(FileInfo::TYPE_FILE);
            
        $mockNode->expects($this->once())
            ->method('getName')
            ->willReturn($filename);

        $mockNode->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $mockNode->expects($this->any())
            ->method('getPath')
            ->willReturn('/user/files/' . $filename);

        if ($shouldSkip === true) {
            // Expect logger to be called with skip message
            $this->mockLogger->expects($this->once())
                ->method('info')
                ->with($this->stringContains('Skipping report creation for anonymized file'));
        } else {
            // Mock normal processing setup for non-anonymized files
            $mockNode->expects($this->any())
                ->method('getMimetype')
                ->willReturn('application/pdf');

            $mockNode->expects($this->any())
                ->method('getSize')
                ->willReturn(1024);

            $mockNode->expects($this->any())
                ->method('getEtag')
                ->willReturn('abc123');

            $this->mockObjectService->expects($this->any())
                ->method('findAll')
                ->willReturn([]);

            $mockEntity = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
            $mockEntity->expects($this->any())
                ->method('jsonSerialize')
                ->willReturn(['id' => '789', 'status' => 'pending']);

            $this->mockObjectService->expects($this->any())
                ->method('saveObject')
                ->willReturn($mockEntity);
        }

        // Act: Call the method under test
        $result = $this->reportingService->createReport($mockNode);

        // Assert: Verify expected behavior
        if ($shouldSkip === true) {
            $this->assertNull($result);
        } else {
            $this->assertIsArray($result);
        }

    }//end testAnonymizedFilenamePatterns()


    /**
     * Data provider for anonymized filename patterns
     *
     * @return array<string, array{string, bool}> Test data with filename and expected skip behavior
     */
    public static function anonymizedFilenameProvider(): array
    {
        return [
            'Simple anonymized docx' => ['document_anonymized.docx', true],
            'Simple anonymized pdf' => ['report_anonymized.pdf', true],
            'Simple anonymized txt' => ['notes_anonymized.txt', true],
            'Complex anonymized name' => ['sensitive_data_2024_anonymized.xlsx', true],
            'Anonymized without extension' => ['file_anonymized', true],
            'Normal document' => ['document.docx', false],
            'Normal pdf' => ['report.pdf', false],
            'File with anonymous in name but not ending' => ['anonymous_document.docx', false],
            'File with anonymized in middle' => ['doc_anonymized_version.pdf', false],
            'Case sensitive check' => ['document_ANONYMIZED.docx', false], // Should not match (case sensitive)
        ];

    }//end anonymizedFilenameProvider()


}//end class 