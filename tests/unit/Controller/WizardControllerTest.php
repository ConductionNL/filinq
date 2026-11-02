<?php

/**
 * Tests for WizardController
 *
 * The real WizardService and its sibling classes over the in-memory store;
 * the template lookup, schema mapper and resolver are doubles.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/Wizard/WizardDoubles.php';

use OCA\Filinq\Controller\WizardController;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\Wizard\WizardAnswers;
use OCA\Filinq\Service\Wizard\WizardConditions;
use OCA\Filinq\Service\Wizard\WizardDefinitionValidator;
use OCA\Filinq\Service\Wizard\WizardRepository;
use OCA\Filinq\Service\Wizard\WizardService;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardFixtures;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * api/wizards.
 */
class WizardControllerTest extends TestCase {

	/**
	 * The store.
	 *
	 * @var WizardObjectStore
	 */
	private WizardObjectStore $store;

	/**
	 * Build a controller whose request carries a body.
	 *
	 * @param array<string, mixed> $body The request parameters.
	 *
	 * @return WizardController The controller.
	 */
	private function controller(array $body=[]): WizardController {
		$this->store ??= new WizardObjectStore();
		$objects = $this->createMock(DocumentObjectServiceResolver::class);
		$objects->method('resolve')->willReturn($this->store);
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => WizardFixtures::TEMPLATE, 'name' => 'Beschikking']);
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($body);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new WizardController(
			appName: 'filinq',
			request: $request,
			wizards: new WizardService(
				repository: new WizardRepository(objectResolver: $objects),
				validator: new WizardDefinitionValidator(),
				templates: $templates,
				resolver: $this->createMock(DataResolverService::class),
				schemas: $this->createMock(SchemaMapper::class),
				answers: new WizardAnswers(conditions: new WizardConditions()),
			),
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class),
		);

	}//end controller()

	/**
	 * Create, read, find for the template, change and remove.
	 *
	 * @return void
	 */
	public function testTheWizardLifecycle(): void {
		$created = $this->controller(body: WizardFixtures::seed() + ['_route' => 'filinq.wizard.create'])->create();
		$this->assertSame(200, $created->getStatus());
		$uuid = $created->getData()['wizard']['uuid'];

		$this->assertSame('Beschikking parkeervergunning, begeleid', $this->controller()->show(id: $uuid)->getData()['name']);
		$this->assertSame($uuid, $this->controller()->forTemplate(id: WizardFixtures::TEMPLATE)->getData()['wizard']['uuid']);
		$this->assertNull($this->controller()->forTemplate(id: 'another')->getData()['wizard']);
		$this->assertSame([$uuid], array_column($this->controller()->index(register: 'filinq', schema: 'dossier')->getData()['results'], 'uuid'));
		$this->assertSame([], $this->controller()->index(register: 'filinq', schema: 'documentContract')->getData()['results']);

		$changed = WizardFixtures::seed();
		$changed['name'] = 'Renamed';
		$this->assertSame('Renamed', $this->controller(body: $changed)->update(id: $uuid)->getData()['wizard']['name']);
		$this->assertArrayNotHasKey('_route', $this->store->rows[$uuid]);

		$this->assertSame(['deleted' => true], $this->controller()->destroy(id: $uuid)->getData());
		$this->assertSame(404, $this->controller()->show(id: $uuid)->getStatus());

	}//end testTheWizardLifecycle()

	/**
	 * Refusals keep their status and per-question errors.
	 *
	 * @return void
	 */
	public function testRefusalsKeepTheirStatusAndErrors(): void {
		$this->controller(body: WizardFixtures::seed())->create();
		$this->assertSame(409, $this->controller(body: WizardFixtures::seed())->create()->getStatus());

		$broken = WizardFixtures::seed();
		$broken['questions'][1]['condition'] = ['questionKey' => 'later', 'operator' => 'answered'];
		$answer = $this->controller(body: $broken)->create();
		$this->assertSame(422, $answer->getStatus());
		$this->assertArrayHasKey('besluit', $answer->getData()['errors']);

		$this->assertSame(404, $this->controller(body: WizardFixtures::seed())->update(id: 'nope')->getStatus());
		$this->assertSame(404, $this->controller()->destroy(id: 'nope')->getStatus());
		$this->assertSame(404, $this->controller()->prefill(id: 'nope', register: 'filinq', schema: 'dossier', objectId: 'd-17')->getStatus());

	}//end testRefusalsKeepTheirStatusAndErrors()

	/**
	 * Every action declares its auth posture: signed-in users, the register decides the rest.
	 *
	 * @return void
	 */
	public function testEveryActionDeclaresNoAdminRequired(): void {
		foreach (['index', 'create', 'show', 'update', 'destroy', 'forTemplate', 'prefill'] as $method) {
			$attributes = (new ReflectionClass(WizardController::class))->getMethod($method)->getAttributes(NoAdminRequired::class);
			$this->assertCount(1, $attributes, $method);
		}

	}//end testEveryActionDeclaresNoAdminRequired()
}//end class
