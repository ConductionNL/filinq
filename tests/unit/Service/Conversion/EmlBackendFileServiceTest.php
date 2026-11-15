<?php

/**
 * Unit tests for EmlBackend when OpenRegister's FileService cannot be resolved.
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

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\EmlBackend;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An EML conversion without a resolvable FileService is refused with a typed
 * failure: nothing is anonymised, assembled or written.
 *
 * @covers \OCA\Filinq\Service\Conversion\EmlBackend
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class EmlBackendFileServiceTest extends TestCase {

	/**
	 * The container failure becomes a ConversionFailedException carrying the
	 * cause, its message and one unavailable attempt record.
	 *
	 * @return void
	 */
	public function testAnUnresolvableFileServiceIsRefused(): void {
		$cause = new RuntimeException('no such service');
		$container = $this->createMock(ContainerInterface::class);
		$container->expects(self::once())->method('get')
			->with('OCA\OpenRegister\Service\FileService')
			->willThrowException($cause);

		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects(self::never())->method('assemble');

		$parent = $this->createMock(Folder::class);
		$parent->expects(self::never())->method('newFile');
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('message.eml');
		$source->method('getPath')->willReturn('/admin/files/message.eml');
		$source->method('getParent')->willReturn($parent);

		$backend = new EmlBackend(
			$this->createMock(IAppConfig::class),
			$this->createMock(IAppManager::class),
			$container,
			$assembly,
			$this->createMock(LoggerInterface::class)
		);

		try {
			$backend->convert($source);
			self::fail('Expected an unresolvable FileService to be refused.');
		} catch (ConversionFailedException $e) {
			self::assertSame('EML backend could not resolve OpenRegister FileService: no such service', $e->getMessage());
			self::assertSame($cause, $e->getPrevious());
			$attempts = $e->getAttempts();
			self::assertCount(1, $attempts);
			self::assertSame('eml', $attempts[0]['name']);
			self::assertFalse($attempts[0]['available']);
			self::assertSame('OpenRegister FileService unavailable', $attempts[0]['reason']);
		}
	}//end testAnUnresolvableFileServiceIsRefused()
}//end class
