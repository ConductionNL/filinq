<?php

/**
 * The signing, intake, publication, anonymisation and settings controllers
 * take their app id from AppIdentity and register under the app's own id.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude test of controller construction, no behaviour of its own
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * @covers \OCA\Filinq\Controller\AnonymizationController
 * @covers \OCA\Filinq\Controller\ContractController
 * @covers \OCA\Filinq\Controller\IntakeController
 * @covers \OCA\Filinq\Controller\PublicationController
 * @covers \OCA\Filinq\Controller\SettingsController
 * @covers \OCA\Filinq\Controller\SigningController
 * @covers \OCA\Filinq\Controller\SigningFolderController
 * @covers \OCA\Filinq\Controller\SubjectErasureController
 * @covers \OCA\Filinq\Controller\Support\AnonymizationServices
 * @covers \OCA\Filinq\Controller\Support\AppConfiguration
 * @covers \OCA\Filinq\Controller\Support\ContractTerms
 * @covers \OCA\Filinq\Controller\Support\IntakeHandling
 * @covers \OCA\Filinq\Controller\Support\PublicationFlow
 * @covers \OCA\Filinq\Controller\Support\SettingsBackends
 * @covers \OCA\Filinq\Controller\Support\SigningServices
 * @uses \OCA\Filinq\Kernel\AppIdentity
 */
final class GuardedControllerAppIdTest extends TestCase {

	/**
	 * The controllers that read the app id from AppIdentity.
	 *
	 * @return array<string, array{class-string<Controller>}>
	 */
	public static function controllers(): array {
		$cases = [];
		foreach (['Anonymization', 'Contract', 'Intake', 'Publication', 'Settings', 'Signing', 'SigningFolder', 'SubjectErasure'] as $name) {
			$cases[$name] = ['OCA\\Filinq\\Controller\\' . $name . 'Controller'];
		}
		return $cases;
	}

	/**
	 * @dataProvider controllers
	 *
	 * @param class-string<Controller> $class The controller.
	 */
	public function testRegistersUnderTheAppId(string $class): void {
		$args = [];
		foreach ((new ReflectionClass($class))->getConstructor()->getParameters() as $parameter) {
			$args[] = $this->double($parameter->getType());
		}
		$controller = new $class(...$args);

		$appName = new ReflectionProperty(Controller::class, 'appName');
		$this->assertSame(AppIdentity::APP_ID, $appName->getValue($controller));
		$this->assertSame('filinq', AppIdentity::APP_ID);
	}

	/**
	 * A double for one constructor parameter: a mock of an interface, or a
	 * bundle built from mocks of its own collaborators.
	 *
	 * @param ReflectionNamedType $type The parameter type.
	 *
	 * @return object The double.
	 */
	private function double(ReflectionNamedType $type): object {
		$name = $type->getName();
		$reflection = new ReflectionClass($name);
		if ($reflection->isInterface() === true || $reflection->isAbstract() === true || $name === IRequest::class) {
			return $this->createMock($name);
		}
		$args = [];
		foreach ($reflection->getConstructor()->getParameters() as $parameter) {
			$args[] = $this->createMock($parameter->getType()->getName());
		}
		return $reflection->newInstanceArgs($args);
	}
}
