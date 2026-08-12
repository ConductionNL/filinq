<?php

/**
 * Unit tests for LegalBasesSummaryService
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

use OCA\DocuDesk\Service\LegalBasesSummaryService;
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
 * Cover the deterministic helpers of LegalBasesSummaryService.
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
class LegalBasesSummaryServiceTest extends TestCase
{

    /**
     * Service under test.
     *
     * @var LegalBasesSummaryService
     */
    private LegalBasesSummaryService $service;

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

        $this->service = new LegalBasesSummaryService(
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
            expected: LegalBasesSummaryService::class,
            actual: $this->service
        );

    }//end testServiceCanBeInstantiated()

    /**
     * `resolveBaseLabels` produces a placeholder entry for every input ref.
     *
     * When OpenRegister's ObjectService is unavailable (the case here — the
     * test stubs no installed apps), `resolveBaseLabels` is best-effort: it
     * returns one `{name, description}` entry per ref with `name` set to the
     * raw ref (so the operator sees the slug rather than a dangling label).
     *
     * @return void
     */
    public function testResolveBaseLabelsFallsBackToRawRef(): void
    {
        $method = new ReflectionMethod(
            objectOrMethod: LegalBasesSummaryService::class,
            method: 'resolveBaseLabels'
        );
        $method->setAccessible(accessible: true);

        $result = $method->invoke($this->service, ['persoonsgegevens', 'long-uuid-12345']);

        $this->assertCount(expectedCount: 2, haystack: $result);
        $this->assertArrayHasKey(key: 'persoonsgegevens', array: $result);
        $this->assertArrayHasKey(key: 'long-uuid-12345', array: $result);
        // ObjectService unavailable → each ref maps to {name: <raw ref>, description: ''}.
        $this->assertSame(expected: 'persoonsgegevens', actual: $result['persoonsgegevens']['name']);
        $this->assertSame(expected: '', actual: $result['persoonsgegevens']['description']);

    }//end testResolveBaseLabelsFallsBackToRawRef()

    /**
     * `countDistinctBases` deduplicates the union of `bases` arrays across rows.
     *
     * @return void
     */
    public function testCountDistinctBases(): void
    {
        $method = new ReflectionMethod(
            objectOrMethod: LegalBasesSummaryService::class,
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
     * `aggregateForDossier` DEDUPS to one row per distinct entity
     * (entityType:entityId): the same entity across files yields a single row
     * with the occurrence count summed, the files comma-joined, and the
     * grondslagen unioned. Rows are ordered by TYPE then NUMERIC id ascending.
     *
     * @return void
     */
    public function testAggregateForDossier(): void
    {
        $method = new ReflectionMethod(
            objectOrMethod: LegalBasesSummaryService::class,
            method: 'aggregateForDossier'
        );
        $method->setAccessible(accessible: true);

        // PERSON:1 appears in BOTH files (must dedup to one row); DATE:2 and
        // LOCATION:10 are distinct. Numbers chosen so a lexical sort (10<2)
        // would mis-order — the numeric sort must put 2 before 10.
        $perFile = [
            [
                'fileId'   => 10,
                'filename' => 'verslag-1.pdf',
                'entities' => [
                    [
                        'placeholder' => '[PERSOON: 1]',
                        'entityType'  => 'PERSON',
                        'entityId'    => 1,
                        'count'       => 1,
                        'bases'       => ['persoonsgegevens'],
                        'baseLabels'  => ['Persoonsgegevens'],
                    ],
                    [
                        'placeholder' => '[DATUM: 2]',
                        'entityType'  => 'DATE',
                        'entityId'    => 2,
                        'count'       => 1,
                        'bases'       => ['strafrechtelijk'],
                        'baseLabels'  => ['Strafrechtelijke gegevens'],
                    ],
                ],
            ],
            [
                'fileId'   => 11,
                'filename' => 'verslag-2.pdf',
                'entities' => [
                    [
                        'placeholder' => '[PERSOON: 1]',
                        'entityType'  => 'PERSON',
                        'entityId'    => 1,
                        'count'       => 2,
                        'bases'       => ['persoonsgegevens'],
                        'baseLabels'  => ['Persoonsgegevens'],
                    ],
                    [
                        'placeholder' => '[LOCATIE: 10]',
                        'entityType'  => 'LOCATION',
                        'entityId'    => 10,
                        'count'       => 1,
                        'bases'       => ['nationale-veiligheid'],
                        'baseLabels'  => ['Nationale veiligheid'],
                    ],
                ],
            ],
        ];

        $result = $method->invoke($this->service, $perFile, []);

        // Totals.
        $this->assertSame(expected: 2, actual: $result['totals']['documentCount']);
        // Total occurrences across files: 1 + 1 (file 10) + 2 + 1 (file 11) = 5.
        $this->assertSame(expected: 5, actual: $result['totals']['entityCount']);
        $this->assertSame(expected: 3, actual: $result['totals']['distinctEntityCount']);
        $this->assertSame(expected: 3, actual: $result['totals']['distinctBasesCount']);

        // Deduped: one row per distinct entity, numeric order DATE 2 → LOCATION 10 → PERSON 1.
        $rows = $result['rows'];
        $this->assertCount(expectedCount: 3, haystack: $rows);
        $this->assertSame(expected: '[DATUM: 2]', actual: $rows[0]['placeholder']);
        $this->assertSame(expected: '[LOCATIE: 10]', actual: $rows[1]['placeholder']);
        $this->assertSame(expected: '[PERSOON: 1]', actual: $rows[2]['placeholder']);

        // PERSON:1 merged across both files: count summed, files joined.
        $this->assertSame(expected: 3, actual: $rows[2]['count']);
        $this->assertSame(expected: 'verslag-1.pdf, verslag-2.pdf', actual: $rows[2]['filename']);

    }//end testAggregateForDossier()

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

        $service = new LegalBasesSummaryService(
            logger: $this->mockLogger,
            pdfService: $this->mockPdfService,
            rootFolder: $this->mockRootFolder,
            userSession: $this->mockUserSession,
            appManager: $this->mockAppManager,
            container: $this->mockContainer,
            l10n: $l10n
        );

        $method = new ReflectionMethod(
            objectOrMethod: LegalBasesSummaryService::class,
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
            objectOrMethod: LegalBasesSummaryService::class,
            method: 'localizeEntityType'
        );
        $method->setAccessible(accessible: true);

        $this->assertSame(expected: 'PERSON', actual: $method->invoke($this->service, 'PERSON'));

    }//end testLocalizeEntityTypeWithoutL10nReturnsRaw()
}//end class
