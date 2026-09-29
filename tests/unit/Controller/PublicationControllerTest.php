<?php

/**
 * Unit tests for PublicationController
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\PublicationController;
use OCA\Filinq\Service\Publication\OpenCatalogiPlatform;
use OCA\Filinq\Service\Publication\PublicationAccess;
use OCA\Filinq\Service\Publication\PublicationNotReadyException;
use OCA\Filinq\Service\Publication\PublicationPipelineService;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The HTTP surface of the pipeline.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PublicationControllerTest extends TestCase {

	/**
	 * The controller for a caller who can open file 42 only.
	 *
	 * @param PublicationPipelineService $pipeline The pipeline double
	 * @param array<string, mixed>|null  $record   What the store finds
	 *
	 * @return PublicationController
	 */
	private function controller(PublicationPipelineService $pipeline, ?array $record): PublicationController {
		$store = $this->createMock(PublicationStore::class);
		$store->method('findRecord')->willReturn($record);
		$store->method('listRecords')->willReturn([['uuid' => 'mine', 'documentFileRef' => '42'], ['uuid' => 'theirs', 'documentFileRef' => '99']]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(static fn (int $id): array => ($id === 42) ? ['file'] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn(false);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new PublicationController(
			'filinq',
			$this->createMock(IRequest::class),
			$pipeline,
			$store,
			$this->createMock(OpenCatalogiPlatform::class),
			new PublicationAccess(rootFolder: $root, groups: $groups),
			$session,
			new NullLogger()
		);

	}//end controller()

	/**
	 * Someone else's publication is not found, and nothing happens to it.
	 *
	 * @return void
	 */
	public function testSomeoneElsesPublicationIsNotFound(): void {
		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->expects($this->never())->method('handoff');
		$pipeline->expects($this->never())->method('withdraw');
		$controller = $this->controller(pipeline: $pipeline, record: ['uuid' => 'theirs', 'documentFileRef' => '99']);

		$this->assertSame(404, $controller->show('theirs')->getStatus());
		$this->assertSame(404, $controller->handoff('theirs')->getStatus());
		$this->assertSame(404, $controller->withdraw('theirs')->getStatus());

		$pipeline2 = $this->createMock(PublicationPipelineService::class);
		$pipeline2->method('sync')->willReturnArgument(0);
		$listed = $this->controller(pipeline: $pipeline2, record: null)->index()->getData()['results'];
		$this->assertSame(['mine'], array_column($listed, 'uuid'));

	}//end testSomeoneElsesPublicationIsNotFound()

	/**
	 * A hand-off that is not ready answers 409 with the reasons.
	 *
	 * @return void
	 */
	public function testANotReadyHandoffAnswersWithTheReasons(): void {
		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->method('handoff')->willThrowException(new PublicationNotReadyException(reasons: ['Consent request c-9: an objection was received']));

		$response = $this->controller(pipeline: $pipeline, record: ['uuid' => 'mine', 'documentFileRef' => '42'])->handoff('mine');

		$this->assertSame(409, $response->getStatus());
		$this->assertSame(['Consent request c-9: an objection was received'], $response->getData()['reasons']);

	}//end testANotReadyHandoffAnswersWithTheReasons()

	/**
	 * The log can be written by the pipeline only: no route changes or removes an entry.
	 *
	 * @return void
	 */
	public function testNoRouteChangesTheLog(): void {
		$routes = require __DIR__ . '/../../../appinfo/routes.php';
		$publication = array_values(array_filter($routes['routes'], static fn (array $r): bool => str_starts_with($r['name'], 'publication#')));

		$this->assertCount(9, $publication);
		foreach ($publication as $route) {
			$this->assertStringNotContainsString('log', $route['url']);
			$this->assertNotSame('DELETE', $route['verb']);
			$this->assertTrue(method_exists(PublicationController::class, explode('#', $route['name'])[1]), $route['name']);
		}

	}//end testNoRouteChangesTheLog()
}//end class
