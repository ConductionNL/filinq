<?php

/**
 * Controllers that take their app id from AppIdentity register under the
 * app's own id, whatever the container would have passed in.
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
 * @covers \OCA\Filinq\Controller\ConformanceController
 * @covers \OCA\Filinq\Controller\DocumentProductionController
 * @covers \OCA\Filinq\Controller\EmlPreviewController
 * @covers \OCA\Filinq\Controller\ExtractionController
 * @covers \OCA\Filinq\Controller\FinalDocumentController
 * @covers \OCA\Filinq\Controller\MetadataController
 * @covers \OCA\Filinq\Controller\OcrController
 * @covers \OCA\Filinq\Controller\ScanIntakeController
 * @covers \OCA\Filinq\Controller\ValidationController
 * @covers \OCA\Filinq\Controller\Support\FinalDocumentChanges
 * @covers \OCA\Filinq\Controller\Support\FinalityRules
 * @covers \OCA\Filinq\Controller\Support\MetadataServices
 * @covers \OCA\Filinq\Controller\Support\OcrServices
 * @covers \OCA\Filinq\Controller\Support\ScanSetupServices
 * @uses \OCA\Filinq\Kernel\AppIdentity
 */
final class ControllerAppIdTest extends TestCase {

	/**
	 * The controllers that read the app id from AppIdentity.
	 *
	 * @return array<string, array{class-string<Controller>}>
	 */
	public static function controllers(): array {
		$cases = [];
		foreach (['Conformance', 'DocumentProduction', 'EmlPreview', 'Extraction', 'FinalDocument', 'Metadata', 'Ocr', 'ScanIntake', 'Validation'] as $name) {
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
