<?php

/**
 * Unit tests for OfficeAppBackend
 *
 * Covers isAvailable() paths, canHandle() routing, and convert()
 * delegation to IConversionManager (all mocked; no real Office app needed).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-11
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\Conversion\OfficeAppBackend;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for OfficeAppBackend
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OfficeAppBackendTest extends TestCase {

	/**
	 * Conversion manager mock.
	 *
	 * @var IConversionManager|MockObject
	 */
	private IConversionManager|MockObject $conversionManager;

	/**
	 * Root folder mock.
	 *
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $rootFolder;

	/**
	 * User session mock.
	 *
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $userSession;

	/**
	 * App config mock.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $appConfig;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * Backend under test.
	 *
	 * @var OfficeAppBackend
	 */
	private OfficeAppBackend $backend;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->conversionManager = $this->createMock(originalClassName: IConversionManager::class);
		$this->rootFolder = $this->createMock(originalClassName: IRootFolder::class);
		$this->userSession = $this->createMock(originalClassName: IUserSession::class);
		$this->appConfig = $this->createMock(originalClassName: IAppConfig::class);
		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);

		$this->backend = new OfficeAppBackend(
			conversionManager: $this->conversionManager,
			rootFolder: $this->rootFolder,
			userSession: $this->userSession,
			appConfig: $this->appConfig,
			logger: $this->logger,
		);

	}//end setUp()

	/**
	 * Test that name() returns the stable identifier 'office_app'
	 *
	 * @return void
	 */
	public function testNameReturnsOfficeApp(): void {
		$this->assertSame(expected: 'office_app', actual: $this->backend->name());

	}//end testNameReturnsOfficeApp()

	/**
	 * Test that isAvailable() returns false when tenant flag is 'false'
	 *
	 * @return void
	 */
	public function testIsUnavailableWhenFlagFalse(): void {
		$this->appConfig->method('getValueString')->willReturn('false');
		$this->assertFalse(condition: $this->backend->isAvailable());

	}//end testIsUnavailableWhenFlagFalse()

	/**
	 * Test that isAvailable() returns false when conversionManager is null
	 *
	 * @return void
	 */
	public function testIsUnavailableWhenManagerNull(): void {
		$this->appConfig->method('getValueString')->willReturn('true');

		$backend = new OfficeAppBackend(
			conversionManager: null,
			rootFolder: $this->rootFolder,
			userSession: $this->userSession,
			appConfig: $this->appConfig,
			logger: $this->logger,
		);

		$this->assertFalse(condition: $backend->isAvailable());

	}//end testIsUnavailableWhenManagerNull()

	/**
	 * Test that isAvailable() returns false when hasProviders() returns false
	 *
	 * @return void
	 */
	public function testIsUnavailableWhenNoProviders(): void {
		$this->appConfig->method('getValueString')->willReturn('true');
		$this->conversionManager->method('hasProviders')->willReturn(false);

		$this->assertFalse(condition: $this->backend->isAvailable());

	}//end testIsUnavailableWhenNoProviders()

	/**
	 * Test that isAvailable() returns true when flag is enabled and providers exist
	 *
	 * @return void
	 */
	public function testIsAvailableWhenProvidersExist(): void {
		$this->appConfig->method('getValueString')->willReturn('true');
		$this->conversionManager->method('hasProviders')->willReturn(true);

		$this->assertTrue(condition: $this->backend->isAvailable());

	}//end testIsAvailableWhenProvidersExist()

	/**
	 * Test that canHandle() returns false when no provider matches the source MIME
	 *
	 * @return void
	 */
	public function testCannotHandleWhenNoProviderMatchesMime(): void {
		$this->conversionManager->method('getProviders')->willReturn([]);

		$this->assertFalse(
			condition: $this->backend->canHandle(mimeType: 'text/plain', extension: 'txt')
		);

	}//end testCannotHandleWhenNoProviderMatchesMime()

	/**
	 * Test that canHandle() returns false when conversionManager is null
	 *
	 * @return void
	 */
	public function testCannotHandleWhenManagerNull(): void {
		$backend = new OfficeAppBackend(
			conversionManager: null,
			rootFolder: $this->rootFolder,
			userSession: $this->userSession,
			appConfig: $this->appConfig,
			logger: $this->logger,
		);

		$this->assertFalse(condition: $backend->canHandle(mimeType: 'text/html', extension: 'html'));

	}//end testCannotHandleWhenManagerNull()

	/**
	 * Test that convert() throws when there is no active user session
	 *
	 * @return void
	 */
	public function testConvertThrowsWithoutUserSession(): void {
		$this->userSession->method('getUser')->willReturn(null);

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('doc.docx');
		$source->method('getParent')->willReturn($this->createMock(originalClassName: Folder::class));

		$this->expectException(exception: ConversionFailedException::class);
		$this->backend->convert(source: $source);

	}//end testConvertThrowsWithoutUserSession()

	/**
	 * Test that convert() throws ConversionFailedException when the manager throws
	 *
	 * @return void
	 */
	public function testConvertThrowsWhenManagerThrows(): void {
		$user = $this->createMock(originalClassName: IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('nodeExists')->willReturn(false);
		$folder->method('getPath')->willReturn('/admin/files');

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('doc.docx');
		$source->method('getParent')->willReturn($folder);

		$this->conversionManager
			->method('convert')
			->willThrowException(new RuntimeException('Office app error'));

		$this->expectException(exception: ConversionFailedException::class);
		$this->backend->convert(source: $source);

	}//end testConvertThrowsWhenManagerThrows()

	/**
	 * Test that isAvailable() returns false when hasProviders() throws
	 *
	 * @return void
	 */
	public function testIsUnavailableWhenHasProvidersThrows(): void {
		$this->appConfig->method('getValueString')->willReturn('true');
		$this->conversionManager
			->method('hasProviders')
			->willThrowException(new RuntimeException('provider error'));

		$this->assertFalse(condition: $this->backend->isAvailable());

	}//end testIsUnavailableWhenHasProvidersThrows()
}//end class
