<?php

/**
 * Unit tests for BasesResolverService's folder and result handling
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use ArrayIterator;
use Closure;
use Exception;
use OCA\Filinq\Service\BasesResolverService;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An upload batch's bases come from the dossiers on the parent folders of its
 * files; failures along the way drop that folder or file, never the batch.
 *
 * @covers \OCA\Filinq\Service\BasesResolverService
 */
class BasesResolverServiceFolderTest extends TestCase {

	/**
	 * The service's logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The user session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * The DI container.
	 *
	 * @var ContainerInterface&MockObject
	 */
	private ContainerInterface&MockObject $container;

	/**
	 * The folder ids findAll was asked about, in order.
	 *
	 * @var list<string>
	 */
	private array $askedFolders = [];

	/**
	 * Set up the mocks, with OpenRegister installed.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->logger      = $this->createMock(LoggerInterface::class);
		$this->rootFolder  = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->container   = $this->createMock(ContainerInterface::class);

	}//end setUp()

	/**
	 * The service under test.
	 *
	 * @param list<string> $apps The installed apps.
	 *
	 * @return BasesResolverService
	 */
	private function service(array $apps = ['openregister']): BasesResolverService {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($apps);

		return new BasesResolverService($this->logger, $this->rootFolder, $this->userSession, $appManager, $this->container);

	}//end service()

	/**
	 * An object service whose findAll answers per folder id through the callback.
	 *
	 * @param Closure $answer Folder id (string) => result.
	 *
	 * @return object
	 */
	private function objectService(Closure $answer): object {
		$this->container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturnCallback(
			function () use ($answer): object {
				return new class($answer, $this) {

					/**
					 * Constructor.
					 *
					 * @param Closure                         $answer The answer callback.
					 * @param BasesResolverServiceFolderTest $test   The test, to record calls.
					 */
					public function __construct(private readonly Closure $answer, private readonly BasesResolverServiceFolderTest $test) {
					}

					/**
					 * Record and answer a findAll call.
					 *
					 * @param array $config The query config.
					 * @param bool  $_rbac  Whether RBAC applies.
					 *
					 * @return mixed
					 */
					public function findAll(array $config = [], bool $_rbac = true): mixed {
						TestCase::assertFalse($_rbac);
						TestCase::assertSame('filinq', $config['filters']['register']);
						TestCase::assertSame('dossier', $config['filters']['schema']);
						$this->test->recordFolder($config['filters']['folder']);

						return ($this->answer)($config['filters']['folder']);
					}
				};
			}
		);

		return $this;

	}//end objectService()

	/**
	 * Record a folder id asked about.
	 *
	 * @param string $folder The folder id.
	 *
	 * @return void
	 */
	public function recordFolder(string $folder): void {
		$this->askedFolders[] = $folder;

	}//end recordFolder()

