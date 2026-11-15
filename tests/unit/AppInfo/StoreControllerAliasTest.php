<?php

/**
 * The store controller alias goes through openregister's LeafAdoption.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\OpenRegister\AppHost\Bootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * bindStoreController() hands Bootstrap::aliasStoreController() one LeafAdoption.
 *
 * The call sits inside a catch (Throwable): a call in the old shape
 * (appId:, controllerNs:) throws there and the store route is silently left
 * unbound, so this test asserts the alias was actually made.
 */
class StoreControllerAliasTest extends TestCase {

	/**
	 * Load the AppHost stub when no real Bootstrap is present.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		if (class_exists(Bootstrap::class) === false) {
			require_once __DIR__ . '/../../stubs/openregister-apphost-bootstrap.stub.php';
		}

		if (property_exists(Bootstrap::class, 'aliased') === false) {
			$this->markTestSkipped('A real OpenRegister Bootstrap is loaded; the recording stub is not.');
		}

		Bootstrap::$aliased = [];

	}//end setUp()

	/**
	 * The alias is made with filinq's app id and controller namespace.
	 *
	 * @return void
	 */
	public function testTheStoreControllerIsAliasedThroughALeafAdoption(): void {
		$bind = new ReflectionMethod(RegistrationBootstrap::class, 'bindStoreController');
		$bind->invoke(new RegistrationBootstrap(), $this->createMock(IRegistrationContext::class));

		$this->assertCount(1, Bootstrap::$aliased);
		$this->assertSame('filinq', Bootstrap::$aliased[0]->appId);
		$this->assertSame('OCA\\Filinq\\Controller', Bootstrap::$aliased[0]->namespace(option: 'controllerNamespace', suffix: 'Controller'));

	}//end testTheStoreControllerIsAliasedThroughALeafAdoption()
}//end class
