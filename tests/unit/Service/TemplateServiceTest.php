<?php

/**
 * Unit tests for TemplateService
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

use Exception;
use OCA\DocuDesk\Service\OpenRegisterResolver;
use OCA\DocuDesk\Service\TemplateService;
use OCA\DocuDesk\Service\TemplateVersionService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Unit tests for TemplateService
 *
 * This test class provides testing for the TemplateService functionality
 * including template creation, retrieval, updating, deletion, and validation.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class TemplateServiceTest extends TestCase {

	/**
	 * The TemplateService instance being tested
	 *
	 * @var TemplateService
	 */
	private TemplateService $templateService;

	/**
	 * Mocked ContainerInterface for dependency injection
	 *
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * Mocked IAppManager for app management
	 *
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * Mocked OpenRegisterResolver for register/schema resolution
	 *
	 * @var OpenRegisterResolver|MockObject
	 */
	private OpenRegisterResolver|MockObject $mockRegisterResolver;

	/**
	 * Set up test environment before each test
	 *
	 * This method initializes the test environment by creating mock objects
	 * and setting up the TemplateService instance for testing.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockRegisterResolver = $this->createMock(OpenRegisterResolver::class);
		$mockVersionService = $this->createMock(TemplateVersionService::class);
		$mockUserSession = $this->createMock(IUserSession::class);

		// Default: getInstalledApps returns an empty array.
		$this->mockAppManager->method('getInstalledApps')
			->willReturn([]);

		$this->templateService = new TemplateService(
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockRegisterResolver,
			$mockVersionService,
			$mockUserSession,
			$this->createMock(IAppConfig::class)
		);

	}//end setUp()

	/**
	 * Clean up after each test
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();

	}//end tearDown()

	/**
	 * Helper to set up a TemplateService with OpenRegister available
	 *
	 * Reconfigures the mocks so that getInstalledApps returns openregister
	 * and the container returns the given mock ObjectService.
	 *
	 * @param ObjectService|MockObject $mockObjectService The mock ObjectService
	 *
	 * @return void
	 */
	private function setUpWithOpenRegister(ObjectService|MockObject $mockObjectService): void {
		// Recreate appManager mock that includes openregister.
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockAppManager->method('getInstalledApps')
			->willReturn(['openregister']);

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockContainer->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($mockObjectService);

		$this->templateService = new TemplateService(
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockRegisterResolver,
			$this->createMock(TemplateVersionService::class),
			$this->createMock(IUserSession::class),
			$this->createMock(IAppConfig::class)
		);

	}//end setUpWithOpenRegister()

	/**
	 * Helper to set up a TemplateService with OpenRegister available and a
	 * caller-supplied TemplateVersionService mock.
	 *
	 * Mirrors setUpWithOpenRegister() but injects the given version service so
	 * tests can assert on the version-history side effects of update flows.
	 *
	 * @param ObjectService|MockObject $mockObjectService The mock ObjectService.
	 * @param TemplateVersionService|MockObject $mockVersionService The mock version service.
	 *
	 * @return void
	 */
	private function setUpWithOpenRegisterAndVersionService(
		ObjectService|MockObject $mockObjectService,
		TemplateVersionService|MockObject $mockVersionService,
	): void {
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockAppManager->method('getInstalledApps')
			->willReturn(['openregister']);

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockContainer->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($mockObjectService);

		$this->templateService = new TemplateService(
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockRegisterResolver,
			$mockVersionService,
			$this->createMock(IUserSession::class),
			$this->createMock(IAppConfig::class)
		);

	}//end setUpWithOpenRegisterAndVersionService()

	/**
	 * Test that createTemplate throws when namespace is missing
	 *
	 * @return void
	 */
	public function testCreateTemplateThrowsWhenNamespaceMissing(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Namespace is required');

		$this->templateService->createTemplate([
			'name' => 'Test Template',
			'content' => '<h1>Hello</h1>',
		]);

	}//end testCreateTemplateThrowsWhenNamespaceMissing()

	/**
	 * Test that createTemplate throws when namespace is invalid
	 *
	 * @return void
	 */
	public function testCreateTemplateThrowsWhenNamespaceInvalid(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid namespace');

		$this->mockRegisterResolver->method('validateNamespace')
			->willThrowException(new \Exception('Invalid namespace: must be lowercase alphanumeric only', 400));

		$this->templateService->createTemplate([
			'namespace' => 'INVALID-NS!',
			'name' => 'Test Template',
			'content' => '<h1>Hello</h1>',
		]);

	}//end testCreateTemplateThrowsWhenNamespaceInvalid()

	/**
	 * Test that createTemplate throws when name is missing
	 *
	 * @return void
	 */
	public function testCreateTemplateThrowsWhenNameMissing(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Name is required');

		$this->templateService->createTemplate([
			'namespace' => 'testapp',
			'content' => '<h1>Hello</h1>',
		]);

	}//end testCreateTemplateThrowsWhenNameMissing()

	/**
	 * Test that createTemplate throws when content is missing
	 *
	 * @return void
	 */
	public function testCreateTemplateThrowsWhenContentMissing(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Content is required');

		$this->templateService->createTemplate([
			'namespace' => 'testapp',
			'name' => 'Test Template',
		]);

	}//end testCreateTemplateThrowsWhenContentMissing()

	/**
	 * Test that getObjectService throws when OpenRegister is not installed
	 *
	 * This indirectly tests getObjectService by calling a public method
	 * that depends on it.
	 *
	 * @return void
	 */
	public function testGetTemplatesThrowsWhenOpenRegisterNotAvailable(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister service is not available');

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn([
				'register' => 'reg-1',
				'schema' => 'schema-1',
			]);

		$this->templateService->getTemplates();

	}//end testGetTemplatesThrowsWhenOpenRegisterNotAvailable()

	/**
	 * Test that getTemplates throws when template register/schema not configured
	 *
	 * @return void
	 */
	public function testGetTemplatesThrowsWhenNotConfigured(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Template register/schema not configured');

		// Need OpenRegister available to pass getObjectService check.
		$mockObjectService = $this->createMock(ObjectService::class);
		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willThrowException(new \Exception('Template register/schema not configured', 500));

		$this->templateService->getTemplates();

	}//end testGetTemplatesThrowsWhenNotConfigured()

	/**
	 * Test that getTemplate throws when template not found
	 *
	 * @return void
	 */
	public function testGetTemplateThrowsWhenNotFound(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Template not found');

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->expects($this->once())
			->method('find')
			->willReturn(null);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn([
				'register' => 'reg-1',
				'schema' => 'schema-1',
			]);

		$this->templateService->getTemplate('non-existent-uuid');

	}//end testGetTemplateThrowsWhenNotFound()

	/**
	 * Test createTemplate with valid data succeeds
	 *
	 * @return void
	 */
	public function testCreateTemplateWithValidData(): void {
		$mockEntity = $this->createMock(ObjectEntity::class);
		$mockEntity->method('jsonSerialize')
			->willReturn([
				'id' => 'uuid-123',
				'namespace' => 'testapp',
				'name' => 'Test',
				'content' => '<p>Test</p>',
			]);

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->expects($this->once())
			->method('saveObject')
			->willReturn($mockEntity);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn([
				'register' => 'reg-1',
				'schema' => 'schema-1',
			]);

		$result = $this->templateService->createTemplate([
			'namespace' => 'testapp',
			'name' => 'Test',
			'content' => '<p>Test</p>',
		]);

		$this->assertIsArray($result);
		$this->assertEquals('uuid-123', $result['id']);

	}//end testCreateTemplateWithValidData()

	/**
	 * Test that updateTemplate snapshots the current state into version history
	 * before persisting the new state.
	 *
	 * Regression lock for the template versioning fix: updateTemplate() must
	 * call TemplateVersionService::createVersion() with the *existing* template
	 * state (fetched via getTemplate) before saving the merged update, so the
	 * pre-edit content is recoverable. The snapshot must use the prior state,
	 * not the incoming payload.
	 *
	 * @return void
	 */
	public function testUpdateTemplateSnapshotsExistingStateIntoVersionHistory(): void {
		$existing = [
			'id' => 'uuid-123',
			'namespace' => 'testapp',
			'name' => 'Original',
			'content' => '<p>Original</p>',
		];

		$savedEntity = $this->createMock(ObjectEntity::class);
		$savedEntity->method('jsonSerialize')
			->willReturn([
				'id' => 'uuid-123',
				'namespace' => 'testapp',
				'name' => 'Updated',
				'content' => '<p>Updated</p>',
			]);

		$mockObjectService = $this->createMock(ObjectService::class);
		// find() backs getTemplate(): returns the pre-edit state.
		$mockObjectService->method('find')->willReturn($existing);
		$mockObjectService->expects($this->once())
			->method('saveObject')
			->willReturn($savedEntity);

		$mockVersionService = $this->createMock(TemplateVersionService::class);
		// The version snapshot must capture the EXISTING (pre-edit) state.
		$mockVersionService->expects($this->once())
			->method('createVersion')
			->with(
				$this->equalTo('uuid-123'),
				$this->equalTo($existing),
				$this->anything(),
				$this->anything()
			);

		$this->setUpWithOpenRegisterAndVersionService($mockObjectService, $mockVersionService);

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn([
				'register' => 'reg-1',
				'schema' => 'schema-1',
			]);

		$result = $this->templateService->updateTemplate('uuid-123', [
			'name' => 'Updated',
			'content' => '<p>Updated</p>',
		]);

		$this->assertIsArray($result);
		$this->assertEquals('Updated', $result['name']);

	}//end testUpdateTemplateSnapshotsExistingStateIntoVersionHistory()

	/**
	 * Test deleteTemplate returns true on success
	 *
	 * @return void
	 */
	public function testDeleteTemplateReturnsTrue(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->expects($this->once())
			->method('deleteObject');

		$this->setUpWithOpenRegister($mockObjectService);

		$result = $this->templateService->deleteTemplate('uuid-to-delete');

		$this->assertTrue($result);

	}//end testDeleteTemplateReturnsTrue()

	/**
	 * Test getTemplatesByNamespace delegates to getTemplates with namespace filter
	 *
	 * @return void
	 */
	public function testGetTemplatesByNamespaceDelegatesToGetTemplates(): void {
		$expectedResults = [
			['id' => '1', 'name' => 'Template 1'],
			['id' => '2', 'name' => 'Template 2'],
		];

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->expects($this->once())
			->method('buildSearchQuery')
			->willReturn([]);
		$mockObjectService->expects($this->once())
			->method('searchObjectsPaginated')
			->willReturn(['results' => $expectedResults, 'total' => 2]);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn([
				'register' => 'reg-1',
				'schema' => 'schema-1',
			]);

		$result = $this->templateService->getTemplatesByNamespace('larpingapp');

		$this->assertCount(2, $result);
		$this->assertEquals('Template 1', $result[0]['name']);

	}//end testGetTemplatesByNamespaceDelegatesToGetTemplates()

	/**
	 * Test getTemplatesByNamespace returns empty array when no results key
	 *
	 * @return void
	 */
	public function testGetTemplatesByNamespaceReturnsEmptyWhenNoResults(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->expects($this->once())
			->method('buildSearchQuery')
			->willReturn([]);
		$mockObjectService->expects($this->once())
			->method('searchObjectsPaginated')
			->willReturn(['results' => [], 'total' => 0]);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn([
				'register' => 'reg-1',
				'schema' => 'schema-1',
			]);

		$result = $this->templateService->getTemplatesByNamespace('emptyns');

		$this->assertEmpty($result);

	}//end testGetTemplatesByNamespaceReturnsEmptyWhenNoResults()

}//end class
