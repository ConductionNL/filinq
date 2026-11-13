<?php

/**
 * Unit tests for BaseLabelResolver
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\BaseLabelResolver;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Base references resolve to the name and toelichting of the `base` object they
 * point at, by slug or by uuid, and fall back to the raw reference otherwise.
 *
 * @covers \OCA\Filinq\Service\BaseLabelResolver
 * @uses   \OCA\Filinq\Service\DossierObjectRepository
 */
class BaseLabelResolverTest extends TestCase {

	/**
	 * The resolver's own logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the logger mock.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Build a resolver over a real repository whose container serves the given ObjectService.
	 *
	 * @param object|null $objectService The ObjectService, or null when OpenRegister is not installed.
	 *
	 * @return BaseLabelResolver
	 */
	private function resolverOver(?object $objectService): BaseLabelResolver {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($objectService === null ? ['files'] : ['files', 'openregister']);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($objectService);

		$repository = new DossierObjectRepository(
			$appManager,
			$container,
			$this->createMock(IRootFolder::class),
			$this->createMock(IUserSession::class),
			$this->createMock(LoggerInterface::class)
		);

		return new BaseLabelResolver($repository, $this->logger);

	}//end resolverOver()

	/**
	 * An ObjectService mock that returns the given result for the `base` lookup.
	 *
	 * @param mixed $result The search result.
	 *
	 * @return ObjectService&MockObject
	 */
	private function objectServiceReturning(mixed $result): ObjectService&MockObject {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'base', [], false, false)
			->willReturn($result);

