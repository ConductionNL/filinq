<?php

/**
 * Unit tests for FileEntityStatsService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FileEntityStatsService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FileEntityStatsService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FileEntityStatsServiceTest extends TestCase {

	/**
	 * @var FileEntityStatsService
	 */
	private FileEntityStatsService $service;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockAppManager = $this->createMock(IAppManager::class);

		$this->service = new FileEntityStatsService(
			$this->mockLogger,
			$this->mockContainer,
			$this->mockAppManager
		);

	}//end setUp()

	/**
	 * Test determineFileStatus returns uploaded for no entities
	 *
	 * @return void
	 */
	public function testDetermineFileStatusReturnsUploadedForNoEntities(): void {
		$this->assertEquals('uploaded', $this->service->determineFileStatus(0, 0));

	}//end testDetermineFileStatusReturnsUploadedForNoEntities()

	/**
	 * Test determineFileStatus returns extracted when entities exist
	 *
	 * @return void
	 */
	public function testDetermineFileStatusReturnsExtracted(): void {
		$this->assertEquals('extracted', $this->service->determineFileStatus(5, 2));

	}//end testDetermineFileStatusReturnsExtracted()

	/**
	 * Test determineFileStatus returns anonymized when all anonymized
	 *
	 * @return void
	 */
	public function testDetermineFileStatusReturnsAnonymized(): void {
		$this->assertEquals('anonymized', $this->service->determineFileStatus(5, 5));

	}//end testDetermineFileStatusReturnsAnonymized()

	/**
	 * Test getEntityStats returns defaults when mapper is null
	 *
	 * @return void
	 */
	public function testGetEntityStatsReturnsDefaultsWhenMapperNull(): void {
		$result = $this->service->getEntityStats(123, null);

		$this->assertEquals(0, $result['entityCount']);
		$this->assertEquals(0, $result['anonymizedCount']);
		$this->assertEquals('uploaded', $result['status']);

	}//end testGetEntityStatsReturnsDefaultsWhenMapperNull()

	/**
	 * Test getFileRiskLevel returns none when service is null
	 *
	 * @return void
	 */
	public function testGetFileRiskLevelReturnsNoneWhenServiceNull(): void {
		$result = $this->service->getFileRiskLevel(123, null);
		$this->assertEquals('none', $result);

	}//end testGetFileRiskLevelReturnsNoneWhenServiceNull()

	/**
	 * Test tryGetEntityRelationMapper returns null when not installed
	 *
	 * @return void
	 */
	public function testTryGetEntityRelationMapperReturnsNullWhenNotInstalled(): void {
		$this->mockAppManager->method('getInstalledApps')
			->willReturn([]);

		$result = $this->service->tryGetEntityRelationMapper();
		$this->assertNull($result);

	}//end testTryGetEntityRelationMapperReturnsNullWhenNotInstalled()

	/**
	 * Test tryGetRiskLevelService returns null when not installed
	 *
	 * @return void
	 */
	public function testTryGetRiskLevelServiceReturnsNullWhenNotInstalled(): void {
		$this->mockAppManager->method('getInstalledApps')
			->willReturn([]);

		$result = $this->service->tryGetRiskLevelService();
		$this->assertNull($result);

	}//end testTryGetRiskLevelServiceReturnsNullWhenNotInstalled()

}//end class
