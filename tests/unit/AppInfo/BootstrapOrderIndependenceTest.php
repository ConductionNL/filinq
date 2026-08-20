<?php

/**
 * Guards the bootstrap invariant that keeps DocuDesk independent of app load order.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\AppInfo
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\AppInfo;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * `OC_App::getEnabledApps()` sorts the app list, and
 * `Coordinator::registerApps()` walks that sorted list one app at a time,
 * calling `registerAutoloading()` and then `register()` for each app before
 * moving to the next. `docudesk` sorts before `openregister`, so during
 * DocuDesk's own `register()` the `OCA\OpenRegister\` prefix is NOT yet on the
 * autoloader — on a completely healthy instance with OpenRegister enabled.
 *
 * Two consequences, both fail-silent:
 *
 *   - a `class_exists()` probe answers FALSE, so it is not a test of "is
 *     OpenRegister installed?" but of "have we reached OpenRegister in the
 *     sorted loop yet?" — and the answer is always no;
 *   - an unguarded reference throws `\Error`, which aborts the WHOLE
 *     `register()`. The Coordinator catches it, logs an emergency and carries
 *     on, so the app stays enabled and looks fine while half its wiring never
 *     registered.
 *
 * DocuDesk already fixed this the right way: the one `class_exists()` probe
 * lives in `ObjectEventRegistrar::boot()`, and `boot()` runs only after EVERY
 * app's `register()` has completed, which makes the probe order-independent.
 * That fix is invisible in the code — nothing stops a future edit from moving
 * the probe back into `register()`, or adding an eager `new`/`instanceof`
 * against an OpenRegister class — and the regression would be silent.
 *
 * These tests are that missing enforcement. They are deliberately preferred
 * over the alternative repair (calling `\OC_App::registerAutoloading()` at the
 * top of `register()`): `OC_App` is a private legacy class with no OCP
 * equivalent — `IAppManager` exposes only `loadApp()`, which would BOOT
 * OpenRegister from inside our own `register()`, a worse ordering violation —
 * and both Psalm (`UndefinedClass`) and PHPMD (`StaticAccess`) reject it
 * correctly rather than as false positives. Suppressing two analysers to add a
 * private-API dependency, in order to guard a hazard this app has already
 * eliminated structurally, is a worse trade than pinning the invariant here.
 *
 * @psalm-suppress  PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class BootstrapOrderIndependenceTest extends TestCase {
	/**
	 * Read the source text of one method.
	 *
	 * @param string $class Fully-qualified class name.
	 * @param string $method Method name.
	 *
	 * @return string The method's source, or '' when the file is unreadable.
	 */
	private function methodSource(string $class, string $method): string {
		$reflected = new ReflectionMethod($class, $method);
		$file = $reflected->getFileName();
		if ($file === false) {
			return '';
		}

		$lines = file($file);
		if ($lines === false) {
			return '';
		}

		$start = ($reflected->getStartLine() - 1);
		$end = $reflected->getEndLine();

		return implode('', array_slice($lines, $start, ($end - $start)));
	}//end methodSource()

	/**
	 * No register()-time code may probe for an OpenRegister class.
	 *
	 * A `class_exists()` here is always FALSE, so the guarded branch is dead
	 * and the fallback runs unconditionally — which is exactly how seven fleet
	 * conversions reported success while being inert.
	 *
	 * @param string $class Registrar class whose register() is inspected.
	 *
	 * @return void
	 *
	 * @dataProvider registerTimeRegistrarProvider
	 */
	public function testRegisterTimeCodeDoesNotProbeForOpenRegister(string $class): void {
		$source = $this->methodSource($class, 'register');

		$this->assertStringNotContainsString(
			'class_exists(',
			$source,
			$class . '::register() must not call class_exists(): during register() the '
			. 'OCA\OpenRegister\ prefix is not on the autoloader yet, so the probe is '
			. 'always false and its guarded branch is dead code. Move the work to boot().'
		);

	}//end testRegisterTimeCodeDoesNotProbeForOpenRegister()

	/**
	 * The filtered-listener probe must be reachable only from boot().
	 *
	 * @return void
	 */
	public function testTheFilteredListenerProbeLivesInBootNotRegister(): void {
		$class = 'OCA\\DocuDesk\\AppInfo\\ObjectEventRegistrar';
		$boot = $this->methodSource($class, 'boot');
		$reg = $this->methodSource($class, 'register');
		$needle = 'registerFilteredObjectListener(';

		$this->assertStringContainsString(
			$needle,
			$boot,
			'ObjectEventRegistrar::boot() must be what declares the filtered listener — '
			. 'boot() runs only after every app register() has completed, which is what '
			. 'makes the OpenRegister guard order-independent.'
		);

		$this->assertStringNotContainsString(
			$needle,
			$reg,
			'ObjectEventRegistrar::register() must NOT declare the filtered listener: '
			. 'at register() time the OpenRegister guard is always false and the listener '
			. 'silently falls back to an UNFILTERED registration.'
		);

	}//end testTheFilteredListenerProbeLivesInBootNotRegister()

	/**
	 * The MetricsEngine service must be keyed by a string, not a class constant.
	 *
	 * `Foo::class` on an imported name is a compile-time string and never
	 * autoloads, but writing the OpenRegister FQCN as a literal keeps that true
	 * even if the import is later dropped — and it is what lets an admin open
	 * the settings page with OpenRegister absent.
	 *
	 * @return void
	 */
	public function testTheMetricsEngineIsRegisteredUnderAStringKey(): void {
		$source = $this->methodSource('OCA\\DocuDesk\\AppInfo\\ObservabilityRegistrar', 'register');

		$this->assertStringContainsString(
			"'OCA\\\\OpenRegister\\\\AppHost\\\\Observability\\\\MetricsEngine'",
			$source,
			'ObservabilityRegistrar::register() must key the MetricsEngine service by a '
			. 'STRING literal. Referencing the class itself would resolve the name at '
			. 'register() time, when OpenRegister is not yet autoloadable.'
		);

	}//end testTheMetricsEngineIsRegisteredUnderAStringKey()

	/**
	 * Registrars whose register() runs inside Application::register().
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function registerTimeRegistrarProvider(): array {
		return [
			'ObjectEventRegistrar' => ['OCA\\DocuDesk\\AppInfo\\ObjectEventRegistrar'],
			'SigningEventRegistrar' => ['OCA\\DocuDesk\\AppInfo\\SigningEventRegistrar'],
			'PdfConversionRegistrar' => ['OCA\\DocuDesk\\AppInfo\\PdfConversionRegistrar'],
			'ObservabilityRegistrar' => ['OCA\\DocuDesk\\AppInfo\\ObservabilityRegistrar'],
			'RegistrationBootstrap' => ['OCA\\DocuDesk\\AppInfo\\RegistrationBootstrap'],
		];

	}//end registerTimeRegistrarProvider()
}//end class
