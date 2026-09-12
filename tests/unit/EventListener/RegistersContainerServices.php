<?php

/**
 * Test helper: a container mock that answers with registered factories.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

/**
 * Registers services on a per-test container mock.
 *
 * The event listener and the two collaborators it builds take a
 * ContainerInterface. These tests used to install a fake object on the
 * process-wide \OC::$server instead, which never resets, so a fake could leak
 * from one test into the next, and on a half-booted Nextcloud the lookup
 * autowired from scratch until memory ran out. This keeps the registry on the
 * test instance, so nothing leaks and nothing touches the global server.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait RegistersContainerServices {

	/**
	 * Factories the container hands out, keyed by service id.
	 *
	 * @var array<string, callable>
	 */
	private array $containerServices = [];

	/**
	 * Register a factory the container mock will answer with.
	 *
	 * @param string $id The service id.
	 * @param callable $factory Factory returning the service.
	 *
	 * @return void
	 */
	protected function registerService(string $id, callable $factory): void {
		$this->containerServices[$id] = $factory;

	}//end registerService()

	/**
	 * Forget every registered service, so the container resolves nothing.
	 *
	 * @return void
	 */
	protected function forgetServices(): void {
		$this->containerServices = [];

	}//end forgetServices()

	/**
	 * A container mock wired to the registered factories.
	 *
	 * An unknown id throws, which is what both a real PSR-11 container and the
	 * server container do, and what the code under test catches.
	 *
	 * @return ContainerInterface&MockObject
	 */
	protected function containerMock(): ContainerInterface {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $id): mixed {
				if (isset($this->containerServices[$id]) === false) {
					throw new \RuntimeException('Service not registered: ' . $id);
				}

				return ($this->containerServices[$id])();
			}
		);
		$container->method('has')->willReturnCallback(
			fn (string $id): bool => isset($this->containerServices[$id])
		);

		return $container;
	}//end containerMock()
}//end trait
