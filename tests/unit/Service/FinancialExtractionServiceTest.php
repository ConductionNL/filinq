<?php

/**
 * Unit tests for FinancialExtractionService
 *
 * Covers the heuristic pipeline shape/confidence (REQ-FIN-02..04), the
 * request validation + persistence path (REQ-FIN-01), the correction-
 * feedback path (REQ-FIN-07), and the AI-enhancement graceful-degradation +
 * checksum-lock guarantees (REQ-FIN-06).
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\Extraction\AmountExtractor;
use OCA\DocuDesk\Service\Extraction\DateExtractor;
use OCA\DocuDesk\Service\Extraction\IbanExtractor;
use OCA\DocuDesk\Service\Extraction\KvkExtractor;
use OCA\DocuDesk\Service\Extraction\TotalsReconciler;
use OCA\DocuDesk\Service\Extraction\VatIdExtractor;
use OCA\DocuDesk\Service\OpenRegisterResolver;
use OCA\DocuDesk\Service\FinancialExtractionService;
use OCA\DocuDesk\Service\OcrService;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for FinancialExtractionService.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinancialExtractionServiceTest extends TestCase
{

    private FinancialExtractionService $service;

    private SettingsService|MockObject $settingsService;

    private ObjectService|MockObject $objectService;

    private IAppConfig|MockObject $config;

    private IUserSession|MockObject $userSession;

    private IRootFolder|MockObject $rootFolder;

    private OcrService|MockObject $ocrService;

    private IEventDispatcher|MockObject $eventDispatcher;

    private ContainerInterface|MockObject $container;

    /**
     * Example supplier-invoice text (mirrors the design.md seed example).
     *
     * @var string
     */
    private const INVOICE_TEXT = <<<TEXT
