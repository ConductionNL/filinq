<?php

/**
 * Unit tests for BatchPrintJob's guards: missing arguments, an unknown
 * requester, a failing render, and the session it restores.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCA\Filinq\Kernel\Contracts\PrintJobServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * The print job runs as its requester and always hands the session back.
 *
 * @covers \OCA\Filinq\BackgroundJob\BatchPrintJob
 */
class BatchPrintJobArgumentsTest extends TestCase {

	/**
	 * The print job service double.
	 *
	 * @var PrintJobServiceInterface&MockObject
	 */
	private PrintJobServiceInterface&MockObject $printJobs;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * The users made active, in order.
	 *
	 * @var array<int, IUser|null>
	 */
	private array $activeUsers = [];

	/**
	 * The user signed in before the job ran.
	 *
	 * @var IUser
	 */
	private IUser $previous;

	/**
	 * Run the job once with $argument; alice is the only known user.
	 *
	 * @param mixed $argument The job argument.
	 *
	 * @param IUserManager|null $users The user manager; null for the default alice/mallory one.
	 *
	 * @return void
	 */
	private function runWith(mixed $argument, ?IUserManager $users = null): void {
		$this->previous = $this->createMock(originalClassName: IUser::class);
		$alice          = $this->createMock(originalClassName: IUser::class);

		if ($users === null) {
			$users = $this->createMock(originalClassName: IUserManager::class);
			$users->method('get')->willReturnMap([['alice', $alice], ['mallory', null]]);
		}

		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn($this->previous);
		$session->method('setVolatileActiveUser')->willReturnCallback(
			function (?IUser $user): void {
				$this->activeUsers[] = $user;
			}
		);

		$job = new BatchPrintJob(
			time: $this->createMock(originalClassName: ITimeFactory::class),
			printJobSvc: $this->printJobs,
			users: $users,
			userSession: $session,
			logger: $this->logger
		);
		(new ReflectionMethod(objectOrMethod: $job, method: 'run'))->invoke($job, $argument);
	}//end runWith()

	/**
	 * Fresh doubles per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->printJobs = $this->createMock(originalClassName: PrintJobServiceInterface::class);
		$this->logger    = $this->createMock(originalClassName: LoggerInterface::class);
	}//end setUp()

	/**
	 * Arguments without a job or a template.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function incompleteArguments(): array {
		return [
			'no job id'      => [['templateId' => 't']],
			'no template id' => [['jobId' => 'j']],
			'empty ids'      => [['jobId' => '', 'templateId' => '']],
			'nothing'        => [[]],
		];
	}//end incompleteArguments()

	/**
	 * Without a job and a template nothing is read, rendered or switched,
	 * and the arguments are logged.
	 *
	 * @param mixed $argument The incomplete argument.
	 *
	 * @return void
	 */
	#[DataProvider('incompleteArguments')]
	public function testIncompleteArgumentsAreLoggedAndNothingRuns(mixed $argument): void {
		$this->printJobs->expects($this->never())->method('getJob');
		$this->printJobs->expects($this->never())->method('renderJob');
		$this->logger->expects($this->once())
			->method('error')
			->with('BatchPrintJob: missing required arguments', ['argument' => $argument]);

		$this->runWith(argument: $argument);

		$this->assertSame(expected: [], actual: $this->activeUsers);
	}//end testIncompleteArgumentsAreLoggedAndNothingRuns()

	/**
	 * A job whose requester is unknown is not rendered, and the session is
	 * handed back unchanged.
	 *
	 * @return void
	 */
	public function testAnUnknownRequesterIsNotRendered(): void {
		$this->printJobs->method('getJob')->with('j')->willReturn(['requestedBy' => 'mallory']);
		$this->printJobs->expects($this->never())->method('renderJob');
		$this->logger->expects($this->once())
			->method('error')
			->with('BatchPrintJob: the user who asked for the job is not known', ['jobId' => 'j']);

		$this->runWith(argument: ['jobId' => 'j', 'templateId' => 't']);

		$this->assertSame(expected: [$this->previous], actual: $this->activeUsers);
	}//end testAnUnknownRequesterIsNotRendered()

	/**
	 * A job that names no requester is not rendered, and no user is looked up.
	 *
	 * @return void
	 */
	public function testAJobWithoutARequesterIsNotRendered(): void {
		$this->printJobs->expects($this->once())->method('getJob')->with('j')->willReturn(['status' => 'queued']);
		$this->printJobs->expects($this->never())->method('renderJob');
		$this->logger->expects($this->once())
			->method('error')
			->with('BatchPrintJob: the user who asked for the job is not known', ['jobId' => 'j']);

		$users = $this->createMock(originalClassName: IUserManager::class);
		$users->expects($this->never())->method('get');

		$this->runWith(argument: ['jobId' => 'j', 'templateId' => 't'], users: $users);

		$this->assertSame(expected: [$this->previous], actual: $this->activeUsers);
	}//end testAJobWithoutARequesterIsNotRendered()

	/**
	 * The job renders as its requester with its items and options, logs the
	 * counts, and restores the previous user.
	 *
	 * @return void
	 */
	public function testTheJobRendersAsItsRequesterAndRestoresTheSession(): void {
		$this->printJobs->method('getJob')->willReturn(['requestedBy' => 'alice']);
		$this->printJobs->expects($this->once())
			->method('renderJob')
			->with('j', 't', [['data' => ['a' => 1]]], ['duplex' => true])
			->willReturn(['rendered' => 2, 'total' => 3, 'status' => 'queued']);
		$this->logger->expects($this->once())
			->method('info')
			->with('BatchPrintJob rendered 2 of 3 letters', ['jobId' => 'j', 'status' => 'queued']);

		$this->runWith(argument: ['jobId' => 'j', 'templateId' => 't', 'items' => [['data' => ['a' => 1]]], 'options' => ['duplex' => true]]);

		$this->assertCount(expectedCount: 2, haystack: $this->activeUsers);
		$this->assertNotSame(expected: $this->previous, actual: $this->activeUsers[0]);
		$this->assertSame(expected: $this->previous, actual: $this->activeUsers[1]);
	}//end testTheJobRendersAsItsRequesterAndRestoresTheSession()

	/**
	 * A failing render is logged with its message and the session is still
	 * restored.
	 *
	 * @return void
	 */
	public function testAFailingRenderIsLoggedAndTheSessionRestored(): void {
		$this->printJobs->method('getJob')->willReturn(['requestedBy' => 'alice']);
		$this->printJobs->method('renderJob')->willThrowException(new RuntimeException(message: 'template gone'));
		$this->logger->expects($this->once())
			->method('error')
			->with('BatchPrintJob failed: template gone', $this->callback(callback: static fn (array $context): bool => $context['jobId'] === 'j'));

		$this->runWith(argument: ['jobId' => 'j', 'templateId' => 't']);

		$this->assertSame(expected: $this->previous, actual: end($this->activeUsers));
	}//end testAFailingRenderIsLoggedAndTheSessionRestored()
}//end class
