<?php

/**
 * Unit tests for BatchStateService TTL keep-alive behavior
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BatchStateService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests that getBatch() resets TTL on read (keep-alive pattern)
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress  PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class BatchStateServiceKeepAliveTest extends TestCase {

	/**
	 * Mocked ICache
	 *
	 * @var ICache|MockObject
	 */
	private ICache|MockObject $mockCache;

	/**
	 * The BatchStateService under test
	 *
	 * @var BatchStateService
	 */
	private BatchStateService $service;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockCache = $this->createMock(ICache::class);

		$mockCacheFactory = $this->createMock(ICacheFactory::class);
		$mockCacheFactory->method('createDistributed')->willReturn($this->mockCache);

		$mockAppConfig = $this->createMock(IAppConfig::class);
		$mockLogger = $this->createMock(LoggerInterface::class);
		$mockUserSession = $this->createMock(IUserSession::class);
		$mockGroupManager = $this->createMock(IGroupManager::class);

		// No user session in keep-alive tests — ownership check is skipped.
		$mockUserSession->method('getUser')->willReturn(null);

		$this->service = new BatchStateService(
			cacheFactory: $mockCacheFactory,
			appConfig: $mockAppConfig,
			logger: $mockLogger,
			userSession: $mockUserSession,
			groupManager: $mockGroupManager
		);

	}//end setUp()

	/**
	 * Test that getBatch resets TTL by calling cache set on read
	 *
	 * @return void
	 */
	public function testGetBatchResetsTtlOnRead(): void {
		$batchData = json_encode(['batchId' => 'test-123', 'status' => 'review', 'files' => []]);

		$this->mockCache->method('get')->willReturn($batchData);

		// The key assertion: set() must be called with the same data to reset TTL.
		$this->mockCache->expects($this->once())
			->method('set')
			->with(
				$this->stringContains(string: 'test-123'),
				$batchData,
				7200
			);

		$result = $this->service->getBatch('test-123');

		$this->assertNotNull(actual: $result);
		$this->assertEquals(expected: 'test-123', actual: $result['batchId']);

	}//end testGetBatchResetsTtlOnRead()

	/**
	 * Test that getBatch returns null for missing batch without calling set
	 *
	 * @return void
	 */
	public function testGetBatchReturnsNullForMissing(): void {
		$this->mockCache->method('get')->willReturn(null);
		$this->mockCache->expects($this->never())->method('set');

		$result = $this->service->getBatch('nonexistent');

		$this->assertNull(actual: $result);

	}//end testGetBatchReturnsNullForMissing()

	/**
	 * Test that getBatch returns null for invalid JSON without calling set
	 *
	 * @return void
	 */
	public function testGetBatchReturnsNullForInvalidJson(): void {
		$this->mockCache->method('get')->willReturn('not-valid-json');
		$this->mockCache->expects($this->never())->method('set');

		$result = $this->service->getBatch('bad-data');

		$this->assertNull(actual: $result);

	}//end testGetBatchReturnsNullForInvalidJson()
}//end class
