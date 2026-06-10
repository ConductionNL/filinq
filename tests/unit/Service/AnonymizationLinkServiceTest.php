<?php

/**
 * Unit tests for AnonymizationService::recordAnonymizationLink — anonymization-link-schema change
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
 * @spec openspec/changes/anonymization-link-schema/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\EntityDetectionService;
use OCA\DocuDesk\Service\FileEntityStatsService;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;
use RuntimeException;

/**
 * Tests for the idempotent source↔anonymised file-link UPSERT.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/anonymization-link-schema/tasks.md#task-3
 */
class AnonymizationLinkServiceTest extends TestCase
{
    /**
     * Build an AnonymizationService whose container resolves the given ObjectService.
     *
     * The app manager reports `openregister` as installed so
     * getOpenRegisterService() resolves through the container.
     *
     * @param ObjectService $objectService The (mock) OR object service to inject via the container.
     *
     * @return AnonymizationService
     */
    private function buildService(ObjectService $objectService): AnonymizationService
    {
        $container = $this->createMock(originalClassName: ContainerInterface::class);
        $container->method('get')->willReturn($objectService);

        $appManager = $this->createMock(originalClassName: IAppManager::class);
        $appManager->method('getInstalledApps')->willReturn(['openregister']);

        return new AnonymizationService(
            logger: new NullLogger(),
            container: $container,
            appManager: $appManager,
            entityDetection: $this->createMock(originalClassName: EntityDetectionService::class),
            appConfig: $this->createMock(originalClassName: IAppConfig::class),
            consentCrud: $this->createMock(originalClassName: ConsentCrudService::class),
            consentService: $this->createMock(originalClassName: ConsentService::class),
            grondslagenSummary: $this->createMock(originalClassName: GrondslagenSummaryService::class),
            fileEntityStats: $this->createMock(originalClassName: FileEntityStatsService::class),
            pdfConversion: $this->createMock(originalClassName: PdfConversionService::class)
        );

    }//end buildService()

    /**
     * Invoke the private recordAnonymizationLink() via reflection.
     *
     * @param AnonymizationService $service      The service under test.
     * @param int                  $fileId       Source file id.
     * @param mixed                $sourceNode   Source node (or null).
     * @param array<string, mixed> $resultInfo   Anonymisation result info.
     * @param string               $outputFormat Output format.
     *
     * @return array<string, mixed> The (possibly enriched) result info.
     */
    private function invokeRecord(
        AnonymizationService $service,
        int $fileId,
        mixed $sourceNode,
        array $resultInfo,
        string $outputFormat
    ): array {
        $method = new ReflectionMethod(objectOrMethod: $service, method: 'recordAnonymizationLink');
        $method->setAccessible(accessible: true);

        return $method->invokeArgs($service, [$fileId, $sourceNode, $resultInfo, $outputFormat]);

    }//end invokeRecord()

    /**
     * Not-found → create: a fresh link record with runCount 1 is saved and its id surfaced.
     *
     * @return void
     *
     * @spec openspec/changes/anonymization-link-schema/tasks.md#task-3
     */
    public function testRecordAnonymizationLinkCreatesNewRecord(): void
    {
        $captured = null;

        $objectService = $this->createMock(originalClassName: ObjectService::class);
        $objectService->method('searchObjects')->willReturn([]);
        $objectService->method('saveObject')->willReturnCallback(
            function (array $object, string $register, string $schema) use (&$captured) {
                $captured = $object;
                return ['@self' => ['id' => 'new-uuid-001']];
            }
        );

        $service = $this->buildService(objectService: $objectService);

        $result = $this->invokeRecord(
            service: $service,
            fileId: 42,
            sourceNode: null,
            resultInfo: [
                'anonymizedFileId'   => 99,
                'anonymizedFileName' => 'doc_anonymized.pdf',
                'anonymizedFilePath' => '/u/doc_anonymized.pdf',
                'replacementCount'   => 5,
            ],
            outputFormat: 'pdf'
        );

        $this->assertIsArray(actual: $captured, message: 'saveObject must have been called with an object.');
        $this->assertSame(expected: 42, actual: $captured['sourceFileId']);
        $this->assertSame(expected: 1, actual: $captured['runCount']);
        $this->assertSame(expected: 99, actual: $captured['anonymizedFileId']);
        $this->assertSame(expected: 'anonymized', actual: $captured['status']);
        $this->assertSame(expected: 'new-uuid-001', actual: $result['anonymizationLinkId']);

    }//end testRecordAnonymizationLinkCreatesNewRecord()