		return $objectService;

	}//end objectServiceReturning()

	/**
	 * No references, no lookup at all.
	 *
	 * @return void
	 */
	public function testNoReferencesResolveToAnEmptyMapWithoutALookup(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->expects($this->never())->method('searchObjectsBySlug');

		$this->assertSame([], $this->resolverOver($objectService)->resolve([]));

	}//end testNoReferencesResolveToAnEmptyMapWithoutALookup()

	/**
	 * Without OpenRegister every reference is labelled with itself.
	 *
	 * @return void
	 */
	public function testWithoutObjectServiceTheRawReferencesAreShown(): void {
		$this->assertSame(
			[
				'art-5-1-a' => ['name' => 'art-5-1-a', 'description' => ''],
				'uuid-2'    => ['name' => 'uuid-2', 'description' => ''],
			],
			$this->resolverOver(null)->resolve(['art-5-1-a', 'uuid-2'])
		);

	}//end testWithoutObjectServiceTheRawReferencesAreShown()

	/**
	 * Slugs and uuids both resolve, from a `results` envelope, and unknown refs stay raw.
	 *
	 * @return void
	 */
	public function testResolvesBySlugAndByUuidFromAResultsEnvelope(): void {
		$objectService = $this->objectServiceReturning(
			[
				'results' => [
					[
						'name'        => 'Persoonlijke levenssfeer',
						'description' => 'Art. 5.1 lid 2 sub e',
						'@self'       => ['slug' => 'privacy', 'id' => 'uuid-privacy'],
					],
					[
						'name'  => 'Bedrijfsgegevens',
						'@self' => ['slug' => 'bedrijf', 'uuid' => 'uuid-bedrijf'],
					],
				],
			]
		);

		$labels = $this->resolverOver($objectService)->resolve(['privacy', 'uuid-bedrijf', 'unknown']);

		$this->assertSame(
			[
				'privacy'      => ['name' => 'Persoonlijke levenssfeer', 'description' => 'Art. 5.1 lid 2 sub e'],
				'uuid-bedrijf' => ['name' => 'Bedrijfsgegevens', 'description' => ''],
				'unknown'      => ['name' => 'unknown', 'description' => ''],
			],
			$labels
		);

	}//end testResolvesBySlugAndByUuidFromAResultsEnvelope()

	/**
	 * A uuid resolves to its description too, and the `id` wins over `uuid`.
	 *
	 * @return void
	 */
	public function testUuidLookupCarriesTheDescriptionAndPrefersId(): void {
		$objectService = $this->objectServiceReturning(
			[
				[
					'name'        => 'Opsporing',
					'description' => 'Art. 5.1 lid 2 sub c',
					'@self'       => ['id' => 'uuid-a', 'uuid' => 'uuid-b'],
				],
			]
		);

		$labels = $this->resolverOver($objectService)->resolve(['uuid-a', 'uuid-b']);

		$this->assertSame(['name' => 'Opsporing', 'description' => 'Art. 5.1 lid 2 sub c'], $labels['uuid-a']);
		$this->assertSame(['name' => 'uuid-b', 'description' => ''], $labels['uuid-b']);

	}//end testUuidLookupCarriesTheDescriptionAndPrefersId()

	/**
	 * Nameless bases, non-array `@self` blocks and non-array items are skipped.
	 *
	 * @return void
	 */
	public function testMalformedBasesAreIgnored(): void {
		$objectService = $this->objectServiceReturning(
			[
				['name' => '', '@self' => ['slug' => 'nameless']],
				['name' => 'No self', '@self' => 'broken'],
				'not-an-object',
				42,
			]
		);

		$labels = $this->resolverOver($objectService)->resolve(['nameless', 'No self']);

		$this->assertSame(
			[
				'nameless' => ['name' => 'nameless', 'description' => ''],
				'No self'  => ['name' => 'No self', 'description' => ''],
			],
			$labels
		);

	}//end testMalformedBasesAreIgnored()

	/**
	 * ObjectEntity results are read through their jsonSerialize payload.
	 *
	 * @return void
	 */
	public function testObjectEntityResultsAreSerialised(): void {
		$entity = $this->createMock(ObjectEntity::class);
		$entity->expects($this->once())
			->method('jsonSerialize')
			->willReturn(['name' => 'Economische belangen', 'description' => 'Art. 5.1 lid 2 sub b', '@self' => ['slug' => 'economie']]);

		$labels = $this->resolverOver($this->objectServiceReturning([$entity]))->resolve(['economie']);

		$this->assertSame(['economie' => ['name' => 'Economische belangen', 'description' => 'Art. 5.1 lid 2 sub b']], $labels);

	}//end testObjectEntityResultsAreSerialised()

	/**
	 * An ObjectEntity that throws on serialise is skipped, not fatal.
	 *
	 * @return void
	 */
	public function testAnObjectEntityThatFailsToSerialiseIsSkipped(): void {
		$entity = $this->createMock(ObjectEntity::class);
		$entity->expects($this->once())->method('jsonSerialize')->willThrowException(new RuntimeException('broken'));

		$labels = $this->resolverOver($this->objectServiceReturning([$entity]))->resolve(['economie']);

		$this->assertSame(['economie' => ['name' => 'economie', 'description' => '']], $labels);

	}//end testAnObjectEntityThatFailsToSerialiseIsSkipped()

	/**
	 * A non-iterable search result yields no lookups.
	 *
	 * @return void
	 */
	public function testANonIterableResultYieldsRawLabels(): void {
		$labels = $this->resolverOver($this->objectServiceReturning(0))->resolve(['privacy']);

		$this->assertSame(['privacy' => ['name' => 'privacy', 'description' => '']], $labels);

	}//end testANonIterableResultYieldsRawLabels()

	/**
	 * A failing lookup is logged and every ref falls back to itself.
	 *
	 * @return void
	 */
	public function testAFailingLookupIsLoggedAndFallsBackToRawLabels(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willThrowException(new Exception('db down'));

		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'LegalBasesSummaryService: failed to load `base` objects for label resolution',
				['error' => 'db down']
			);

		$labels = $this->resolverOver($objectService)->resolve(['privacy']);

		$this->assertSame(['privacy' => ['name' => 'privacy', 'description' => '']], $labels);

	}//end testAFailingLookupIsLoggedAndFallsBackToRawLabels()

	/**
	 * Grouping dedupes by name, drops nameless entries, keeps the first description and sorts.
	 *
	 * @return void
	 */
	public function testGroupByNameDedupesDropsNamelessAndSorts(): void {
		$resolver = $this->resolverOver(null);

		$grouped = $resolver->groupByName(
			[
				'b1' => ['name' => 'Privacy', 'description' => ''],
				'b2' => ['name' => 'Economie', 'description' => 'eerste'],
				'b3' => ['name' => 'Privacy', 'description' => 'toelichting'],
				'b4' => ['name' => '', 'description' => 'zonder naam'],
				'b5' => ['name' => 'Economie', 'description' => 'tweede'],
			]
		);

		$this->assertSame(
			[
				['name' => 'Economie', 'description' => 'eerste'],
				['name' => 'Privacy', 'description' => 'toelichting'],
			],
			$grouped
		);

	}//end testGroupByNameDedupesDropsNamelessAndSorts()

	/**
	 * Grouping nothing yields nothing.
	 *
	 * @return void
	 */
	public function testGroupByNameOfNothingIsEmpty(): void {
		$this->assertSame([], $this->resolverOver(null)->groupByName([]));

	}//end testGroupByNameOfNothingIsEmpty()
}//end class
