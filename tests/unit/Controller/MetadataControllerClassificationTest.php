<?php

/**
 * Tests for the classification hook on the on-demand enrich endpoint
 *
 * POST api/metadata/enrich gains the same hook as the event path, over the
 * real InboundClassificationService. The body's objectData is the caller's
 * word, so a suggestion is only made for a file the caller can open.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/Classification/InboundClassificationFixture.php';

use OCA\Filinq\Controller\MetadataController;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Tests\Unit\Service\Classification\ClassificationObjectStore;
use OCA\Filinq\Tests\Unit\Service\Classification\InboundClassificationFixture;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The on-demand enrich endpoint offers the document to classification.
 */
class MetadataControllerClassificationTest extends TestCase {
	use InboundClassificationFixture;

	/**
	 * Fresh store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->store = new ClassificationObjectStore();

	}//end setUp()

	/**
	 * Enriching an inbound document the caller can open leaves a suggestion and reports it.
	 *
	 * @return void
	 */
	public function testOnDemandEnrichSuggestsForAReachableFile(): void {
		$response = $this->controller(reachable: [812010])->enrich();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('suggested', $response->getData()['classification']);
		$this->assertCount(1, $this->store->saves);
		$this->assertSame('intake-1', $this->store->saves[0]['objectId']);

	}//end testOnDemandEnrichSuggestsForAReachableFile()

	/**
	 * A file the caller cannot open gets no suggestion.
	 *
	 * @return void
	 */
	public function testOnDemandEnrichDoesNotSuggestForAnotherUsersFile(): void {
		$response = $this->controller(reachable: [])->enrich();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('skipped', $response->getData()['classification']);
		$this->assertSame([], $this->store->saves);

	}//end testOnDemandEnrichDoesNotSuggestForAnotherUsersFile()

	/**
	 * The controller over the real classification service.
	 *
	 * @param int[] $reachable The file ids the caller's folder holds.
	 *
	 * @return MetadataController The controller.
	 */
	private function controller(array $reachable): MetadataController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(['objectId' => 'intake-1', 'register' => 'filinq', 'schema' => 'intakeDocument', 'objectData' => $this->intake()]);

		$metadata = $this->createMock(MetadataService::class);
		$metadata->method('enhanceMetadata')->willReturn([]);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('annemarie');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturnCallback(fn (int $id): ?File => (in_array($id, $reachable, true) === true ? $this->createMock(File::class) : null));
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('annemarie')->willReturn($userFolder);

		return new MetadataController(
			appName: 'filinq',
			request: $request,
			logger: new NullLogger(),
			metadataService: $metadata,
			l10n: $l10n,
			userSession: $session,
			classification: $this->service(),
			rootFolder: $root,
		);

	}//end controller()
}//end class
