<?php

/**
 * Characterization tests for MetadataController::enrich()
 *
 * Pins the full response of every enrich path (not authenticated, saved,
 * nothing to enrich, failure) and the user the classification gate runs for.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\MetadataController;
use OCA\Filinq\Kernel\Contracts\InboundClassificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the enrich endpoint.
 *
 * @covers \OCA\Filinq\Controller\MetadataController
 */
class MetadataControllerEnrichTraceTest extends TestCase {

	private const PARAMS = [
		'objectId' => 'doc-7',
		'register' => 'filinq',
		'schema' => 'document',
		'objectData' => ['title' => 'Brief', 'file' => 4411],
	];

	private MetadataServiceInterface&MockObject $metadata;

	private InboundClassificationServiceInterface&MockObject $classification;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->metadata = $this->createMock(MetadataServiceInterface::class);
		$this->classification = $this->createMock(InboundClassificationServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Without a session user the endpoint answers 401 and touches nothing.
	 *
	 * @return void
	 */
	public function testNotAuthenticatedAnswers401AndCallsNoService(): void {
		$this->metadata->expects($this->never())->method('enhanceMetadata');
		$this->metadata->expects($this->never())->method('saveEnrichedMetadata');
		$this->classification->expects($this->never())->method('classify');

		$response = $this->controller(params: self::PARAMS, uid: null)->enrich();

		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testNotAuthenticatedAnswers401AndCallsNoService()

	/**
	 * Enriched metadata is saved under the request's object, register and schema.
	 *
	 * @return void
	 */
	public function testEnrichedMetadataIsSavedAndReported(): void {
		$this->metadata->expects($this->once())->method('enhanceMetadata')
			->with(self::PARAMS['objectData'])
			->willReturn(['language' => 'nl', 'pages' => 2]);
		$this->metadata->expects($this->once())->method('saveEnrichedMetadata')
			->with('doc-7', 'filinq', 'document', ['language' => 'nl', 'pages' => 2])
			->willReturn(['id' => 'doc-7', 'language' => 'nl']);
		$this->classification->expects($this->once())->method('classify')
			->with(self::PARAMS['objectData'], ['id' => 'doc-7', 'register' => 'filinq', 'schema' => 'document'])
			->willReturn(['outcome' => 'suggested']);

		$response = $this->controller(params: self::PARAMS, uid: 'annemarie', reachable: [4411])->enrich();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			[
				'success' => true,
				'enrichedFields' => ['language', 'pages'],
				'object' => ['id' => 'doc-7', 'language' => 'nl'],
				'classification' => 'suggested',
			],
			$response->getData()
		);

	}//end testEnrichedMetadataIsSavedAndReported()

	/**
	 * Nothing to enrich saves nothing and still reports the classification.
	 *
	 * @return void
	 */
	public function testNothingToEnrichSavesNothing(): void {
		$this->metadata->method('enhanceMetadata')->willReturn([]);
		$this->metadata->expects($this->never())->method('saveEnrichedMetadata');
		$this->classification->method('classify')->willReturn(['outcome' => 'suggested']);

		$response = $this->controller(params: self::PARAMS, uid: 'annemarie', reachable: [4411])->enrich();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			['success' => true, 'message' => 'No metadata enrichment needed', 'classification' => 'suggested'],
			$response->getData()
		);

	}//end testNothingToEnrichSavesNothing()

	/**
	 * A forged userId in the request does not choose whose folder the
	 * classification gate looks in: the session user's folder is used.
	 *
	 * @return void
	 */
	public function testForgedUserIdDoesNotChooseTheFolder(): void {
		$this->metadata->method('enhanceMetadata')->willReturn([]);
		$this->classification->expects($this->never())->method('classify');

		$params = self::PARAMS + ['userId' => 'admin', 'requestedBy' => 'admin'];
		$response = $this->controller(params: $params, uid: 'annemarie', reachable: [], forgedReachable: [4411])->enrich();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('skipped', $response->getData()['classification']);

	}//end testForgedUserIdDoesNotChooseTheFolder()

	/**
	 * A failing save answers 500 with the message and logs the error.
	 *
	 * @return void
	 */
	public function testFailureAnswers500AndLogs(): void {
		$this->metadata->method('enhanceMetadata')->willReturn(['language' => 'nl']);
		$this->metadata->method('saveEnrichedMetadata')->willThrowException(new RuntimeException('store down'));
		$this->classification->method('classify')->willReturn(['outcome' => 'suggested']);
		$this->logger->expects($this->once())->method('error')
			->with('Failed to enrich metadata: store down', $this->callback(fn (array $ctx): bool => $ctx['exception'] instanceof RuntimeException));

		$response = $this->controller(params: self::PARAMS, uid: 'annemarie', reachable: [4411])->enrich();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Failed to enrich metadata: store down'], $response->getData());

	}//end testFailureAnswers500AndLogs()

	/**
	 * A missing field answers 400 before any service runs.
	 *
	 * @return void
	 */
	public function testMissingFieldAnswers400First(): void {
		$this->metadata->expects($this->never())->method('enhanceMetadata');

		$params = self::PARAMS;
		unset($params['schema']);
		$response = $this->controller(params: $params, uid: 'annemarie')->enrich();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'schema is required'], $response->getData());

	}//end testMissingFieldAnswers400First()

	/**
	 * The controller with a session for $uid whose folder holds $reachable.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 * @param string|null $uid The session user, or null for none.
	 * @param int[] $reachable File ids in the session user's folder.
	 * @param int[] $forgedReachable File ids in any other user's folder.
	 *
	 * @return MetadataController The controller.
	 */
	private function controller(array $params, ?string $uid, array $reachable = [], array $forgedReachable = []): MetadataController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text, array $args = []): string => vsprintf($text, $args));

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$file = $this->createMock(File::class);
		$file->method('isUpdateable')->willReturn(true);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(
			function (string $owner) use ($uid, $reachable, $forgedReachable, $file): Folder {
				$ids = $forgedReachable;
				if ($owner === $uid) {
					$ids = $reachable;
				}

				$folder = $this->createMock(Folder::class);
				$folder->method('getFirstNodeById')->willReturnCallback(fn (int $id): ?File => (in_array($id, $ids, true) === true ? $file : null));
				return $folder;
			}
		);

		return new MetadataController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			metadataService: $this->metadata,
			l10n: $l10n,
			userSession: $session,
			classification: $this->classification,
			rootFolder: $root,
		);

	}//end controller()
}//end class
