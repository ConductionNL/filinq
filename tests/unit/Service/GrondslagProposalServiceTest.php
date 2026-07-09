<?php
/**
 * Unit tests for GrondslagProposalService
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

use OCA\DocuDesk\Service\GrondslagProposalService;
use OCA\OpenRegister\Db\EntityRelation;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the entity-type → grondslag proposal service.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class GrondslagProposalServiceTest extends TestCase
{

    /**
     * Build a service with a config returning the given mapping JSON.
     *
     * @param string                  $mappingJson Raw JSON for the mapping config key.
     * @param ContainerInterface|null $container   Optional container (for mapper resolution).
     * @param bool                    $orInstalled Whether OpenRegister appears installed.
     *
     * @return GrondslagProposalService The service under test.
     */
    private function makeService(string $mappingJson, ?ContainerInterface $container=null, bool $orInstalled=true): GrondslagProposalService
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')->willReturn($mappingJson);

        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getInstalledApps')->willReturn($orInstalled === true ? ['openregister'] : []);

        if ($container === null) {
            $container = $this->createMock(ContainerInterface::class);
        }

        return new GrondslagProposalService(
            $config,
            $appManager,
            $container,
            $this->createMock(LoggerInterface::class)
        );

    }//end makeService()


    /**
     * The curated selectable list includes the verified core types.
     *
     * @return void
     */
    public function testGetSelectableEntityTypesReturnsCuratedList(): void
    {
        $types = $this->makeService('{}')->getSelectableEntityTypes();

        $this->assertContains('PERSON', $types);
        $this->assertContains('EMAIL', $types);
        $this->assertContains('BSN', $types);
        $this->assertContains('LOCATION', $types);

    }//end testGetSelectableEntityTypesReturnsCuratedList()


    /**
     * A valid mapping JSON decodes to the expected array.
     *
     * @return void
     */
    public function testGetMappingDecodesValidJson(): void
    {
        $service = $this->makeService('{"PERSON":["uitvoering-publiekrechtelijke-taak"]}');

        $this->assertSame(
            ['PERSON' => ['uitvoering-publiekrechtelijke-taak']],
            $service->getMapping()
        );

    }//end testGetMappingDecodesValidJson()


    /**
     * Malformed JSON yields an empty mapping rather than an error.
     *
     * @return void
     */
    public function testGetMappingReturnsEmptyOnMalformedJson(): void
    {
        $this->assertSame([], $this->makeService('not json at all')->getMapping());

    }//end testGetMappingReturnsEmptyOnMalformedJson()


    /**
     * Non-string slugs, empty slugs, and non-array values are filtered out.
     *
     * @return void
     */
    public function testGetMappingFiltersInvalidEntries(): void
    {
        $service = $this->makeService('{"PERSON":["a", 5, ""], "EMAIL":"oops", "BSN":[]}');

        $this->assertSame(['PERSON' => ['a']], $service->getMapping());

    }//end testGetMappingFiltersInvalidEntries()


    /**
     * Empty mapping short-circuits: no relations are touched.
     *
     * @return void
     */
    public function testApplyProposalsNoopWhenMappingEmpty(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $this->assertSame(0, $this->makeService('{}', $container)->applyProposals(42));

    }//end testApplyProposalsNoopWhenMappingEmpty()


    /**
     * A relation with empty bases is pre-filled from the mapping.
     *
     * @return void
     */
    public function testApplyProposalsFillsEmptyBases(): void
    {
        $relation = new EntityRelation();
        $relation->setBases(null);

        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('findEntitiesForFile')->willReturn(
            [['relation_id' => 1, 'entity_type' => 'PERSON']]
        );
        $mapper->method('find')->with(1)->willReturn($relation);
        $mapper->expects($this->once())
            ->method('updateDecisionMetadata')
            ->with($relation, ['bases' => ['grondslag-a']])
            ->willReturn($relation);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($mapper);

        $service = $this->makeService('{"PERSON":["grondslag-a"]}', $container);

        $this->assertSame(1, $service->applyProposals(7));

    }//end testApplyProposalsFillsEmptyBases()


    /**
     * A relation that already has bases is never overwritten (non-clobber).
     *
     * @return void
     */
    public function testApplyProposalsSkipsNonEmptyBases(): void
    {
        $relation = new EntityRelation();
        $relation->setBases(['operator-chosen']);

        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('findEntitiesForFile')->willReturn(
            [['relation_id' => 1, 'entity_type' => 'PERSON']]
        );
        $mapper->method('find')->with(1)->willReturn($relation);
        $mapper->expects($this->never())->method('updateDecisionMetadata');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($mapper);

        $service = $this->makeService('{"PERSON":["grondslag-a"]}', $container);

        $this->assertSame(0, $service->applyProposals(7));

    }//end testApplyProposalsSkipsNonEmptyBases()


    /**
     * Detected types absent from the mapping are left untouched.
     *
     * @return void
     */
    public function testApplyProposalsSkipsUnmappedType(): void
    {
        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('findEntitiesForFile')->willReturn(
            [['relation_id' => 9, 'entity_type' => 'EMAIL']]
        );
        $mapper->expects($this->never())->method('find');
        $mapper->expects($this->never())->method('updateDecisionMetadata');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($mapper);

        // Mapping only covers PERSON; the detected EMAIL must be skipped.
        $service = $this->makeService('{"PERSON":["grondslag-a"]}', $container);

        $this->assertSame(0, $service->applyProposals(7));

    }//end testApplyProposalsSkipsUnmappedType()


    /**
     * Changing the mapping never refreshes an already-filled relation
     * (no retroactive refresh) — re-running only fills still-empty relations.
     *
     * @return void
     */
    public function testApplyProposalsDoesNotRefreshFilledRelationAfterMappingChange(): void
    {
        // Relation already carries a basis from an earlier mapping.
        $relation = new EntityRelation();
        $relation->setBases(['old-basis']);

        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('findEntitiesForFile')->willReturn(
            [['relation_id' => 1, 'entity_type' => 'PERSON']]
        );
        $mapper->method('find')->with(1)->willReturn($relation);
        $mapper->expects($this->never())->method('updateDecisionMetadata');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($mapper);

        // Mapping now points PERSON at a different basis; the filled relation
        // must stay on 'old-basis'.
        $service = $this->makeService('{"PERSON":["new-basis"]}', $container);

        $this->assertSame(0, $service->applyProposals(7));
        $this->assertSame(['old-basis'], $relation->getBases());

    }//end testApplyProposalsDoesNotRefreshFilledRelationAfterMappingChange()


    /**
     * The available-bases list includes operator-added `base` records.
     *
     * @return void
     */
    public function testGetAvailableBasesIncludesOperatorAddedBase(): void
    {
        $objectService = $this->createMock(ObjectService::class);
        $objectService->method('searchObjectsBySlug')->willReturn(
            [
                ['@self' => ['slug' => 'uitvoering-publiekrechtelijke-taak'], 'name' => 'Uitvoering publiekrechtelijke taak', 'description' => 'Woo art. 5'],
                ['@self' => ['slug' => 'gemeentelijke-verordening'], 'name' => 'Gemeentelijke verordening', 'description' => 'Lokale grondslag'],
                // Malformed entries are skipped.
                ['@self' => ['slug' => ''], 'name' => 'No slug'],
                ['name' => 'No self'],
            ]
        );

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($objectService);

        $bases = $this->makeService('{}', $container)->getAvailableBases();

        $this->assertCount(2, $bases);
        $slugs = array_column($bases, 'slug');
        $this->assertContains('uitvoering-publiekrechtelijke-taak', $slugs);
        $this->assertContains('gemeentelijke-verordening', $slugs);

    }//end testGetAvailableBasesIncludesOperatorAddedBase()


    /**
     * Available bases are extracted when the search returns ObjectEntity
     * objects (the real shape), not just plain arrays — regression guard for
     * the empty-list bug where ObjectEntity results were skipped.
     *
     * @return void
     */
    public function testGetAvailableBasesHandlesObjectEntityResults(): void
    {
        $entity = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $entity->method('jsonSerialize')->willReturn(
            ['@self' => ['slug' => 'persoonsgegevens'], 'name' => 'Persoonsgegevens', 'description' => 'Woo art. 5.1']
        );

        $objectService = $this->createMock(ObjectService::class);
        $objectService->method('searchObjectsBySlug')->willReturn([$entity]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($objectService);

        $bases = $this->makeService('{}', $container)->getAvailableBases();

        $this->assertCount(1, $bases);
        $this->assertSame('persoonsgegevens', $bases[0]['slug']);
        $this->assertSame('Persoonsgegevens', $bases[0]['name']);

    }//end testGetAvailableBasesHandlesObjectEntityResults()


    /**
     * Enrichment merges each relation's bases into the detection rows by
     * relation id, so the review UI can display the proposed grondslag.
     *
     * @return void
     */
    public function testEnrichEntitiesWithBasesMergesByRelationId(): void
    {
        $rel1 = new EntityRelation();
        $rel1->setId(1);
        $rel1->setBases(['persoonsgegevens']);
        $rel2 = new EntityRelation();
        $rel2->setId(2);
        $rel2->setBases(null);

        $mapper = $this->createMock(EntityRelationMapper::class);
        $mapper->method('findByFileId')->with(7)->willReturn([$rel1, $rel2]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($mapper);

        $entities = [
            ['relation_id' => 1, 'entity_type' => 'PERSON'],
            ['relation_id' => 2, 'entity_type' => 'PHONE'],
        ];

        $out = $this->makeService('{}', $container)->enrichEntitiesWithBases($entities, 7);

        $this->assertSame(['persoonsgegevens'], $out[0]['bases']);
        $this->assertNull($out[1]['bases']);

    }//end testEnrichEntitiesWithBasesMergesByRelationId()


    /**
     * Available bases degrade to an empty list when OpenRegister is absent.
     *
     * @return void
     */
    public function testGetAvailableBasesEmptyWhenOpenRegisterUnavailable(): void
    {
        $service = $this->makeService('{}', null, false);

        $this->assertSame([], $service->getAvailableBases());

    }//end testGetAvailableBasesEmptyWhenOpenRegisterUnavailable()


}//end class
