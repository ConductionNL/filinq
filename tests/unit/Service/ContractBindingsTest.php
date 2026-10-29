<?php

/**
 * Every Kernel contract is bound to a class that implements it.
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

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\Filinq\Service\ContractBindings;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;

/**
 * Nextcloud autowires concrete classes but not interfaces, so a contract that
 * is not bound cannot be injected anywhere.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class ContractBindingsTest extends TestCase {

	/**
	 * Every interface in lib/Kernel/Contracts has a binding.
	 *
	 * @return void
	 */
	public function testEveryContractIsBound(): void {
		$contracts = [];
		foreach (glob(dirname(__DIR__, 3) . '/lib/Kernel/Contracts/*.php') ?: [] as $file) {
			$contracts[] = 'OCA\\Filinq\\Kernel\\Contracts\\' . basename($file, '.php');
		}

		$bound = array_keys(ContractBindings::BINDINGS);
		sort($contracts);
		sort($bound);

		$this->assertNotSame(expected: [], actual: $contracts);
		$this->assertSame(expected: $contracts, actual: $bound);

	}//end testEveryContractIsBound()

	/**
	 * Every binding points at a concrete class that implements its contract.
	 *
	 * @return void
	 */
	public function testEveryBindingImplementsItsContract(): void {
		foreach (ContractBindings::BINDINGS as $contract => $implementation) {
			$this->assertTrue(condition: interface_exists($contract), message: $contract);
			$this->assertTrue(condition: is_subclass_of($implementation, $contract), message: $implementation . ' implements ' . $contract);
		}

	}//end testEveryBindingImplementsItsContract()

	/**
	 * The app bootstrap registers every binding as a service alias.
	 *
	 * @return void
	 */
	public function testTheBootstrapRegistersEveryBinding(): void {
		$context = new class implements IRegistrationContext {
			/**
			 * Alias to target.
			 *
			 * @var array<string, string>
			 */
			public array $aliases = [];

			public function registerService(string $name, callable $factory, bool $shared = true): void {
				unset($name, $factory, $shared);
			}

			public function registerAlias(string $alias, string $target): void {
				unset($alias, $target);
			}

			public function registerServiceAlias(string $alias, string $target): void {
				$this->aliases[$alias] = $target;
			}

			public function registerParameter(string $name, mixed $value): void {
				unset($name, $value);
			}

			public function registerEventListener(string $event, string $listener, int $priority = 0): void {
				unset($event, $listener, $priority);
			}

			/**
			 * Every other registration member, ignored.
			 *
			 * @param string            $name      The member.
			 * @param array<int, mixed> $arguments Its arguments.
			 *
			 * @return void
			 */
			public function __call(string $name, array $arguments): void {
				unset($name, $arguments);
			}
		};

		(new RegistrationBootstrap())->register(context: $context);

		foreach (ContractBindings::BINDINGS as $contract => $implementation) {
			$this->assertSame(expected: $implementation, actual: $context->aliases[$contract] ?? null, message: $contract);
		}

	}//end testTheBootstrapRegistersEveryBinding()
}//end class
