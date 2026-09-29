<?php

/**
 * The LibreSign client seam is bound by the bootstrap.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/libresign-signing-provider/tasks.md#task-1.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\Filinq\Service\Signing\LibreSignClient;
use OCA\Filinq\Service\Signing\OcsLibreSignClient;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;

/**
 * Without the alias the container cannot build the LibreSign provider, and
 * with it the factory, so every signing request would 500.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class LibreSignWiringTest extends TestCase {

	/**
	 * The bootstrap binds the client interface to the OCS client.
	 *
	 * @return void
	 */
	public function testTheClientSeamIsBound(): void {
		$context = new class implements IRegistrationContext {
			/**
			 * Alias to target.
			 *
			 * @var array<string, string>
			 */
			public array $aliases = [];

			public function registerService(string $name, callable $factory, bool $shared = true): void {
			}

			public function registerAlias(string $alias, string $target): void {
			}

			public function registerServiceAlias(string $alias, string $target): void {
				$this->aliases[$alias] = $target;
			}

			public function registerParameter(string $name, mixed $value): void {
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

		$this->assertSame(OcsLibreSignClient::class, $context->aliases[LibreSignClient::class] ?? null);
		$this->assertTrue(is_subclass_of(OcsLibreSignClient::class, LibreSignClient::class));

	}//end testTheClientSeamIsBound()
}//end class
