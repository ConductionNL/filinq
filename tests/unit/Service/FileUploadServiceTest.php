<?php

/**
 * Unit tests for FileUploadService
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

use OCA\Filinq\Service\FileUploadService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FileUploadService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FileUploadServiceTest extends TestCase {

	/**
	 * @var FileUploadService
	 */
	private FileUploadService $service;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $mockRootFolder;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockRootFolder = $this->createMock(IRootFolder::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);

		$this->service = new FileUploadService(
			$this->mockLogger,
			$this->mockRootFolder,
			$this->mockUserSession
		);

	}//end setUp()

	/**
	 * Test getCurrentUserId throws when no user logged in
	 *
	 * @return void
	 */
	public function testGetCurrentUserIdThrowsWhenNoUser(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No user is currently logged in.');

		$this->mockUserSession->method('getUser')
			->willReturn(null);

		$this->service->getCurrentUserId();

	}//end testGetCurrentUserIdThrowsWhenNoUser()

	/**
	 * Test getCurrentUserId returns user ID
	 *
	 * @return void
	 */
	public function testGetCurrentUserIdReturnsUserId(): void {
		$mockUser = $this->createMock(IUser::class);
		$mockUser->method('getUID')
			->willReturn('admin');

		$this->mockUserSession->method('getUser')
			->willReturn($mockUser);

		$this->assertEquals('admin', $this->service->getCurrentUserId());

	}//end testGetCurrentUserIdReturnsUserId()

	/**
	 * Test resolveUniqueFileName returns original when no conflict
	 *
	 * @return void
	 */
	public function testResolveUniqueFileNameNoConflict(): void {
		$mockFolder = $this->createMock(Folder::class);
		$mockFolder->method('nodeExists')
			->willReturn(false);

		$result = $this->service->resolveUniqueFileName($mockFolder, 'test.pdf');
		$this->assertEquals('test.pdf', $result);

	}//end testResolveUniqueFileNameNoConflict()

	/**
	 * Test resolveUniqueFileName appends counter on conflict
	 *
	 * @return void
	 */
	public function testResolveUniqueFileNameWithConflict(): void {
		$mockFolder = $this->createMock(Folder::class);
		$mockFolder->method('nodeExists')
			->willReturnMap([
				['test.pdf', true],
				['test_1.pdf', false],
			]);

		$result = $this->service->resolveUniqueFileName($mockFolder, 'test.pdf');
		$this->assertEquals('test_1.pdf', $result);

	}//end testResolveUniqueFileNameWithConflict()

	/**
	 * Test getFilinqFolder throws when no user logged in
	 *
	 * @return void
	 */
	public function testGetFilinqFolderThrowsWhenNoUser(): void {
		$this->expectException(\Exception::class);

		$this->mockUserSession->method('getUser')
			->willReturn(null);

		$this->service->getFilinqFolder();

	}//end testGetFilinqFolderThrowsWhenNoUser()

}//end class
