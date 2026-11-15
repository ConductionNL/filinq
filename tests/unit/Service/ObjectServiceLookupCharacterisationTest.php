<?php

/**
 * Unit tests for how five services look up OpenRegister's ObjectService
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\ListReferenceResolver;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Pins getObjectService(): the container's ObjectService while OpenRegister
 * is installed, a RuntimeException otherwise.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ObjectServiceLookupCharacterisationTest extends TestCase {

	/**
	 * The services that look up the ObjectService themselves.
	 *
	 * @return array<string,array{0:class-string}>
	 */
	public static function services(): array {
		return [
			'correspondence'   => [CorrespondenceService::class],
			'data resolver'    => [DataResolverService::class],
			'list reference'   => [ListReferenceResolver::class],
			'template slug'    => [TemplateSlugResolver::class],
			'template version' => [TemplateVersionService::class],
		];

	}//end services()

	/**
	 * With OpenRegister installed the container's ObjectService is returned.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testInstalledOpenRegisterAnswersTheObjectService(string $class): void {
		$objectService = $this->createMock(ObjectService::class);
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->once())->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($objectService);

		$this->assertSame($objectService, $this->lookup($this->serviceWith($class, $container), ['files', 'openregister']));

	}//end testInstalledOpenRegisterAnswersTheObjectService()

	/**
	 * Without OpenRegister the lookup refuses and never touches the container.
	 *
	 * @param class-string $class The service under test.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testMissingOpenRegisterThrows(string $class): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister service is not available.');
		$this->lookup($this->serviceWith($class, $container), ['files', 'OpenRegister']);

	}//end testMissingOpenRegisterThrows()

	/**
	 * Build the service with only its container wired in.
	 *
	 * @param class-string       $class     The service under test.
	 * @param ContainerInterface $container The container.
	 *
	 * @return object The service.
	 */
	private function serviceWith(string $class, ContainerInterface $container): object {
		$service = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(function (ContainerInterface $container): void {
			$this->container = $container;
		})->call($service, $container);

		return $service;

	}//end serviceWith()

	/**
	 * Wire the installed apps in and call the private getObjectService().
	 *
	 * @param object             $service The service under test.
	 * @param array<int, string> $apps    The installed app ids.
	 *
	 * @return mixed What the lookup returns.
	 */
	private function lookup(object $service, array $apps): mixed {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($apps);
		(function (IAppManager $appManager): void {
			$this->appManager = $appManager;
		})->call($service, $appManager);

		return (new ReflectionClass($service))->getMethod('getObjectService')->invoke($service);

	}//end lookup()

}//end class
