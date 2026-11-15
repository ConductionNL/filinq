<?php

/**
 * Behaviour tests for FileEntityStatsService
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
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Files\Service\RiskLevelService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Entity counts, anonymised counts, status and risk level per file, read from
 * OpenRegister when it is installed and degrading to defaults when it fails.
 *
 * @covers \OCA\Filinq\Service\FileEntityStatsService
 */
class FileEntityStatsServiceBehaviourTest extends TestCase {

	/**
	 * The service's logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The DI container.
	 *
	 * @var ContainerInterface&MockObject
	 */
	private ContainerInterface&MockObject $container;

	/**
	 * The service under test, with OpenRegister installed.
	 *
	 * @var FileEntityStatsService
	 */
	private FileEntityStatsService $service;

	/**
	 * Set up the service with OpenRegister installed.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logger    = $this->createMock(LoggerInterface::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$appManager      = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['files', 'openregister']);

		$this->service = new FileEntityStatsService($this->logger, $this->container, $appManager);

	}//end setUp()

	/**
	 * A relation whose anonymised flag is the given value.
	 *
	 * @param bool|null $anonymized The flag.
	 *
	 * @return object
	 */
	private function relation(?bool $anonymized): object {
		return new class($anonymized) {

			/**
			 * Constructor.
			 *
			 * @param bool|null $anonymized The flag.
			 */
			public function __construct(private readonly ?bool $anonymized) {
			}

			/**
			 * The anonymised flag.
			 *
			 * @return bool|null
			 */
			public function getAnonymized(): ?bool {
				return $this->anonymized;
			}
		};

	}//end relation()

	/**
	 * With OpenRegister installed both services come from the container.
	 *
	 * @return void
	 */
	public function testServicesAreResolvedFromTheContainer(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$risk   = $this->createMock(RiskLevelService::class);
		$this->container->method('get')->willReturnMap([
			['OCA\OpenRegister\Db\EntityRelationMapper', $mapper],
			['OCA\OpenRegister\Files\Service\RiskLevelService', $risk],
		]);

		$this->assertSame($mapper, $this->service->tryGetEntityRelationMapper());
		$this->assertSame($risk, $this->service->tryGetRiskLevelService());

	}//end testServicesAreResolvedFromTheContainer()

	/**
	 * A container failure is logged as a warning and resolves to null.
	 *
	 * @return void
	 */
	public function testContainerFailuresResolveToNull(): void {
		$this->container->method('get')->willThrowException(new RuntimeException('gone'));
		$this->logger->expects($this->exactly(2))
			->method('warning')
			->willReturnCallback(function (string $message): void {
				$this->assertContains(
					$message,
					['EntityRelationMapper not available: gone', 'RiskLevelService not available: gone']
				);
			});

		$this->assertNull($this->service->tryGetEntityRelationMapper());
		$this->assertNull($this->service->tryGetRiskLevelService());

	}//end testContainerFailuresResolveToNull()

	/**
	 * Some relations anonymised: counts are reported and the file is extracted.
	 *
	 * @return void
	 */
	public function testPartlyAnonymisedFileIsExtracted(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->expects($this->once())
			->method('findByFileId')
			->with(42)
			->willReturn([$this->relation(true), $this->relation(false), $this->relation(null)]);

		$this->assertSame(
			['entityCount' => 3, 'anonymizedCount' => 1, 'status' => 'extracted'],
			$this->service->getEntityStats(42, $mapper)
		);

	}//end testPartlyAnonymisedFileIsExtracted()

	/**
	 * Every relation anonymised: the file is anonymized.
	 *
	 * @return void
	 */
	public function testFullyAnonymisedFileIsAnonymized(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findByFileId')->willReturn([$this->relation(true), $this->relation(true)]);

		$this->assertSame(
			['entityCount' => 2, 'anonymizedCount' => 2, 'status' => 'anonymized'],
			$this->service->getEntityStats(7, $mapper)
		);

	}//end testFullyAnonymisedFileIsAnonymized()

	/**
	 * A failing lookup is logged at debug and the defaults are returned.
	 *
	 * @return void
	 */
	public function testAFailingLookupReturnsTheDefaults(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findByFileId')->willThrowException(new Exception('db down'));
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not fetch entities for file 9: db down');

		$this->assertSame(
			['entityCount' => 0, 'anonymizedCount' => 0, 'status' => 'uploaded'],
			$this->service->getEntityStats(9, $mapper)
		);

	}//end testAFailingLookupReturnsTheDefaults()

	/**
	 * The risk level is passed through, and a failure falls back to none.
	 *
	 * @return void
	 */
	public function testRiskLevelIsReadAndFallsBackToNone(): void {
		$risk = $this->createMock(RiskLevelService::class);
		$risk->method('getRiskLevel')->willReturnCallback(function (int $fileId): string {
			if ($fileId === 1) {
				return 'high';
			}

			throw new Exception('no index');
		});
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not fetch risk level for file 2: no index');

		$this->assertSame('high', $this->service->getFileRiskLevel(1, $risk));
		$this->assertSame('none', $this->service->getFileRiskLevel(2, $risk));

	}//end testRiskLevelIsReadAndFallsBackToNone()
}//end class
