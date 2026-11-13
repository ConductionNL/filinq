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
use OCA\Filinq\Controller\Support\PublicationFlow;
use OCA\Filinq\Service\Publication\OpenCatalogiPlatform;
use OCA\Filinq\Service\Publication\PublicationAccess;
use OCA\Filinq\Exception\PublicationNotReadyException;
use OCA\Filinq\Service\Publication\DiwooMissingFields;
use OCA\Filinq\Service\Publication\PublicationPipelineService;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCP\Files\Folder;
use OCP\IAppConfig;
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
	 * @param OpenCatalogiPlatform|null  $platform The platform double
	 * @param bool                       $publisher Whether the caller is in a publisher group
	 * @param array<string, mixed>       $params    The request parameters
	 *
	 * @return PublicationController
	 */
	private function controller(
		PublicationPipelineService $pipeline,
		?array $record,
		?OpenCatalogiPlatform $platform = null,
		bool $publisher = true,
		array $params = []
	): PublicationController {
		$store = $this->createMock(PublicationStore::class);
		$store->method('findRecord')->willReturn($record);
		$store->method('listRecords')->willReturn([['uuid' => 'mine', 'documentFileRef' => '42'], ['uuid' => 'theirs', 'documentFileRef' => '99']]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(static fn (int $id): array => ($id === 42) ? ['file'] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn(false);
		$groups->method('isInGroup')->willReturnCallback(
			static fn (string $uid, string $group): bool => $publisher === true && $group === 'filinq-publishers'
		);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key, $default = null) => ($params[$key] ?? $default));

		return new PublicationController(
			$request,
			new PublicationFlow(pipeline: $pipeline, store: $store),
			($platform ?? $this->createMock(OpenCatalogiPlatform::class)),
			new PublicationAccess(rootFolder: $root, groups: $groups, appConfig: $config),
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
	 * The categories, a new check and a destruction date answer through their routes.
	 *
	 * @return void
	 */
	public function testCategoriesReadinessAndDestructionDate(): void {
		$platform = $this->createMock(OpenCatalogiPlatform::class);
		$platform->method('categories')->willReturn([['code' => 'c_8c840238', 'label' => 'Adviezen']]);
		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->method('evaluate')->willReturn(['uuid' => 'mine', 'status' => 'ready']);
		$pipeline->method('setDestructionDate')->willThrowException(new \InvalidArgumentException('A destruction date needs a date (YYYY-MM-DD) and its source', 400));

		$controller = $this->controller(pipeline: $pipeline, record: ['uuid' => 'mine', 'documentFileRef' => '42'], platform: $platform);

		$this->assertSame('c_8c840238', $controller->categories()->getData()['results'][0]['code']);
		$this->assertSame('ready', $controller->readiness('mine')->getData()['status']);
		$this->assertSame(400, $controller->destructionDate('mine')->getStatus());

		$theirs = $this->controller(pipeline: $pipeline, record: ['uuid' => 'theirs', 'documentFileRef' => '99']);
		$this->assertSame(404, $theirs->readiness('theirs')->getStatus());
		$this->assertSame(404, $theirs->destructionDate('theirs')->getStatus());

	}//end testCategoriesReadinessAndDestructionDate()

	/**
	 * The log can be written by the pipeline only: no route changes or removes an entry.
	 *
	 * @return void
	 */
	public function testNoRouteChangesTheLog(): void {
		$routes = require __DIR__ . '/../../../appinfo/routes.php';
		$publication = array_values(array_filter($routes['routes'], static fn (array $r): bool => str_starts_with($r['name'], 'publication#')));

		// Each route is declared once and served at its /api/v1/ twin too (ApiPathAliasesTest).
		$this->assertCount(expectedCount: 20, haystack: $publication);
		foreach ($publication as $route) {
			$this->assertStringNotContainsString('log', $route['url']);
			$this->assertNotSame('DELETE', $route['verb']);
			$this->assertTrue(method_exists(PublicationController::class, explode('#', $route['name'])[1]), $route['name']);
		}

	}//end testNoRouteChangesTheLog()

	/**
	 * The index carries each record's missing DiWoo fields, and `incomplete`
	 * keeps only the records that miss one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-every-publication-record-says-which-diwoo-fields-it-misses-req-wmw-001
	 */
	public function testTheIndexShowsAndFiltersMissingMetadata(): void {
		$complete = [
			'wooCategory' => 'c_8c840238', 'documentsoort' => 'advies', 'publisher' => 'gm0000',
			'officieleTitel' => 'Advies', 'creatiedatum' => '2025-03-12', 'publicatiedatum' => '2026-11-20',
		];
		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->method('sync')->willReturnCallback(static fn (array $record): array => $record + $complete);

		$all = $this->controller(pipeline: $pipeline, record: null)->index()->getData()['results'];
		$this->assertSame([[]], array_column($all, 'diwooMissing'), 'the caller sees only their own record, and it is complete');

		$incomplete = $this->controller(pipeline: $pipeline, record: null, params: ['incomplete' => 'true'])->index()->getData()['results'];
		$this->assertSame([], $incomplete);

		$bare = $this->createMock(PublicationPipelineService::class);
		$bare->method('sync')->willReturnArgument(0);
		$listed = $this->controller(pipeline: $bare, record: null, params: ['incomplete' => '1'])->index()->getData()['results'];
		$this->assertSame(['mine'], array_column($listed, 'uuid'));
		$this->assertSame(DiwooMissingFields::MANDATORY, $listed[0]['diwooMissing']);

	}//end testTheIndexShowsAndFiltersMissingMetadata()

	/**
	 * A bulk change reaches only records the caller may act on; the others are
	 * reported as skipped and never passed on.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
	 */
	public function testABulkChangeReachesOnlyRecordsTheCallerMayActOn(): void {
		$mine = ['uuid' => 'mine', 'documentFileRef' => '42'];
		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->expects($this->once())->method('updateMetadataMany')
			->with([$mine, $mine], ['publisher' => 'gm0363'], 'anna')
			->willReturn(['updated' => [$mine, $mine], 'skipped' => []]);

		$response = $this->controller(pipeline: $pipeline, record: $mine, params: ['ids' => ['a', 'b', 'a'], 'values' => ['publisher' => 'gm0363']])->bulkMetadata();
		$this->assertSame(200, $response->getStatus());
		$this->assertCount(2, $response->getData()['updated']);

		$theirs = ['uuid' => 'theirs', 'documentFileRef' => '99'];
		$untouched = $this->createMock(PublicationPipelineService::class);
		$untouched->expects($this->once())->method('updateMetadataMany')
			->with([], ['publisher' => 'gm0363'], 'anna')
			->willReturn(['updated' => [], 'skipped' => []]);
		$refused = $this->controller(pipeline: $untouched, record: $theirs, params: ['ids' => ['x'], 'values' => ['publisher' => 'gm0363']])->bulkMetadata();
		$this->assertSame([['uuid' => 'x', 'reason' => 'not found']], $refused->getData()['skipped']);

		$none = $this->createMock(PublicationPipelineService::class);
		$none->expects($this->never())->method('updateMetadataMany');
		$this->assertSame(400, $this->controller(pipeline: $none, record: $mine, params: ['ids' => [], 'values' => ['publisher' => 'x']])->bulkMetadata()->getStatus());

	}//end testABulkChangeReachesOnlyRecordsTheCallerMayActOn()
	/**
	 * A publication starts only for a document the caller may act on: not for a
	 * file outside the caller's folder, not for a reader who is no publisher.
	 * A publisher starts one for a file of hers, as herself.
	 *
	 * @return void
	 */
	public function testAPublicationStartsOnlyWhereTheCallerMayAct(): void {
		$refused = $this->createMock(PublicationPipelineService::class);
		$refused->expects($this->never())->method('create');
		$elsewhere = ['documentFileRef' => '99', 'userId' => 'admin'];

		$this->assertSame(404, $this->controller(pipeline: $refused, record: null, params: $elsewhere)->create()->getStatus());
		$this->assertSame(404, $this->controller(pipeline: $refused, record: null, publisher: false, params: ['documentFileRef' => '42'])->create()->getStatus());

		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->expects($this->once())
			->method('create')
			->with('42', 'document', '', 'anna')
			->willReturn(['uuid' => 'new', 'documentFileRef' => '42']);

		$this->assertSame(201, $this->controller(pipeline: $pipeline, record: null, params: ['documentFileRef' => '42'])->create()->getStatus());

	}//end testAPublicationStartsOnlyWhereTheCallerMayAct()

	/**
	 * A reader who can open the document sees its publication but cannot hand it off.
	 *
	 * @return void
	 */
	public function testAReaderWhoIsNoPublisherCannotAct(): void {
		$record = ['uuid' => 'mine', 'documentFileRef' => '42', 'status' => 'draft'];
		$pipeline = $this->createMock(PublicationPipelineService::class);
		$pipeline->expects($this->never())->method('handoff');
		$pipeline->method('sync')->willReturnArgument(0);
		$controller = $this->controller(pipeline: $pipeline, record: $record, publisher: false);

		$this->assertSame(expected: 200, actual: $controller->show(id: 'mine')->getStatus());
		$this->assertSame(expected: 404, actual: $controller->handoff(id: 'mine')->getStatus());

	}//end testAReaderWhoIsNoPublisherCannotAct()
}//end class
