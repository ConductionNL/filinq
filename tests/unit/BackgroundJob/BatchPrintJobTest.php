<?php

/**
 * Unit tests for BatchPrintJob
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

require_once __DIR__ . '/../Service/PrintJobDoubles.php';

use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Kernel\PrintJobRequest;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PrintJobService;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Tests\Unit\Service\PrintJobDoubles;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A large batch is rendered by the background job into the same job.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class BatchPrintJobTest extends TestCase {
	use PrintJobDoubles;

	/**
	 * The dispatched argument of a large batch renders into its job.
	 *
	 * @return void
	 */
	public function testTheQueuedBatchIsRenderedIntoItsJob(): void {
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturn('%PDF');
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 't', 'content' => '']);
		$jobList = $this->createMock(IJobList::class);
		$dispatched = null;
		$jobList->method('add')->willReturnCallback(
			static function (string $class, mixed $argument) use (&$dispatched): void {
				$dispatched = $argument;
			}
		);
		$service = new PrintJobService(
			$pdf,
			$templates,
			$this->createMock(DataResolverService::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			new JobQueue(jobList: $jobList),
			$this->createMock(LoggerInterface::class)
		);

		$created = $service->createBatchJob(request: new PrintJobRequest(templateId: 't'), items: array_fill(0, 12, ['data' => []]), userId: 'u');
		$this->assertSame('rendering', $created['status']);

		$job = $this->batchPrintJob(service: $service, session: $this->session(), logger: $this->createMock(LoggerInterface::class));
		// The QueuedJob stub's execute() does nothing, so run() is called the
		// way the real QueuedJob::start() calls it.
		(new \ReflectionMethod($job, 'run'))->invoke($job, $dispatched);

		$stored = $this->rows[$created['jobId']];
		$this->assertSame('queued', $stored['status']);
		$this->assertSame(12, $stored['rendered']);
		$this->assertCount(12, $this->stored);

	}//end testTheQueuedBatchIsRenderedIntoItsJob()

	/**
	 * A job with a template nobody can load is failed, not left rendering.
	 *
	 * @return void
	 */
	public function testAMissingTemplateFailsTheJob(): void {
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willThrowException(new \Exception('gone'));
		$service = new PrintJobService(
			$this->createMock(PdfService::class),
			$templates,
			$this->createMock(DataResolverService::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			new JobQueue(jobList: $this->createMock(IJobList::class)),
			$this->createMock(LoggerInterface::class)
		);
		$this->rows['j'] = ['uuid' => 'j', 'status' => 'rendering', 'requestedBy' => 'u', 'total' => 1];

		$job = $this->batchPrintJob(service: $service, session: $this->session(), logger: $this->createMock(LoggerInterface::class));
		(new \ReflectionMethod($job, 'run'))->invoke($job, ['jobId' => 'j', 'templateId' => 't', 'items' => [['data' => []]], 'options' => []]);

		$this->assertSame('failed', $this->rows['j']['status']);
		$this->assertSame('Template not found', $this->rows['j']['statusDetails']);

	}//end testAMissingTemplateFailsTheJob()

	/**
	 * The letters' data is resolved as the user who asked for the job, and the
	 * session is left as it was found.
	 *
	 * @return void
	 */
	public function testTheDataIsResolvedAsTheRequester(): void {
		$session = $this->session();
		$resolvedAs = [];
		$resolver = $this->createMock(DataResolverService::class);
		$resolver->method('resolve')->willReturnCallback(
			static function () use ($session, &$resolvedAs): array {
				$resolvedAs[] = $session->getUser()?->getUID();
				return ['data' => [], 'errors' => [], 'warnings' => []];
			}
		);
		$service = $this->service(resolver: $resolver);
		$this->rows['j'] = ['uuid' => 'j', 'status' => 'rendering', 'requestedBy' => 'u', 'total' => 2];
		$items = array_fill(0, 2, ['dataRefs' => [['register' => 'r', 'schema' => 's', 'id' => 'o']]]);

		$job = $this->batchPrintJob(service: $service, session: $session, logger: $this->createMock(LoggerInterface::class));
		(new \ReflectionMethod($job, 'run'))->invoke($job, ['jobId' => 'j', 'templateId' => 't', 'items' => $items, 'options' => []]);

		$this->assertSame(['u', 'u'], $resolvedAs);
		$this->assertNull($session->getUser());
		$this->assertSame('queued', $this->rows['j']['status']);

	}//end testTheDataIsResolvedAsTheRequester()

	/**
	 * A job whose requester no longer exists resolves nothing and is failed.
	 *
	 * @return void
	 */
	public function testAJobWithoutAKnownRequesterIsNotRendered(): void {
		$resolver = $this->createMock(DataResolverService::class);
		$resolver->expects($this->never())->method('resolve');
		$service = $this->service(resolver: $resolver);
		$this->rows['j'] = ['uuid' => 'j', 'status' => 'rendering', 'requestedBy' => 'gone', 'total' => 1];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error');

		$job = $this->batchPrintJob(service: $service, session: $this->session(), logger: $logger);
		(new \ReflectionMethod($job, 'run'))->invoke($job, ['jobId' => 'j', 'templateId' => 't', 'items' => [['dataRefs' => [['register' => 'r', 'schema' => 's', 'id' => 'o']]]], 'options' => []]);

		$this->assertSame([], $this->stored);
		$this->assertSame('rendering', $this->rows['j']['status']);

	}//end testAJobWithoutAKnownRequesterIsNotRendered()

	/**
	 * A print job service over the doubles, with a template that loads.
	 *
	 * @param DataResolverService $resolver The resolver.
	 *
	 * @return PrintJobService The service.
	 */
	private function service(DataResolverService $resolver): PrintJobService {
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturn('%PDF');
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 't', 'content' => '']);

		return new PrintJobService(
			$pdf,
			$templates,
			$resolver,
			$this->printJobRepository(),
			$this->printJobFileStore(),
			new JobQueue(jobList: $this->createMock(IJobList::class)),
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The background job, with user 'u' known to the user manager.
	 *
	 * @param PrintJobService $service The print job service.
	 * @param IUserSession    $session The session.
	 * @param LoggerInterface $logger  The logger.
	 *
	 * @return BatchPrintJob The job.
	 */
	private function batchPrintJob(PrintJobService $service, IUserSession $session, LoggerInterface $logger): BatchPrintJob {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('u');
		$users = $this->createMock(IUserManager::class);
		$users->method('get')->willReturnCallback(static fn (string $uid): ?IUser => $uid === 'u' ? $user : null);

		return new BatchPrintJob($this->createMock(ITimeFactory::class), $service, $users, $session, $logger);

	}//end batchPrintJob()

	/**
	 * A session that starts without a user and holds the one it is given.
	 *
	 * @return IUserSession The session.
	 */
	private function session(): IUserSession {
		$session = $this->createMock(IUserSession::class);
		$current = null;
		$session->method('getUser')->willReturnCallback(static function () use (&$current): ?IUser {
			return $current;
		});
		$session->method('setVolatileActiveUser')->willReturnCallback(static function (?IUser $user) use (&$current): void {
			$current = $user;
		});

		return $session;

	}//end session()
}//end class
