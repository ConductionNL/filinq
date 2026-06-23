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
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\DocuDesk\Service\PdfService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IL10N;
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
 * suite exercises the pure-data helpers (`resolveBaseLabels`,
 * `countDistinctBases`, `aggregateForDossier`) through reflection plus
 * the construction smoke test.
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
        $this->assertStringStartsWith(prefix: '⟨grondslag verwijderd:', string: $result['persoonsgegevens']);

    }//end testResolveBaseLabelsProducesPlaceholders()


    /**
     * `countDistinctBases` deduplicates the union of `bases` arrays across rows.
     *
     * @return void
     */
    public function testCountDistinctBases(): void
    {
        $method = new ReflectionMethod(
            objectOrMethod: GrondslagenSummaryService::class,
            method: 'countDistinctBases'
        );
        $method->setAccessible(accessible: true);

        $entities = [
            ['bases' => ['persoonsgegevens', 'strafrechtelijk']],
            ['bases' => ['persoonsgegevens']],
            ['bases' => []],
            ['bases' => null],
            ['bases' => ['nationale-veiligheid']],
        ];

        $count = $method->invoke($this->service, $entities);

        $this->assertSame(expected: 3, actual: $count);

    }//end testCountDistinctBases()


    /**
     * `aggregateForDossier` produces per-document, per-basis, and totals
     * tables matching the per-dossier template's expected shape.
     *
     * @return void
     */
    public function testAggregateForDossier(): void
    {
        $method = new ReflectionMethod(
            objectOrMethod: GrondslagenSummaryService::class,
            method: 'aggregateForDossier'
        );
        $method->setAccessible(accessible: true);

        $perFile = [
            [
                'fileId'   => 10,
                'filename' => 'verslag-1.pdf',
                'entities' => [
                    ['bases' => ['persoonsgegevens']],
                    ['bases' => ['persoonsgegevens', 'strafrechtelijk']],
                ],
            ],
            [
                'fileId'   => 11,
                'filename' => 'verslag-2.pdf',
                'entities' => [
                    ['bases' => ['nationale-veiligheid']],
                    ['bases' => ['persoonsgegevens']],
                ],
            ],
        ];

        $labelMap = [
            'persoonsgegevens'     => 'Persoonsgegevens',
            'strafrechtelijk'      => 'Strafrechtelijke gegevens',
            'nationale-veiligheid' => 'Nationale veiligheid',
        ];

        $result = $method->invoke($this->service, $perFile, $labelMap);

        $this->assertSame(expected: 2, actual: $result['totals']['documentCount']);
        $this->assertSame(expected: 4, actual: $result['totals']['entityCount']);
        $this->assertSame(expected: 3, actual: $result['totals']['distinctBasesCount']);

        $this->assertCount(expectedCount: 2, haystack: $result['perDocument']);
        $this->assertSame(expected: 'verslag-1.pdf', actual: $result['perDocument'][0]['filename']);
        $this->assertSame(expected: 2, actual: $result['perDocument'][0]['entityCount']);

        $perBasis = $result['perBasis'];
        $this->assertCount(expectedCount: 3, haystack: $perBasis);

        // Persoonsgegevens appears in both documents, three times total.
        $persoonsgegevens = $this->findBasisRow(rows: $perBasis, ref: 'persoonsgegevens');
        $this->assertNotNull(actual: $persoonsgegevens);
        $this->assertSame(expected: 'Persoonsgegevens', actual: $persoonsgegevens['name']);
        $this->assertSame(expected: 2, actual: $persoonsgegevens['documentCount']);
        $this->assertSame(expected: 3, actual: $persoonsgegevens['entityCount']);

    }//end testAggregateForDossier()


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


    /**
     * The summary localises the placeholder TYPE to the acting user's language
     * (PERSON → PERSOON) so the legend matches OpenRegister's redacted output;
     * an unknown type falls back to its raw label.
     *
     * @return void
     */
    public function testLocalizeEntityTypeTranslatesKnownTypesAndFallsBack(): void
    {
        $l10n = $this->createMock(originalClassName: IL10N::class);
        $l10n->method('t')->willReturnCallback(
            static function (string $text): string {
                $map = ['PERSON' => 'PERSOON', 'ORGANIZATION' => 'ORGANISATIE'];
                return ($map[$text] ?? $text);
            }
        );

        $service = new GrondslagenSummaryService(
            logger: $this->mockLogger,
            pdfService: $this->mockPdfService,
            rootFolder: $this->mockRootFolder,
            userSession: $this->mockUserSession,
            appManager: $this->mockAppManager,
            container: $this->mockContainer,
            l10n: $l10n
        );

        $method = new ReflectionMethod(
            objectOrMethod: GrondslagenSummaryService::class,
            method: 'localizeEntityType'
        );
        $method->setAccessible(accessible: true);

        $this->assertSame(expected: 'PERSOON', actual: $method->invoke($service, 'PERSON'));
        $this->assertSame(expected: 'ORGANISATIE', actual: $method->invoke($service, 'ORGANIZATION'));
        // Unknown / free-form type → raw label unchanged.
        $this->assertSame(expected: 'CUSTOM_THING', actual: $method->invoke($service, 'CUSTOM_THING'));

    }//end testLocalizeEntityTypeTranslatesKnownTypesAndFallsBack()


    /**
     * With no IL10N injected the raw English label is emitted.
     *
     * @return void
     */
    public function testLocalizeEntityTypeWithoutL10nReturnsRaw(): void
    {
        // $this->service was constructed without an IL10N (l10n defaults null).
        $method = new ReflectionMethod(
            objectOrMethod: GrondslagenSummaryService::class,
            method: 'localizeEntityType'
        );
        $method->setAccessible(accessible: true);

        $this->assertSame(expected: 'PERSON', actual: $method->invoke($this->service, 'PERSON'));

    }//end testLocalizeEntityTypeWithoutL10nReturnsRaw()


}//end class