	/**
	 * Log a user in whose folder maps file ids to parent folder ids.
	 *
	 * @param array<int, int|Exception> $parents File id => parent folder id, or the exception getById throws.
	 *
	 * @return void
	 */
	private function userWithFiles(array $parents): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(function (int $id) use ($parents): array {
			if (isset($parents[$id]) === false) {
				return [];
			}

			if ($parents[$id] instanceof Exception) {
				throw $parents[$id];
			}

			$parent = $this->createMock(Folder::class);
			$parent->method('getId')->willReturn($parents[$id]);
			$node = $this->createMock(Node::class);
			$node->method('getParent')->willReturn($parent);

			return [$node];
		});
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($userFolder);

	}//end userWithFiles()

	/**
	 * Upload batches resolve each file's parent folder once and merge the bases.
	 *
	 * @return void
	 */
	public function testUploadBatchMergesBasesAcrossParentFolders(): void {
		$this->userWithFiles([1 => 10, 2 => 10, 3 => 20, 4 => new RuntimeException('gone')]);
		$this->objectService(static fn (string $folder): array => match ($folder) {
			'10' => ['results' => [['bases' => ['privacy', '', 7, 'economie']], ['bases' => 'not-a-list']]],
			'20' => [['bases' => ['privacy', 'veiligheid']]],
		});

		$bases = $this->service()->resolveBasesForBatch([
			'files' => [['fileId' => 1], ['fileId' => 2], ['fileId' => 0], ['fileId' => 3], ['fileId' => 4], ['fileId' => 99]],
		]);

		$this->assertSame(['privacy', 'economie', 'veiligheid'], $bases);
		$this->assertSame(['10', '20'], $this->askedFolders);

	}//end testUploadBatchMergesBasesAcrossParentFolders()

	/**
	 * Without a logged-in user an upload batch has no bases.
	 *
	 * @return void
	 */
	public function testUploadBatchWithoutUserHasNoBases(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->container->expects($this->never())->method('get');

		$this->assertSame([], $this->service()->resolveBasesForBatch(['files' => [['fileId' => 1]]]));

	}//end testUploadBatchWithoutUserHasNoBases()

	/**
	 * A user folder that cannot be read is logged and yields no bases.
	 *
	 * @return void
	 */
	public function testUnreadableUserFolderHasNoBases(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);
		$this->rootFolder->method('getUserFolder')->willThrowException(new RuntimeException('no storage'));
		$this->logger->expects($this->once())
			->method('debug')
			->with('BasesResolverService: could not get user folder', ['error' => 'no storage']);

		$this->assertSame([], $this->service()->resolveBasesForBatch(['files' => [['fileId' => 1]]]));

	}//end testUnreadableUserFolderHasNoBases()

	/**
	 * Explicit folder batches read entities and Traversable results.
	 *
	 * @return void
	 */
	public function testFolderBatchReadsEntityCandidates(): void {
		$withObject = new class {

			/**
			 * The object payload.
			 *
			 * @return array
			 */
			public function getObject(): array {
				return ['bases' => ['privacy']];
			}
		};
		$withJson   = new class {

			/**
			 * Not an array; falls through to jsonSerialize.
			 *
			 * @return string
			 */
			public function getObject(): string {
				return 'x';
			}

			/**
			 * The serialised payload.
			 *
			 * @return array
			 */
			public function jsonSerialize(): array {
				return ['bases' => ['economie']];
			}
		};

		$this->objectService(static fn (string $folder): ArrayIterator => new ArrayIterator([$withObject, $withJson, new \stdClass(), 'scalar']));

		$this->assertSame(['privacy', 'economie'], $this->service()->resolveBasesForBatch(['folderId' => '5']));
		$this->assertSame(['5'], $this->askedFolders);

	}//end testFolderBatchReadsEntityCandidates()

	/**
	 * A failing findAll is logged and the folder contributes nothing.
	 *
	 * @return void
	 */
	public function testFailingFindAllContributesNothing(): void {
		$this->objectService(static function (string $folder): array {
			throw new Exception('db');
		});
		$this->logger->expects($this->once())
			->method('debug')
			->with('BasesResolverService: findAll failed for folder', ['folderId' => 5, 'error' => 'db']);

		$this->assertSame([], $this->service()->resolveBasesForBatch(['folderId' => 5]));

	}//end testFailingFindAllContributesNothing()

	/**
	 * Scalar results and non-list `results` contribute nothing.
	 *
	 * @return void
	 */
	public function testScalarResultsContributeNothing(): void {
		$this->objectService(static fn (string $folder): mixed => $folder === '5' ? 3 : ['results' => 'none']);

		$this->assertSame([], $this->service()->resolveBasesForBatch(['folderId' => 5]));
		$this->assertSame([], $this->service()->resolveBasesForBatch(['folderId' => 6]));

	}//end testScalarResultsContributeNothing()

	/**
	 * Without OpenRegister, or when the container fails, there are no bases.
	 *
	 * @return void
	 */
	public function testObjectServiceUnavailableHasNoBases(): void {
		$this->assertSame([], $this->service(['files'])->resolveBasesForBatch(['folderId' => 5]));

		$this->container->method('get')->willThrowException(new RuntimeException('not registered'));
		$this->logger->expects($this->once())
			->method('debug')
			->with('BasesResolverService: ObjectService unavailable', ['error' => 'not registered']);

		$this->assertSame([], $this->service()->resolveBasesForBatch(['folderId' => 5]));

	}//end testObjectServiceUnavailableHasNoBases()
}//end class
