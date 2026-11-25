<?php

/**
 * Unit tests for DocumentObjectServiceResolver
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * The resolver hands out OpenRegister's ObjectService, also typed as its contract.
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */
class DocumentObjectServiceResolverTest extends TestCase {

	/**
	 * A resolver over a container that holds the given service.
	 *
	 * @param array<int, string> $installed The installed apps.
	 * @param object             $service   What the container answers.
	 *
	 * @return DocumentObjectServiceResolver
	 */
	private function resolver(array $installed, object $service): DocumentObjectServiceResolver {
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($installed);
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($service);

		return new DocumentObjectServiceResolver(container: $container, appManager: $apps);

	}//end resolver()

	/**
	 * The contract is the container's ObjectService.
	 *
	 * @return void
	 */
	public function testTheContractIsTheObjectService(): void {
		$service = $this->createMock(originalClassName: ObjectServiceInterface::class);

		$this->assertSame(expected: $service, actual: $this->resolver(installed: ['openregister'], service: $service)->contract());

	}//end testTheContractIsTheObjectService()

	/**
	 * Without OpenRegister there is no contract either.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterThereIsNoContract(): void {
		$this->expectException(exception: RuntimeException::class);

		$this->resolver(installed: ['files'], service: $this->createMock(originalClassName: ObjectServiceInterface::class))->contract();

	}//end testWithoutOpenRegisterThereIsNoContract()
}//end class
