<?php

/**
 * Unit tests for LegalBasisCatalog
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
use OCA\Filinq\Service\LegalBasisCatalog;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The catalog lists the `base` records from OpenRegister as slug/name/description
 * rows, and degrades to nothing when OpenRegister is absent or fails.
 *
 * @covers \OCA\Filinq\Service\LegalBasisCatalog
 */
class LegalBasisCatalogTest extends TestCase {

	/**
	 * The catalog's logger.
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
	 * A catalog whose container serves the given ObjectService.
	 *
	 * @param object|null $objectService The ObjectService, or null when OpenRegister is not installed.
	 *
	 * @return LegalBasisCatalog
	 */
	private function catalogOver(?object $objectService): LegalBasisCatalog {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($objectService === null ? ['files'] : ['files', 'openregister']);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($objectService);

		return new LegalBasisCatalog($appManager, $container, $this->logger);

	}//end catalogOver()

	/**
	 * An ObjectService that returns the given result for the `base` search.
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
	 * Without OpenRegister installed nothing is resolved and the container is not asked.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterThereAreNoBases(): void {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['files']);
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');

		$catalog = new LegalBasisCatalog($appManager, $container, $this->logger);

		$this->assertNull($catalog->resolve('OCA\OpenRegister\Service\ObjectService'));
		$this->assertSame([], $catalog->getAvailableBases());

	}//end testWithoutOpenRegisterThereAreNoBases()

	/**
	 * A container failure is logged with the class name and resolves to null.
	 *
	 * @return void
	 */
	public function testAContainerFailureResolvesToNull(): void {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new Exception('not registered'));

		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'LegalBasisCatalog: OpenRegister service unavailable',
				['class' => 'Some\Service', 'error' => 'not registered']
			);

		$catalog = new LegalBasisCatalog($appManager, $container, $this->logger);

		$this->assertNull($catalog->resolve('Some\Service'));

	}//end testAContainerFailureResolvesToNull()

	/**
	 * A failing search is logged and yields no bases.
	 *
	 * @return void
	 */
	public function testAFailingSearchYieldsNoBases(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willThrowException(new Exception('db down'));

		$this->logger->expects($this->once())
			->method('warning')
			->with('LegalBasisCatalog: failed to load `base` records', ['error' => 'db down']);

		$this->assertSame([], $this->catalogOver($objectService)->getAvailableBases());

	}//end testAFailingSearchYieldsNoBases()

	/**
	 * Rows from a `results` envelope become slug/name/description; incomplete rows are dropped.
	 *
	 * @return void
	 */
	public function testResultsEnvelopeRowsAreMappedAndIncompleteOnesDropped(): void {
		$result = [
			'results' => [
				['@self' => ['slug' => 'privacy'], 'name' => 'Privacy', 'description' => 'Art. 5.1 lid 2 sub e'],
				['@self' => ['slug' => 'economie'], 'name' => 'Economie', 'description' => ['nested']],
				['@self' => ['slug' => ''], 'name' => 'No slug'],
				['@self' => 'not-an-array', 'name' => 'Bad self'],
				['@self' => ['slug' => 'nameless'], 'name' => ['multi']],
				'not-a-row',
			],
		];

		$this->assertSame(
			[
				['slug' => 'privacy', 'name' => 'Privacy', 'description' => 'Art. 5.1 lid 2 sub e'],
				['slug' => 'economie', 'name' => 'Economie', 'description' => ''],
			],
			$this->catalogOver($this->objectServiceReturning($result))->getAvailableBases()
		);

	}//end testResultsEnvelopeRowsAreMappedAndIncompleteOnesDropped()

	/**
	 * ObjectEntity rows are read through jsonSerialize; one that throws is skipped.
	 *
	 * @return void
	 */
	public function testObjectEntityRowsAreSerialisedAndFailuresSkipped(): void {
		$good = $this->createMock(ObjectEntity::class);
		$good->method('jsonSerialize')->willReturn(['@self' => ['slug' => 'veiligheid'], 'name' => 'Veiligheid']);
		$bad = $this->createMock(ObjectEntity::class);
		$bad->method('jsonSerialize')->willThrowException(new RuntimeException('broken'));

		$this->assertSame(
			[['slug' => 'veiligheid', 'name' => 'Veiligheid', 'description' => '']],
			$this->catalogOver($this->objectServiceReturning([$bad, new \stdClass(), $good]))->getAvailableBases()
		);

	}//end testObjectEntityRowsAreSerialisedAndFailuresSkipped()

	/**
	 * A non-iterable search result yields no bases.
	 *
	 * @return void
	 */
	public function testANonIterableResultYieldsNoBases(): void {
		$this->assertSame([], $this->catalogOver($this->objectServiceReturning(3))->getAvailableBases());

	}//end testANonIterableResultYieldsNoBases()
}//end class