    /**
     * Found → update: the existing @self is preserved and runCount is incremented.
     *
     * @return void
     *
     * @spec openspec/changes/anonymization-link-schema/tasks.md#task-3
     */
    public function testRecordAnonymizationLinkUpdatesExistingRecord(): void
    {
        $captured = null;

        $existing = [
            '@self'            => ['id' => 'existing-uuid-007'],
            'sourceFileId'     => 42,
            'anonymizedFileId' => 90,
            'runCount'         => 1,
            'status'           => 'anonymized',
        ];

        $objectService = $this->createMock(originalClassName: ObjectService::class);
        $objectService->method('searchObjects')->willReturn([$existing]);
        $objectService->method('saveObject')->willReturnCallback(
            function (array $object, string $register, string $schema) use (&$captured) {
                $captured = $object;
                return ['@self' => ['id' => 'existing-uuid-007']];
            }
        );

        $service = $this->buildService(objectService: $objectService);

        $result = $this->invokeRecord(
            service: $service,
            fileId: 42,
            sourceNode: null,
            resultInfo: [
                'anonymizedFileId'   => 123,
                'anonymizedFileName' => 'doc_anonymized.pdf',
                'anonymizedFilePath' => '/u/doc_anonymized.pdf',
                'replacementCount'   => 8,
            ],
            outputFormat: 'pdf'
        );

        $this->assertIsArray(actual: $captured, message: 'saveObject must have been called.');
        $selfId = $captured['@self']['id'];
        $this->assertSame(expected: 'existing-uuid-007', actual: $selfId, message: 'Existing @self must be preserved.');
        $this->assertSame(expected: 2, actual: $captured['runCount'], message: 'runCount must be incremented.');
        $this->assertSame(expected: 123, actual: $captured['anonymizedFileId'], message: 'anonymizedFileId must reflect the new run.');
        $this->assertSame(expected: 'existing-uuid-007', actual: $result['anonymizationLinkId']);

    }//end testRecordAnonymizationLinkUpdatesExistingRecord()

    /**
     * Best-effort: a saveObject failure is swallowed and no link id is surfaced.
     *
     * @return void
     *
     * @spec openspec/changes/anonymization-link-schema/tasks.md#task-3
     */
    public function testRecordAnonymizationLinkIsBestEffortOnSaveFailure(): void
    {
        $objectService = $this->createMock(originalClassName: ObjectService::class);
        $objectService->method('searchObjects')->willReturn([]);
        $objectService->method('saveObject')->willThrowException(new RuntimeException('OR unavailable'));

        $service = $this->buildService(objectService: $objectService);

        $result = $this->invokeRecord(
            service: $service,
            fileId: 42,
            sourceNode: null,
            resultInfo: ['anonymizedFileId' => 99],
            outputFormat: 'pdf'
        );

        $this->assertArrayNotHasKey(key: 'anonymizationLinkId', array: $result, message: 'No link id when persistence fails.');

    }//end testRecordAnonymizationLinkIsBestEffortOnSaveFailure()

    /**
     * Best-effort: a searchObjects failure is swallowed; the method returns without saving.
     *
     * Chosen branch: the whole method body is wrapped in one try/catch, so a
     * lookup failure aborts the upsert entirely (no fall-through to create) —
     * the run result is returned unmodified.
     *
     * @return void
     *
     * @spec openspec/changes/anonymization-link-schema/tasks.md#task-3
     */
    public function testRecordAnonymizationLinkIsBestEffortOnSearchFailure(): void
    {
        $objectService = $this->createMock(originalClassName: ObjectService::class);
        $objectService->method('searchObjects')->willThrowException(new RuntimeException('search boom'));
        $objectService->expects($this->never())->method('saveObject');

        $service = $this->buildService(objectService: $objectService);

        $result = $this->invokeRecord(
            service: $service,
            fileId: 42,
            sourceNode: null,
            resultInfo: ['anonymizedFileId' => 99],
            outputFormat: 'pdf'
        );

        $this->assertArrayNotHasKey(key: 'anonymizationLinkId', array: $result, message: 'No link id when the lookup fails.');

    }//end testRecordAnonymizationLinkIsBestEffortOnSearchFailure()
}//end class
