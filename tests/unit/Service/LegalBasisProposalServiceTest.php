<?php

/**
 * Unit tests for LegalBasisProposalService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\LegalBasisProposalService;
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
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class LegalBasisProposalServiceTest extends TestCase {

	/**
	 * Build a service with a config returning the given mapping JSON.
	 *
	 * @param string $mappingJson Raw JSON for the mapping config key.
	 * @param ContainerInterface|null $container Optional container (for mapper resolution).
	 * @param bool $orInstalled Whether OpenRegister appears installed.
	 *
	 * @return LegalBasisProposalService The service under test.
	 */
	private function makeService(string $mappingJson, ?ContainerInterface $container = null, bool $orInstalled = true): LegalBasisProposalService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn($mappingJson);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($orInstalled === true ? ['openregister'] : []);

		if ($container === null) {
			$container = $this->createMock(ContainerInterface::class);
		}

		return new LegalBasisProposalService(
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
	public function testGetSelectableEntityTypesReturnsCuratedList(): void {
		$types = $this->makeService('{}')->getSelectableEntityTypes();

		$this->assertContains('PERSON', $types);
		$this->assertContains('EMAIL', $types);
		$this->assertContains('BSN', $types);
		$this->assertContains('LOCATION', $types);
		// KENTEKEN (Dutch license plate) is selectable/toggleable and carried
		// in the whitelist sent to the detector (enable-kenteken-entity-type).
		$this->assertContains('KENTEKEN', $types);

	}//end testGetSelectableEntityTypesReturnsCuratedList()

	/**
	 * A valid mapping JSON decodes to the expected array.
	 *
	 * @return void
	 */
	public function testGetMappingDecodesValidJson(): void {
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
	public function testGetMappingReturnsEmptyOnMalformedJson(): void {
		$this->assertSame([], $this->makeService('not json at all')->getMapping());

	}//end testGetMappingReturnsEmptyOnMalformedJson()

	/**
	 * Non-string slugs, empty slugs, and non-array values are filtered out.
	 *
	 * @return void
	 */
	public function testGetMappingFiltersInvalidEntries(): void {
		$service = $this->makeService('{"PERSON":["a", 5, ""], "EMAIL":"oops", "BSN":[]}');

		$this->assertSame(['PERSON' => ['a']], $service->getMapping());

	}//end testGetMappingFiltersInvalidEntries()

	/**
	 * Empty mapping short-circuits: no relations are touched.
	 *
	 * @return void
	 */
	public function testApplyProposalsNoopWhenMappingEmpty(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');

		$this->assertSame(0, $this->makeService('{}', $container)->applyProposals(42));

	}//end testApplyProposalsNoopWhenMappingEmpty()

	/**
	 * A relation with empty bases is pre-filled from the mapping.
	 *
	 * @return void
	 */
	public function testApplyProposalsFillsEmptyBases(): void {
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
	public function testApplyProposalsSkipsNonEmptyBases(): void {
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
	public function testApplyProposalsSkipsUnmappedType(): void {
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
	public function testApplyProposalsDoesNotRefreshFilledRelationAfterMappingChange(): void {
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
	public function testGetAvailableBasesIncludesOperatorAddedBase(): void {
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
	public function testGetAvailableBasesHandlesObjectEntityResults(): void {
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
	public function testEnrichEntitiesWithBasesMergesByRelationId(): void {
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
	public function testGetAvailableBasesEmptyWhenOpenRegisterUnavailable(): void {
		$service = $this->makeService('{}', null, false);

		$this->assertSame([], $service->getAvailableBases());

	}//end testGetAvailableBasesEmptyWhenOpenRegisterUnavailable()

	/**
	 * Build a service whose config returns the given JSON for the
	 * enabled-entity-types key and the default for every other key.
	 *
	 * @param string $enabledJson Raw JSON for the enabled-types config key.
	 *
	 * @return LegalBasisProposalService The service under test.
	 */
	private function makeServiceEnabled(string $enabledJson): LegalBasisProposalService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use ($enabledJson): string {
				if ($key === LegalBasisProposalService::ENABLED_TYPES_CONFIG_KEY) {
					return $enabledJson;
				}

				return $default;
			}
		);

		return new LegalBasisProposalService(
			$config,
			$this->createMock(IAppManager::class),
			$this->createMock(ContainerInterface::class),
			$this->createMock(LoggerInterface::class)
		);

	}//end makeServiceEnabled()

	/**
	 * An unset selection means "all types": the full curated list for the UI
	 * and a null whitelist (no detection constraint) for the backend.
	 *
	 * @return void
	 */
	public function testEnabledTypesDefaultToAllWhenUnset(): void {
		$service = $this->makeServiceEnabled('');
		$curated = $service->getSelectableEntityTypes();

		$this->assertSame($curated, $service->getEnabledEntityTypes());
		$this->assertNull($service->getEntityTypeWhitelist());

	}//end testEnabledTypesDefaultToAllWhenUnset()

	/**
	 * A genuine subset (dates disabled) yields a whitelist in curated order
	 * that omits only the disabled type — nothing else is affected.
	 *
	 * @return void
	 */
	public function testWhitelistReturnsSubsetWithoutDisabledType(): void {
		$curated = $this->makeServiceEnabled('')->getSelectableEntityTypes();
		$enabled = array_values(
			array_filter($curated, static fn (string $type): bool => $type !== 'DATE')
		);

		$service = $this->makeServiceEnabled((string)json_encode($enabled));
		$whitelist = $service->getEntityTypeWhitelist();

		$this->assertSame($enabled, $whitelist);
		$this->assertNotContains('DATE', (array)$whitelist);
		$this->assertContains('BSN', (array)$whitelist);
		$this->assertSame($enabled, $service->getEnabledEntityTypes());

	}//end testWhitelistReturnsSubsetWithoutDisabledType()

	/**
	 * When every curated type is enabled the whitelist collapses to null so
	 * the default "detect all" behaviour is preserved.
	 *
	 * @return void
	 */
	public function testWhitelistNullWhenAllTypesEnabled(): void {
		$curated = $this->makeServiceEnabled('')->getSelectableEntityTypes();
		$service = $this->makeServiceEnabled((string)json_encode($curated));

		$this->assertNull($service->getEntityTypeWhitelist());

	}//end testWhitelistNullWhenAllTypesEnabled()

	/**
	 * Unknown/stale ids are dropped and the result keeps curated order,
	 * regardless of the stored order.
	 *
	 * @return void
	 */
	public function testEnabledTypesSanitiseUnknownAndPreserveOrder(): void {
		$curated = $this->makeServiceEnabled('')->getSelectableEntityTypes();
		$service = $this->makeServiceEnabled((string)json_encode(['UFO', 'DATE', 'PERSON']));

		$expected = array_values(
			array_filter($curated, static fn (string $type): bool => in_array($type, ['DATE', 'PERSON'], true))
		);

		$this->assertSame($expected, $service->getEnabledEntityTypes());
		$this->assertSame($expected, $service->getEntityTypeWhitelist());

	}//end testEnabledTypesSanitiseUnknownAndPreserveOrder()

	/**
	 * Malformed JSON and an empty selection both degrade to "all types".
	 *
	 * @return void
	 */
	public function testWhitelistNullOnMalformedOrEmptySelection(): void {
		$this->assertNull($this->makeServiceEnabled('not json')->getEntityTypeWhitelist());
		$this->assertNull($this->makeServiceEnabled('[]')->getEntityTypeWhitelist());

		$malformed = $this->makeServiceEnabled('not json');
		$this->assertSame($malformed->getSelectableEntityTypes(), $malformed->getEnabledEntityTypes());

	}//end testWhitelistNullOnMalformedOrEmptySelection()

	/**
	 * Build a service around a mapper, with the given mapping JSON and logger.
	 *
	 * @param EntityRelationMapper|null $mapper  The mapper the container answers, or null for none.
	 * @param string                    $mapping Raw JSON for the mapping config key.
	 * @param LoggerInterface|null      $logger  The logger, or a silent mock.
	 *
	 * @return LegalBasisProposalService The service under test.
	 */
	private function serviceWithMapper(?EntityRelationMapper $mapper, string $mapping, ?LoggerInterface $logger = null): LegalBasisProposalService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn($mapping);

		$appManager = $this->createMock(IAppManager::class);
		$installed = [];
		if ($mapper !== null) {
			$installed = ['openregister'];
		}

		$appManager->method('getInstalledApps')->willReturn($installed);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($mapper);

		return new LegalBasisProposalService($config, $appManager, $container, $logger ?? $this->createMock(LoggerInterface::class));

	}//end serviceWithMapper()

	/**
	 * Numeric type keys and a mapping that is not an object are dropped.
	 *
	 * @return void
	 */
	public function testGetMappingDropsNumericKeysAndScalars(): void {
		$this->assertSame([], $this->makeService('["a"]')->getMapping());
		$this->assertSame([], $this->makeService('"x"')->getMapping());
		$this->assertSame(['EMAIL' => ['b', 'c']], $this->makeService('{"0":["a"],"EMAIL":[null,"b",true,"c"]}')->getMapping());

	}//end testGetMappingDropsNumericKeysAndScalars()

	/**
	 * Without OpenRegister nothing is proposed, and a failed detection lookup is logged and proposes nothing.
	 *
	 * @return void
	 */
	public function testApplyProposalsWithoutMapperOrDetections(): void {
		$this->assertSame(0, $this->serviceWithMapper(null, '{"PERSON":["a"]}')->applyProposals(7));

		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->with(7)->willThrowException(new \Exception('down'));
		$mapper->expects($this->never())->method('find');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'LegalBasisProposalService: could not load detections for proposal',
			['fileId' => 7, 'error' => 'down']
		);

		$this->assertSame(0, $this->serviceWithMapper($mapper, '{"PERSON":["a"]}', $logger)->applyProposals(7));

	}//end testApplyProposalsWithoutMapperOrDetections()

	/**
	 * Camel-case rows and object rows are read; rows without a type or relation id are skipped;
	 * a failing relation is logged and the others are still filled.
	 *
	 * @return void
	 */
	public function testApplyProposalsReadsEveryRowShapeAndSurvivesAFailure(): void {
		$empty = new EntityRelation();
		$empty->setBases([]);
		$filled = new EntityRelation();
		$filled->setBases(null);

		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturn(
			[
				['relationId' => 1, 'entityType' => 'PERSON'],
				(object) ['relation_id' => 2, 'entity_type' => 'PERSON'],
				['relation_id' => 0, 'entity_type' => 'PERSON'],
				['relation_id' => 4, 'entity_type' => ''],
				['relation_id' => 5, 'entity_type' => 'PERSON'],
			]
		);
		$mapper->method('find')->willReturnCallback(
			static function (int $id) use ($empty, $filled) {
				if ($id === 5) {
					throw new \Exception('gone');
				}

				if ($id === 1) {
					return $empty;
				}

				return $filled;
			}
		);
		$mapper->expects($this->exactly(2))->method('updateDecisionMetadata')->willReturnArgument(0);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'LegalBasisProposalService: failed to pre-fill bases',
			['fileId' => 9, 'relationId' => 5, 'error' => 'gone']
		);

		$this->assertSame(2, $this->serviceWithMapper($mapper, '{"PERSON":["a","b"]}', $logger)->applyProposals(9));

	}//end testApplyProposalsReadsEveryRowShapeAndSurvivesAFailure()

	/**
	 * Enrichment leaves the rows alone without OpenRegister or when the lookup fails, and only
	 * touches rows whose relation id is known.
	 *
	 * @return void
	 */
	public function testEnrichEntitiesWithBasesEdgeCases(): void {
		$rows = [['relation_id' => 1], (object) ['relationId' => 2], ['relation_id' => 3], ['entity_type' => 'X']];
		$this->assertSame($rows, $this->serviceWithMapper(null, '{}')->enrichEntitiesWithBases($rows, 7));

		$failing = $this->createMock(EntityRelationMapper::class);
		$failing->method('findByFileId')->willThrowException(new \Exception('down'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'LegalBasisProposalService: could not load bases for enrichment',
			['fileId' => 7, 'error' => 'down']
		);
		$this->assertSame($rows, $this->serviceWithMapper($failing, '{}', $logger)->enrichEntitiesWithBases($rows, 7));

		$rel1 = new EntityRelation();
		$rel1->setId(1);
		$rel1->setBases(['a']);
		$rel2 = new EntityRelation();
		$rel2->setId(2);
		$rel2->setBases(['b']);
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findByFileId')->willReturn([$rel1, $rel2]);

		$out = $this->serviceWithMapper($mapper, '{}')->enrichEntitiesWithBases($rows, 7);
		$expected = [['relation_id' => 1, 'bases' => ['a']], ['relationId' => 2, 'bases' => ['b']], ['relation_id' => 3], ['entity_type' => 'X']];
		$this->assertSame($expected, $out);

	}//end testEnrichEntitiesWithBasesEdgeCases()
}//end class
