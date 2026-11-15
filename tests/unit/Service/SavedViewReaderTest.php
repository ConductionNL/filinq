<?php

/**
 * Unit tests for SavedViewReader
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
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SavedViewReader;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A missing view answers null, an empty view answers an empty list, and a
 * resolving view answers the records its query returns.
 *
 * @covers \OCA\Filinq\Service\SavedViewReader
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class SavedViewReaderTest extends TestCase {

	/**
	 * OpenRegister's view service id.
	 *
	 * @var string
	 */
	private const VIEW_SERVICE = 'OCA\\OpenRegister\\Search\\Service\\ViewService';

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * The session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the boundaries.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->objectService = $this->createMock(ObjectService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Build a reader whose container serves the given view service.
	 *
	 * @param object|null $viewService  The view service, or null to make the lookup throw.
	 * @param bool        $openRegister Whether OpenRegister is installed.
	 *
	 * @return SavedViewReader
	 */
	private function reader(?object $viewService, bool $openRegister = true): SavedViewReader {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($openRegister === true ? ['files', 'openregister'] : ['files']);

		$objectService = $this->objectService;
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($viewService, $objectService): object {
				if ($id === 'OCA\OpenRegister\Service\ObjectService') {
					return $objectService;
				}

				if ($id === self::VIEW_SERVICE && $viewService !== null) {
					return $viewService;
				}

				throw new RuntimeException('no service ' . $id);
			}
		);

		return new SavedViewReader(
			new DocumentObjectServiceResolver($container, $appManager),
			$container,
			$appManager,
			$this->userSession,
			$this->logger
		);

	}//end reader()

	/**
	 * A view service that records the arguments it is asked with and returns the given view.
	 *
	 * @param mixed $found The view `find` returns.
	 *
	 * @return object
	 */
	private function viewServiceFinding(mixed $found): object {
		return new class ($found) {
			/**
			 * The arguments of each find call.
			 *
			 * @var array<int, array<int, string>>
			 */
			public array $calls = [];

			/**
			 * Constructor.
			 *
			 * @param mixed $found The view to return.
			 *
			 * @return void
			 */
			public function __construct(private readonly mixed $found) {
			}//end __construct()

			/**
			 * Find a view.
			 *
			 * @param string $view  The view id.
			 * @param string $owner The owner.
			 *
			 * @return mixed
			 */
			public function find(string $view, string $owner): mixed {
				$this->calls[] = [$view, $owner];

				return $this->found;
			}//end find()
		};

	}//end viewServiceFinding()

	/**
	 * A view object carrying the given query.
	 *
	 * @param mixed $query The query.
	 *
	 * @return object
	 */
	private function viewWithQuery(mixed $query): object {
		return new class ($query) {
			/**
			 * Constructor.
			 *
			 * @param mixed $query The query.
			 *
			 * @return void
			 */
			public function __construct(private readonly mixed $query) {
			}//end __construct()

			/**
			 * The view's query.
			 *
			 * @return mixed
			 */
			public function getQuery(): mixed {
				return $this->query;
			}//end getQuery()
		};

	}//end viewWithQuery()

	/**
	 * A resolving view returns its records, read as the session user.
	 *
	 * @return void
	 */
	public function testReturnsTheRecordsOfTheViewReadAsTheSessionUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$query = ['@self' => ['register' => 'filinq', 'schema' => 'case'], 'status' => 'open'];
		$views = $this->viewServiceFinding($this->viewWithQuery($query));
		$this->objectService->expects($this->once())
			->method('searchObjects')
			->with($query)
			->willReturn([['id' => 1], ['id' => 2]]);

		$this->assertSame([['id' => 1], ['id' => 2]], $this->reader($views)->records('open-cases'));
		$this->assertSame([['open-cases', 'alice']], $views->calls);

	}//end testReturnsTheRecordsOfTheViewReadAsTheSessionUser()

	/**
	 * An explicit owner wins over the session user, which is not even asked.
	 *
	 * @return void
	 */
	public function testAnExplicitOwnerIsUsedAsIs(): void {
		$this->userSession->expects($this->never())->method('getUser');
		$views = $this->viewServiceFinding($this->viewWithQuery(['q' => 1]));
		$this->objectService->method('searchObjects')->willReturn([]);

		$this->assertSame([], $this->reader($views)->records('v1', 'bob'));
		$this->assertSame([['v1', 'bob']], $views->calls);

	}//end testAnExplicitOwnerIsUsedAsIs()

	/**
	 * Without a session user the view is read with an empty owner.
	 *
	 * @return void
	 */
	public function testWithoutASessionUserTheOwnerStaysEmpty(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$views = $this->viewServiceFinding($this->viewWithQuery(['q' => 1]));
		$this->objectService->method('searchObjects')->willReturn([['id' => 3]]);

		$this->assertSame([['id' => 3]], $this->reader($views)->records('v1'));
		$this->assertSame([['v1', '']], $views->calls);

	}//end testWithoutASessionUserTheOwnerStaysEmpty()

	/**
	 * A non-array search result is an empty view, not a missing one.
	 *
	 * @return void
	 */
	public function testANonArrayResultIsAnEmptyList(): void {
		$views = $this->viewServiceFinding($this->viewWithQuery(['q' => 1]));
		$this->objectService->method('searchObjects')->willReturn(0);

		$this->assertSame([], $this->reader($views)->records('v1', 'bob'));

	}//end testANonArrayResultIsAnEmptyList()

	/**
	 * An empty view id is not a view.
	 *
	 * @return void
	 */
	public function testAnEmptyViewIdIsNull(): void {
		$this->userSession->expects($this->never())->method('getUser');
		$this->objectService->expects($this->never())->method('searchObjects');

		$this->assertNull($this->reader($this->viewServiceFinding(null))->records(''));

	}//end testAnEmptyViewIdIsNull()

	/**
	 * Without OpenRegister no view resolves and no service is looked up.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterTheViewDoesNotResolve(): void {
		$views = $this->viewServiceFinding($this->viewWithQuery(['q' => 1]));
		$this->objectService->expects($this->never())->method('searchObjects');

		$this->assertNull($this->reader($views, openRegister: false)->records('v1', 'bob'));
		$this->assertSame([], $views->calls);

	}//end testWithoutOpenRegisterTheViewDoesNotResolve()

	/**
	 * A view service that cannot be reached is logged at info and answers null.
	 *
	 * @return void
	 */
	public function testAnUnreachableViewServiceIsLoggedAndNull(): void {
		$this->objectService->expects($this->never())->method('searchObjects');
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'[SavedViewReader] the view does not resolve',
				$this->callback(
					static fn (array $context): bool => $context['view'] === 'v1'
						&& $context['error'] === 'no service ' . self::VIEW_SERVICE
				)
			);

		$this->assertNull($this->reader(null)->records('v1', 'bob'));

	}//end testAnUnreachableViewServiceIsLoggedAndNull()

	/**
	 * A view that is not found, has no query method, or holds a non-array query is missing.
	 *
	 * @return void
	 */
	public function testUnresolvableViewsAreNull(): void {
		$this->objectService->expects($this->never())->method('searchObjects');

		$this->assertNull($this->reader($this->viewServiceFinding(null))->records('v1', 'bob'));
		$this->assertNull($this->reader($this->viewServiceFinding(new \stdClass()))->records('v1', 'bob'));
		$this->assertNull($this->reader($this->viewServiceFinding($this->viewWithQuery('not-a-query')))->records('v1', 'bob'));

	}//end testUnresolvableViewsAreNull()

	/**
	 * A view whose records cannot be read is logged as a warning and answers null.
	 *
	 * @return void
	 */
	public function testARecordReadFailureIsLoggedAndNull(): void {
		$views = $this->viewServiceFinding($this->viewWithQuery(['q' => 1]));
		$this->objectService->method('searchObjects')->willThrowException(new RuntimeException('index offline'));
		$this->logger->expects($this->once())
			->method('warning')
			->with(
				'[SavedViewReader] the view resolved but its records could not be read',
				$this->callback(static fn (array $context): bool => $context['view'] === 'v1' && $context['error'] === 'index offline')
			);

		$this->assertNull($this->reader($views)->records('v1', 'bob'));

	}//end testARecordReadFailureIsLoggedAndNull()
}//end class
