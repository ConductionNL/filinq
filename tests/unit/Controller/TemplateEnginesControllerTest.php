<?php

/**
 * Unit tests for TemplateEnginesController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\TemplateEnginesController;
use OCA\Filinq\Service\Vendor\VendorRenderSources;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * GET /api/templates/engines answers filinq and one engine per active vendor source.
 */
#[CoversClass(TemplateEnginesController::class)]
class TemplateEnginesControllerTest extends TestCase {

	/**
	 * The controller over the given sources.
	 *
	 * @param list<array{id: string, slug: string, name: string}> $sources The active sources.
	 * @param bool                                                $user    Whether a user is signed in.
	 *
	 * @return TemplateEnginesController The controller.
	 */
	private function controller(array $sources, bool $user = true): TemplateEnginesController {
		$vendor = $this->createMock(VendorRenderSources::class);
		$vendor->method('active')->willReturn($sources);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user ? $this->createMock(IUser::class) : null);

		return new TemplateEnginesController(request: $this->createMock(IRequest::class), sources: $vendor, userSession: $session);

	}//end controller()

	/**
	 * Without integriq only filinq is offered.
	 *
	 * @return void
	 */
	public function testWithoutIntegriqOnlyFilinqIsOffered(): void {
		$response = $this->controller(sources: [])->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => [['id' => 'filinq', 'name' => 'Filinq', 'type' => 'filinq']]], $response->getData());

	}//end testWithoutIntegriqOnlyFilinqIsOffered()

	/**
	 * Each active vendor source is one `vendor:<slug>` engine with its display name.
	 *
	 * @return void
	 */
	public function testEachActiveSourceIsAnEngine(): void {
		$response = $this->controller(sources: [['id' => 'src-1', 'slug' => 'smartdocuments', 'name' => 'SmartDocuments']])->index();

		$this->assertSame(
			[
				['id' => 'filinq', 'name' => 'Filinq', 'type' => 'filinq'],
				['id' => 'vendor:smartdocuments', 'name' => 'SmartDocuments', 'type' => 'vendor'],
			],
			$response->getData()['results']
		);

	}//end testEachActiveSourceIsAnEngine()

	/**
	 * Any signed-in user may read the list; without a user it is 401.
	 *
	 * @return void
	 */
	public function testASignedInUserIsNeeded(): void {
		$this->assertSame(401, $this->controller(sources: [], user: false)->index()->getStatus());
		$this->assertNotSame([], (new ReflectionMethod(TemplateEnginesController::class, 'index'))->getAttributes(NoAdminRequired::class));

	}//end testASignedInUserIsNeeded()

	/**
	 * The route is declared before the template id route, so `engines` is never read as an id.
	 *
	 * @return void
	 */
	public function testTheRouteComesBeforeTheTemplateIdRoute(): void {
		$routes = (string) file_get_contents(__DIR__ . '/../../../appinfo/routes.php');
		$engines = strpos($routes, "'templateEngines#index', 'url' => 'api/templates/engines', 'verb' => 'GET'");
		$byId    = strpos($routes, "'templates#show', 'url' => 'api/templates/{id}'");

		$this->assertIsInt($engines);
		$this->assertIsInt($byId);
		$this->assertLessThan($byId, $engines);

	}//end testTheRouteComesBeforeTheTemplateIdRoute()
}//end class
