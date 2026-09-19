<?php

/**
 * A polling caller is never told a queued merge is gone
 *
 * 🔴 THE DEFECT THESE TESTS HOLD DOWN. `MergeJobRepository::find()` caught
 * every Throwable and returned `null`, and `MergeController::show()` reads
 * `null` as "there is no merge with that id" and answers 404. So a register
 * that was briefly away told the person watching a queued merge that their
 * merge did not exist. A 404 ends the polling, because something that is gone
 * is not coming back, and the merge then finished with nobody watching.
 *
 * The same shape sat on the other side of the queue: `findQueued()` turned an
 * unreadable queue into an empty list, and MergeDocumentsJob ran over it,
 * logged nothing and finished looking exactly like a run with no work to do.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\MergeController;
use OCA\Filinq\Exception\MergeJobStoreUnreadableException;
use OCA\Filinq\Service\DocumentMergeService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\MergeJobRepository;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Http;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Asserts that a failed read and a genuine absence reach the caller apart.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MergePollAnswersTruthfullyTest extends TestCase {

	/**
	 * A repository over an OpenRegister that behaves as told.
	 *
	 * @param bool                      $unreadable True to make the read raise, as a register that is away does.
	 * @param array<string, mixed>|null $row        The row a working read finds, or null for a genuine absence.
	 *
	 * @return MergeJobRepository The repository.
	 */
	private function repository(bool $unreadable, ?array $row = null): MergeJobRepository {
		$objectService = $this->createMock(ObjectService::class);

		if ($unreadable === true) {
			$objectService->method('find')->willThrowException(
				new RuntimeException('the register did not answer')
			);
			$objectService->method('searchObjectsBySlug')->willThrowException(
				new RuntimeException('the register did not answer')
			);
		} else {
			$objectService->method('find')->willReturn($row);
			$objectService->method('searchObjectsBySlug')->willReturn([]);
		}

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new MergeJobRepository($resolver, new NullLogger());

	}//end repository()

	/**
	 * A controller over the given job store.
	 *
	 * @param MergeJobRepository $jobs The job store.
	 *
	 * @return MergeController The controller, with a signed-in user.
	 */
	private function controller(MergeJobRepository $jobs): MergeController {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new MergeController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->createMock(DocumentMergeService::class),
			$jobs,
			$this->createMock(IAppConfig::class),
			$session
		);

	}//end controller()

	/**
	 * A register that cannot be read does not make the merge disappear.
	 *
	 * @return void
	 */
	public function testAFailedReadIsNotAMissingMerge(): void {
		$response = $this->controller($this->repository(unreadable: true))->show(id: 'merge-1');

		$this->assertSame(
			Http::STATUS_SERVICE_UNAVAILABLE,
			$response->getStatus(),
			'a read that failed answers 503, so the caller keeps polling a merge that is still queued'
		);

		$data = $response->getData();
		$this->assertTrue(
			($data['retryable'] ?? false),
			'the answer says it is worth asking again'
		);
		$this->assertStringNotContainsStringIgnoringCase(
			'no merge',
			(string)($data['error'] ?? ''),
			'nothing in the answer claims the merge does not exist'
		);

	}//end testAFailedReadIsNotAMissingMerge()

	/**
	 * An id nobody ever queued is still a 404, because that is true.
	 *
	 * @return void
	 */
	public function testAnIdNobodyQueuedIsStillAbsent(): void {
		$response = $this->controller($this->repository(unreadable: false, row: null))->show(id: 'never-queued');

		$this->assertSame(
			Http::STATUS_NOT_FOUND,
			$response->getStatus(),
			'a genuine absence still answers 404'
		);

	}//end testAnIdNobodyQueuedIsStillAbsent()

	/**
	 * A job that is there is answered with the job.
	 *
	 * @return void
	 */
	public function testAQueuedJobIsAnswered(): void {
		$response = $this->controller(
			$this->repository(unreadable: false, row: ['uuid' => 'merge-1', 'status' => 'queued'])
		)->show(id: 'merge-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus(), 'a job that is there answers 200');
		$this->assertSame('queued', ($response->getData()['status'] ?? ''), 'and carries its status');

	}//end testAQueuedJobIsAnswered()

	/**
	 * The repository raises rather than answering null on a failed read.
	 *
	 * @return void
	 */
	public function testTheStoreRaisesOnAFailedRead(): void {
		$this->expectException(MergeJobStoreUnreadableException::class);

		$this->repository(unreadable: true)->find(uuid: 'merge-1');

	}//end testTheStoreRaisesOnAFailedRead()

	/**
	 * An unreadable queue is not an empty queue.
	 *
	 * @return void
	 */
	public function testAnUnreadableQueueRaisesRatherThanReadingEmpty(): void {
		$this->expectException(MergeJobStoreUnreadableException::class);

		$this->repository(unreadable: true)->findQueued();

	}//end testAnUnreadableQueueRaisesRatherThanReadingEmpty()
}//end class
