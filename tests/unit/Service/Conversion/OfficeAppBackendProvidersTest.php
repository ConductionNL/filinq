<?php

/**
 * Unit tests for OfficeAppBackend's provider matching.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\OfficeAppBackend;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * Which inputs the office-app backend accepts, by the registered conversion providers.
 *
 * @covers \OCA\Filinq\Service\Conversion\OfficeAppBackend
 */
class OfficeAppBackendProvidersTest extends TestCase {

	/**
	 * The conversion manager double.
	 *
	 * @var IConversionManager&MockObject
	 */
	private IConversionManager $manager;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->manager = $this->createMock(IConversionManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * A conversion provider double with a source and target MIME.
	 *
	 * @param string $from The source MIME.
	 * @param string $to   The target MIME.
	 *
	 * @return object
	 */
	private static function provider(string $from, string $to): object {
		return new class($from, $to) {
			/**
			 * Constructor.
			 *
			 * @param string $from The source MIME.
			 * @param string $to   The target MIME.
			 */
			public function __construct(private string $from, private string $to) {
			}//end __construct()

			/**
			 * The source MIME.
			 *
			 * @return string
			 */
			public function getFrom(): string {
				return $this->from;
			}//end getFrom()

			/**
			 * The target MIME.
			 *
			 * @return string
			 */
			public function getTo(): string {
				return $this->to;
			}//end getTo()
		};
	}//end provider()

	/**
	 * The backend under test.
	 *
	 * @return OfficeAppBackend
	 */
	private function backend(): OfficeAppBackend {
		return new OfficeAppBackend(
			conversionManager: $this->manager,
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $this->createMock(IUserSession::class),
			appConfig: $this->createMock(IAppConfig::class),
			logger: $this->logger,
		);
	}//end backend()

	/**
	 * A provider from the source MIME to PDF accepts it, and the provider list is read once.
	 *
	 * @return void
	 */
	public function testAProviderToPdfAcceptsItsSourceMime(): void {
		$this->manager->expects(self::once())->method('getProviders')->willReturn(
			[
				self::provider(from: 'application/vnd.ms-excel', to: 'application/pdf'),
				self::provider(from: 'application/msword', to: 'application/pdf'),
			]
		);
		$this->logger->expects(self::never())->method('debug');

		$backend = $this->backend();
		self::assertTrue($backend->canHandle(mimeType: 'application/msword', extension: 'doc'));
		self::assertTrue($backend->canHandle(mimeType: 'application/vnd.ms-excel', extension: 'xls'));
	}//end testAProviderToPdfAcceptsItsSourceMime()

	/**
	 * A provider from the right MIME to another target is declined and the decline is logged.
	 *
	 * @return void
	 */
	public function testAProviderToAnotherTargetIsDeclined(): void {
		$this->manager->method('getProviders')->willReturn(
			[self::provider(from: 'application/msword', to: 'image/png')]
		);
		$this->logger->expects(self::once())->method('debug')->with(
			'[OfficeAppBackend] No registered provider maps this input to PDF',
			['mimeType' => 'application/msword', 'extension' => 'doc']
		);

		self::assertFalse($this->backend()->canHandle(mimeType: 'application/msword', extension: 'doc'));
	}//end testAProviderToAnotherTargetIsDeclined()

	/**
	 * A failing provider list is logged once, treated as empty, and not asked again.
	 *
	 * @return void
	 */
	public function testAFailingProviderListIsTreatedAsEmpty(): void {
		$this->manager->expects(self::once())->method('getProviders')->willThrowException(new RuntimeException('richdocuments offline'));
		$this->logger->expects(self::once())->method('warning')->with(
			'[OfficeAppBackend] IConversionManager::getProviders threw; treating as no providers',
			['exception' => RuntimeException::class, 'message' => 'richdocuments offline']
		);

		$backend = $this->backend();
		self::assertFalse($backend->canHandle(mimeType: 'application/msword', extension: 'doc'));
		self::assertFalse($backend->canHandle(mimeType: 'text/plain', extension: 'txt'));
	}//end testAFailingProviderListIsTreatedAsEmpty()
}//end class
