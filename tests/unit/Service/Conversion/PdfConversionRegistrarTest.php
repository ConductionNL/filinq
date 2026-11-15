<?php

/**
 * Unit tests for PdfConversionRegistrar.
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

use OCA\Filinq\Service\Conversion\EmlBackend;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\MpdfBackend;
use OCA\Filinq\Service\Conversion\OfficeAppBackend;
use OCA\Filinq\Service\Conversion\PdfConversionRegistrar;
use OCA\Filinq\Service\Conversion\PhpWordBackend;
use OCA\Filinq\Service\Conversion\TcLibPdfBackend;
use OCA\Filinq\Service\PdfConversionService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The PDF conversion service factory and its backend order.
 *
 * @covers \OCA\Filinq\Service\Conversion\PdfConversionRegistrar
 * @uses   \OCA\Filinq\Service\PdfConversionService
 */
class PdfConversionRegistrarTest extends TestCase {

	/**
	 * The factory resolves every backend in cascade order, then the logger.
	 *
	 * @return void
	 */
	public function testRegistersFactoryResolvingBackendsInOrder(): void {
		$factory = null;
		$context = $this->createMock(IRegistrationContext::class);
		$context->expects($this->once())->method('registerService')
			->with(PdfConversionService::class, $this->isInstanceOf(\Closure::class))
			->willReturnCallback(static function (string $name, \Closure $closure) use (&$factory): void {
				$factory = $closure;
			});

		(new PdfConversionRegistrar())->register(context: $context);

		$requested = [];
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(function (string $id) use (&$requested) {
			$requested[] = $id;
			return $this->createMock($id);
		});

		$service = $factory($container);

		$this->assertInstanceOf(PdfConversionService::class, $service);
		$this->assertSame(
			[
				OfficeAppBackend::class,
				LibreOfficeHeadlessBackend::class,
				PhpWordBackend::class,
				TcLibPdfBackend::class,
				MpdfBackend::class,
				EmlBackend::class,
				LoggerInterface::class,
			],
			$requested
		);
	}//end testRegistersFactoryResolvingBackendsInOrder()
}//end class
