<?php

/**
 * Unit tests for DashboardRegistrar
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Dashboard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Dashboard;

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\Filinq\Dashboard\AnonymizationWidget;
use OCA\Filinq\Dashboard\DashboardRegistrar;
use OCA\Filinq\Dashboard\FileEntitiesWidget;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;

/**
 * Pins that both dashboard widgets are registered, by the Dashboard
 * registrar and through the app's registration bootstrap.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DashboardRegistrarTest extends TestCase {
	/**
	 * The registrar registers exactly the two Filinq widgets.
	 *
	 * @return void
	 */
	public function testRegistersBothWidgets(): void {
		$context = $this->recordingContext();

		(new DashboardRegistrar())->register(context: $context);

		$this->assertSame(expected: [AnonymizationWidget::class, FileEntitiesWidget::class], actual: $context->widgets);

	}//end testRegistersBothWidgets()

	/**
	 * The app's registration bootstrap registers each widget once.
	 *
	 * @return void
	 */
	public function testBootstrapRegistersEachWidgetOnce(): void {
		$context = $this->recordingContext();

		(new RegistrationBootstrap())->register(context: $context);

		$widgets = $context->widgets;
		sort($widgets);
		$this->assertSame(expected: [AnonymizationWidget::class, FileEntitiesWidget::class], actual: $widgets);

	}//end testBootstrapRegistersEachWidgetOnce()

	/**
	 * A registration context that records the dashboard widgets it is given.
	 *
	 * @return IRegistrationContext&object{widgets: list<string>}
	 */
	private function recordingContext(): IRegistrationContext {
		return new class implements IRegistrationContext {
			/**
			 * Registered widget classes, in order.
			 *
			 * @var list<string>
			 */
			public array $widgets = [];

			/**
			 * Ignored.
			 *
			 * @param string $name Ignored.
			 * @param callable $factory Ignored.
			 * @param bool $shared Ignored.
			 *
			 * @return void
			 */
			public function registerService(string $name, callable $factory, bool $shared = true): void {
				unset($name, $factory, $shared);
			}

			/**
			 * Ignored.
			 *
			 * @param string $alias Ignored.
			 * @param string $target Ignored.
			 *
			 * @return void
			 */
			public function registerAlias(string $alias, string $target): void {
				unset($alias, $target);
			}

			/**
			 * Ignored.
			 *
			 * @param string $alias Ignored.
			 * @param string $target Ignored.
			 *
			 * @return void
			 */
			public function registerServiceAlias(string $alias, string $target): void {
				unset($alias, $target);
			}

			/**
			 * Ignored.
			 *
			 * @param string $name Ignored.
			 * @param mixed $value Ignored.
			 *
			 * @return void
			 */
			public function registerParameter(string $name, mixed $value): void {
				unset($name, $value);
			}

			/**
			 * Ignored.
			 *
			 * @param string $event Ignored.
			 * @param string $listener Ignored.
			 * @param int $priority Ignored.
			 *
			 * @return void
			 */
			public function registerEventListener(string $event, string $listener, int $priority = 0): void {
				unset($event, $listener, $priority);
			}

			/**
			 * Records registerDashboardWidget(); ignores every other member.
			 *
			 * @param string            $name      The member.
			 * @param array<int, mixed> $arguments Its arguments.
			 *
			 * @return void
			 */
			public function __call(string $name, array $arguments): void {
				if ($name === 'registerDashboardWidget') {
					$this->widgets[] = (string) $arguments[0];
				}
			}
		};

	}//end recordingContext()
}//end class