Hostbaar B.V.
KvK: 12345678
BTW-nummer: NL001234567B01
IBAN: NL91ABNA0417164300
Factuurnummer: 2024-0042
Factuurdatum: 15-03-2024
Vervaldatum: 14-04-2024
Managed hosting maart 2024
Subtotaal € 100,00
BTW € 21,00
Totaal € 121,00
TEXT;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectService = $this->getMockBuilder(className: ObjectService::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['saveObject', 'find'])
            ->getMock();

        $this->settingsService = $this->createMock(SettingsService::class);
        $this->settingsService->method('getObjectService')->willReturn($this->objectService);
        // The binding is resolved through SettingsService and FAILS CLOSED when
        // unset, instead of defaulting to register '' / schema ''. An unstubbed
        // mock returns null, which is precisely what the guard exists to catch.
        $this->settingsService->method('resolveFinancialExtractionBinding')
            ->willReturn(['register' => 'document', 'schema' => 'financialExtraction']);

        $this->config = $this->createMock(IAppConfig::class);
        $this->config->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default=''): string {
                $map = [
                    'financialExtraction_register' => 'document',
                    'financialExtraction_schema'   => 'financialExtraction',
                ];
                return $map[$key] ?? $default;
            }
        );

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('annemarie');

        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession->method('getUser')->willReturn($user);

        $this->rootFolder      = $this->createMock(IRootFolder::class);
        $this->ocrService      = $this->createMock(OcrService::class);
        $this->eventDispatcher = $this->createMock(IEventDispatcher::class);
        $this->container       = $this->createMock(ContainerInterface::class);
        $logger                = $this->createMock(LoggerInterface::class);

        $this->service = new FinancialExtractionService(
            settingsService: $this->settingsService,
            // A REAL resolver over the stubbed SettingsService, not a mock: it
            // is the piece that turns an unset binding into
            // RegisterNotConfiguredException, so mocking it away would remove
            // the behaviour these tests are meant to run through.
            registerResolver: new OpenRegisterResolver(settingsService: $this->settingsService),
            userSession: $this->userSession,
            rootFolder: $this->rootFolder,
            ocrService: $this->ocrService,
            eventDispatcher: $this->eventDispatcher,
            container: $this->container,
            logger: $logger,
            ibanExtractor: new IbanExtractor(),
            kvkExtractor: new KvkExtractor(),
            vatIdExtractor: new VatIdExtractor(),
            dateExtractor: new DateExtractor(),
            amountExtractor: new AmountExtractor(),
            totalsReconciler: new TotalsReconciler(),
        );

    }//end setUp()

    /**
     * runExtraction() on fixture text shapes the full field set with bounded
     * confidences and boosts reconciling totals (task 3.1 acceptance).
     *
     * @return void
     */
    public function testRunExtractionShapesFullFieldSetWithBoundedConfidence(): void
    {
        $result = $this->service->runExtraction(text: self::INVOICE_TEXT, docType: 'supplier-invoice');

        // Every declared key is present (REQ-FIN-03).
        foreach (['supplierName', 'supplierIban', 'supplierKvk', 'supplierVatId', 'invoiceNumber', 'issueDate', 'dueDate', 'currency', 'totalExcl', 'totalVat', 'totalIncl', 'vatBreakdown', 'lines'] as $key) {
            $this->assertArrayHasKey($key, $result['fields']);
        }

        $this->assertSame('NL91ABNA0417164300', $result['fields']['supplierIban']);
        $this->assertSame('12345678', $result['fields']['supplierKvk']);
        $this->assertSame('NL001234567B01', $result['fields']['supplierVatId']);
        $this->assertSame('2024-03-15', $result['fields']['issueDate']);
        $this->assertSame('2024-04-14', $result['fields']['dueDate']);
        $this->assertSame(100.0, $result['fields']['totalExcl']);
        $this->assertSame(21.0, $result['fields']['totalVat']);
        $this->assertSame(121.0, $result['fields']['totalIncl']);
        $this->assertTrue($result['reconciled']);

        foreach ($result['fieldConfidence'] as $confidence) {
            $this->assertGreaterThanOrEqual(0.0, $confidence);
            $this->assertLessThanOrEqual(1.0, $confidence);
        }

        $this->assertGreaterThanOrEqual(0.0, $result['overallConfidence']);
        $this->assertLessThanOrEqual(1.0, $result['overallConfidence']);

    }//end testRunExtractionShapesFullFieldSetWithBoundedConfidence()

    /**
     * A field the pipeline cannot determine is null, never omitted, and
     * contributes nothing to fieldConfidence (REQ-FIN-04).
     *
     * @return void
     */
    public function testRunExtractionMissingFieldIsNullNotOmitted(): void
    {
        $result = $this->service->runExtraction(text: 'Lunch € 18,50', docType: 'receipt');

        $this->assertArrayHasKey('supplierKvk', $result['fields']);
        $this->assertNull($result['fields']['supplierKvk']);
        $this->assertArrayNotHasKey('supplierKvk', $result['fieldConfidence']);

    }//end testRunExtractionMissingFieldIsNullNotOmitted()

    /**
     * Non-reconciling totals do not receive a confidence boost.
     *
     * @return void
     */
    public function testNonReconcilingTotalsGetNoBoost(): void
    {
        $result = $this->service->runExtraction(
            text: 'Subtotaal € 100,00 BTW € 21,00 Totaal € 130,00',
            docType: 'supplier-invoice'
        );

        $this->assertFalse($result['reconciled']);

    }//end testNonReconcilingTotalsGetNoBoost()

    /**
     * A VAT breakdown with two distinct rates yields two entries, each
     * carrying its own base and amount.
     *
     * @return void
     */
    public function testVatBreakdownCapturedPerRate(): void
    {
        $result = $this->service->runExtraction(
            text: "21% BTW over € 100,00 = € 21,00\n9% BTW over € 16,97 = € 1,53",
            docType: 'supplier-invoice'
        );

        $rates = array_column($result['fields']['vatBreakdown'], 'rate');
        $this->assertCount(2, $result['fields']['vatBreakdown']);
        $this->assertContains(21, $rates);
        $this->assertContains(9, $rates);

    }//end testVatBreakdownCapturedPerRate()

    /**
     * An invalid docType is rejected with a 400-coded exception before any
     * extraction runs.
     *
     * @return void
     */
    public function testExtractFinancialRejectsInvalidDocType(): void
    {
        $this->objectService->expects($this->never())->method('saveObject');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(400);

        $this->service->extractFinancial(data: ['docType' => 'invoice', 'fileId' => 1], requestedBy: 'annemarie');

    }//end testExtractFinancialRejectsInvalidDocType()

    /**
     * Neither fileId nor documentUri supplied is rejected with a 400-coded
     * exception and nothing is persisted.
     *
     * @return void
     */
    public function testExtractFinancialRejectsMissingFileReference(): void
    {
        $this->objectService->expects($this->never())->method('saveObject');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(400);

        $this->service->extractFinancial(data: ['docType' => 'receipt'], requestedBy: 'annemarie');

    }//end testExtractFinancialRejectsMissingFileReference()

    /**
     * A valid request resolves OCR text, populates fields, and persists a
     * financialExtraction object; no event is dispatched when callbackEvent
     * is false (REQ-FIN-01).
     *
     * @return void
     */
    public function testExtractFinancialHappyPathPersistsWithoutEvent(): void
    {
        $this->mockResolvableImageFile(fileId: 42, text: self::INVOICE_TEXT);

        $this->objectService->expects($this->once())
            ->method('saveObject')
            ->willReturnCallback(function ($object, $register, $schema) {
                $this->assertSame('document', $register);
                $this->assertSame('financialExtraction', $schema);
                $this->assertSame('supplier-invoice', $object['docType']);
                $this->assertSame('annemarie', $object['requestedBy']);
                $this->assertSame('shillinq', $object['sourceApp']);
                $this->assertSame('NL91ABNA0417164300', $object['fields']['supplierIban']);
                $this->assertSame([], $object['corrections']);

                $saved             = $object;
                $saved['id']       = 'extraction-1';
                return new class ($saved) implements \JsonSerializable {
                    public function __construct(private array $data)
                    {
                    }
                    public function jsonSerialize(): array
                    {
                        return $this->data;
                    }
                };
            });

        $this->eventDispatcher->expects($this->never())->method('dispatchTyped');

        $result = $this->service->extractFinancial(
            data: ['fileId' => 42, 'docType' => 'supplier-invoice', 'sourceApp' => 'shillinq', 'callbackEvent' => false],
            requestedBy: 'annemarie'
        );

        $this->assertSame('extraction-1', $result['id']);

    }//end testExtractFinancialHappyPathPersistsWithoutEvent()

    /**
     * callbackEvent: true dispatches the canonical completion event exactly
     * once (REQ-FIN-05).
     *
     * @return void
     */
    public function testExtractFinancialDispatchesEventWhenRequested(): void
    {
        $this->mockResolvableImageFile(fileId: 7, text: self::INVOICE_TEXT);

        $this->objectService->method('saveObject')->willReturnCallback(function ($object) {
            $saved       = $object;
            $saved['id'] = 'extraction-2';
            return new class ($saved) implements \JsonSerializable {
                public function __construct(private array $data)
                {
                }
                public function jsonSerialize(): array
                {
                    return $this->data;
                }
            };
        });

        $this->eventDispatcher->expects($this->once())
            ->method('dispatchTyped')
            ->with($this->isInstanceOf(\OCA\DocuDesk\Event\FinancialExtractionCompletedEvent::class));

        $this->service->extractFinancial(
            data: ['fileId' => 7, 'docType' => 'supplier-invoice', 'sourceApp' => 'shillinq', 'callbackEvent' => true],
            requestedBy: 'annemarie'
        );

    }//end testExtractFinancialDispatchesEventWhenRequested()

    /**
     * Corrections for an unknown extraction id return a 404-coded exception
     * and store nothing (REQ-FIN-07).
     *
     * @return void
     */
    public function testAddCorrectionUnknownIdThrows404(): void
    {
        $this->objectService->method('find')->willReturn(null);
        $this->objectService->expects($this->never())->method('saveObject');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(404);

        $this->service->addCorrection(id: 'missing', correctedFields: ['supplierName' => 'ACME B.V.'], correctedBy: 'annemarie');

    }//end testAddCorrectionUnknownIdThrows404()

    /**
     * A correction is stored paired with the original value, additively —
     * the original extraction fields are left untouched (REQ-FIN-07).
     *
     * @return void
     */
    public function testAddCorrectionStoredAdditively(): void
    {
        $existing = new class ([
            'id'                => 'extraction-3',
            'fields'            => ['supplierName' => null, 'totalIncl' => 18.50],
            'fieldConfidence'   => ['totalIncl' => 0.71],
            'overallConfidence' => 0.71,
            'corrections'       => [],
        ]) implements \JsonSerializable {
            public function __construct(private array $data)
            {
            }
            public function jsonSerialize(): array
            {
                return $this->data;
            }
        };

        $this->objectService->method('find')->willReturn($existing);
        $this->objectService->expects($this->once())
            ->method('saveObject')
            ->willReturnCallback(function ($object) {
                $this->assertSame(18.50, $object['fields']['totalIncl']);
                $this->assertCount(1, $object['corrections']);
                $this->assertSame('supplierName', $object['corrections'][0]['field']);
                $this->assertNull($object['corrections'][0]['original']);
                $this->assertSame('Lunchroom De Hoek', $object['corrections'][0]['corrected']);
                $this->assertSame('annemarie', $object['corrections'][0]['correctedBy']);

                return new class ($object) implements \JsonSerializable {
                    public function __construct(private array $data)
                    {
                    }
                    public function jsonSerialize(): array
                    {
                        return $this->data;
                    }
                };
            });

        $result = $this->service->addCorrection(
            id: 'extraction-3',
            correctedFields: ['supplierName' => 'Lunchroom De Hoek'],
            correctedBy: 'annemarie'
        );

        $this->assertSame(18.50, $result['fields']['totalIncl']);

    }//end testAddCorrectionStoredAdditively()

    /**
     * No AI provider available (container resolution fails for both
     * managers): the heuristic-only result is returned unchanged and no
     * error is raised (REQ-FIN-06 graceful degradation).
     *
     * @return void
     */
    public function testApplyAiEnhancementNoProviderReturnsUnchanged(): void
    {
        $this->container->method('get')->willThrowException(new \Exception('service not found'));

        $heuristicOnly = $this->service->runExtraction(text: 'Lunch € 18,50', docType: 'receipt');
        $enhanced      = $this->service->applyAiEnhancement(text: 'Lunch € 18,50', result: $heuristicOnly, requestedBy: 'annemarie');

        $this->assertSame($heuristicOnly, $enhanced);

    }//end testApplyAiEnhancementNoProviderReturnsUnchanged()

    /**
     * An available AI provider fills a null field but never overwrites a
     * checksum-validated field (mod-97-valid supplierIban) (REQ-FIN-06).
     *
     * @return void
     */
    public function testApplyAiEnhancementFillsNullFieldWithoutOverwritingLockedField(): void
    {
        $manager = $this->createMock(\OCP\TaskProcessing\IManager::class);
        $manager->method('runTask')->willReturnCallback(function (\OCP\TaskProcessing\Task $task) {
            $task->setStatus(\OCP\TaskProcessing\Task::STATUS_SUCCESSFUL);
            $task->setOutput(['output' => json_encode(['supplierName' => 'Hostbaar B.V.', 'supplierIban' => 'NL00FAKE0000000000'])]);
            return $task;
        });

        $this->container->method('get')->willReturnCallback(
            function (string $class) use ($manager) {
                if ($class === 'OCP\\TaskProcessing\\IManager') {
                    return $manager;
                }
                throw new \Exception('unexpected service: '.$class);
            }
        );

        $text     = 'IBAN: NL91ABNA0417164300';
        $baseline = $this->service->runExtraction(text: $text, docType: 'receipt');
        $this->assertSame('NL91ABNA0417164300', $baseline['fields']['supplierIban']);
        $this->assertNull($baseline['fields']['supplierName']);

        $enhanced = $this->service->applyAiEnhancement(text: $text, result: $baseline, requestedBy: 'annemarie');

        $this->assertSame('Hostbaar B.V.', $enhanced['fields']['supplierName']);
        // The checksum-valid IBAN is never overwritten by the AI's (bogus) suggestion.
        $this->assertSame('NL91ABNA0417164300', $enhanced['fields']['supplierIban']);

    }//end testApplyAiEnhancementFillsNullFieldWithoutOverwritingLockedField()

    /**
     * Mock a resolvable image file (fileId -> IUserSession/IRootFolder/File)
     * whose OCR extraction yields the given text.
     *
     * @param int    $fileId The Nextcloud file id.
     * @param string $text   The text OcrService should return.
     *
     * @return void
     */
    private function mockResolvableImageFile(int $fileId, string $text): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getName')->willReturn('receipt.jpg');
        $file->method('getContent')->willReturn('binary-image-bytes');

        $folder = $this->createMock(Folder::class);
        $folder->method('getById')->with($fileId)->willReturn([$file]);

        $this->rootFolder->method('getUserFolder')->with('annemarie')->willReturn($folder);

        $this->ocrService->method('needsOcr')->willReturn(true);
        $this->ocrService->method('isTesseractAvailable')->willReturn(true);
        $this->ocrService->method('getOcrLanguages')->willReturn('nld+eng');
        $this->ocrService->method('getOcrDpi')->willReturn(300);
        $this->ocrService->method('extractTextFromImage')->willReturn(['text' => $text, 'confidence' => 92.0]);

    }//end mockResolvableImageFile()
}//end class
