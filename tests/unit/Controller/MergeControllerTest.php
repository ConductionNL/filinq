<?php

/**
 * The document merge endpoint's create and show answers, asserted from the caller.
 *
 * A merge is refused without a session user; small merges answer 200 with the
 * result, large ones (by the configured page threshold) are queued and answer
 * 202; a refusal keeps its own status and anything else is a 500. A job is
 * shown, missing (404) or temporarily unreadable (503, retryable).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\MergeController;
use OCA\Filinq\Exception\MergeJobStoreUnreadableException;
use OCA\Filinq\Exception\MergeRefusedException;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
use OCA\Filinq\Kernel\Contracts\MergeJobRepositoryInterface;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * `MergeController`.
 *
 * @covers \OCA\Filinq\Controller\MergeController
 * @uses   \OCA\Filinq\Exception\MergeRefusedException
 */
class MergeControllerTest extends TestCase {

	/**
	 * The merge service double.
	 *
	 * @var DocumentMergeServiceInterface&MockObject
	 */
	private DocumentMergeServiceInterface&MockObject $merges;

	/**
	 * The job repository double.
	 *
	 * @var MergeJobRepositoryInterface&MockObject
	 */
	private MergeJobRepositoryInterface&MockObject $jobs;

	/**
	 * The app config double.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig&MockObject $config;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->merges = $this->createMock(DocumentMergeServiceInterface::class);
		$this->jobs   = $this->createMock(MergeJobRepositoryInterface::class);
		$this->config = $this->createMock(IAppConfig::class);
	}//end setUp()

	/**
	 * The controller, with or without a session user.
	 *
	 * @param bool $loggedIn Whether a user is logged in.
	 *
	 * @return MergeController The controller.
	 */
	private function controller(bool $loggedIn = true): MergeController {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $this->createMock(IUser::class) : null);

		return new MergeController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			merges: $this->merges,
			jobs: $this->jobs,
			config: $this->config,
			userSession: $session,
		);
	}//end controller()

	/**
	 * Without a session user nothing is merged, queued or looked up.
	 *
	 * @return void
	 */
	public function testWithoutAUserNothingIsMergedOrShown(): void {
		$this->merges->expects($this->never())->method($this->anything());
		$this->jobs->expects($this->never())->method($this->anything());

		foreach ([$this->controller(false)->create(inputs: [['id' => 'a']]), $this->controller(false)->show(id: 'j1')] as $response) {
			$this->assertSame(401, $response->getStatus());
			$this->assertSame(['error' => 'You must be logged in to merge documents.'], $response->getData());
		}
	}//end testWithoutAUserNothingIsMergedOrShown()

	/**
	 * A merge under the threshold runs now and answers 200 with the result.
	 *
	 * @return void
	 */
	public function testASmallMergeRunsNow(): void {
		$inputs = [['id' => 'a'], ['id' => 'b']];
		$this->config->method('getValueInt')->with('filinq', 'mergeSyncThresholdPages', 50)->willReturn(20);
		$this->merges->expects($this->once())->method('shouldQueue')->with($inputs, 20)->willReturn(false);
		$this->merges->expects($this->never())->method('queue');
		$this->merges->expects($this->once())->method('merge')
			->with($inputs, ['toc' => true], ['id' => 'case-1'])
			->willReturn(['fileId' => 77]);

		$response = $this->controller()->create(inputs: $inputs, options: ['toc' => true], hostObject: ['id' => 'case-1']);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['fileId' => 77], $response->getData());
	}//end testASmallMergeRunsNow()

	/**
	 * A merge over the threshold is queued and answers 202 with the job.
	 *
	 * @return void
	 */
	public function testALargeMergeIsQueued(): void {
		$this->config->method('getValueInt')->willReturn(10);
		$this->merges->method('shouldQueue')->willReturn(true);
		$this->merges->expects($this->never())->method('merge');
		$this->merges->expects($this->once())->method('queue')
			->with([['id' => 'a']], [], [])
			->willReturn(['id' => 'job-1', 'status' => 'queued']);

		$response = $this->controller()->create(inputs: [['id' => 'a']]);

		$this->assertSame(202, $response->getStatus());
		$this->assertSame(['id' => 'job-1', 'status' => 'queued'], $response->getData());
	}//end testALargeMergeIsQueued()

	/**
	 * A threshold of zero or less is replaced by the default.
	 *
	 * @return void
	 */
	public function testANonPositiveThresholdFallsBackToTheDefault(): void {
		foreach ([0, -5] as $declared) {
			$this->setUp();
			$this->config->method('getValueInt')->willReturn($declared);
			$this->merges->expects($this->once())->method('shouldQueue')->with([], MergeController::DEFAULT_THRESHOLD)->willReturn(false);
			$this->merges->method('merge')->willReturn([]);

			$this->assertSame(200, $this->controller()->create()->getStatus());
		}
	}//end testANonPositiveThresholdFallsBackToTheDefault()

	/**
	 * A refused merge answers the refusal's own status and message.
	 *
	 * @return void
	 */
	public function testARefusalKeepsItsStatus(): void {
		$this->config->method('getValueInt')->willReturn(50);
		$this->merges->method('shouldQueue')->willReturn(false);
		$this->merges->method('merge')->willThrowException(new MergeRefusedException('You may not read input b.', 403));

		$response = $this->controller()->create(inputs: [['id' => 'b']]);

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'You may not read input b.'], $response->getData());
	}//end testARefusalKeepsItsStatus()

	/**
	 * Any other failure, also in deciding whether to queue, answers 500.
	 *
	 * @return void
	 */
	public function testAnyOtherFailureIsAServerError(): void {
		$this->config->method('getValueInt')->willReturn(50);
		$this->merges->method('shouldQueue')->willThrowException(new RuntimeException('page count failed'));

		$response = $this->controller()->create(inputs: [['id' => 'a']]);

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'page count failed'], $response->getData());
	}//end testAnyOtherFailureIsAServerError()

	/**
	 * A job is shown as stored, a missing one is a 404, and an unreadable store a retryable 503.
	 *
	 * @return void
	 */
	public function testShowAnswersTheJobMissingOrRetryable(): void {
		$this->jobs->method('find')->willReturnCallback(
			static fn (string $uuid): ?array => match ($uuid) {
				'j1' => ['id' => 'j1', 'status' => 'done'],
				'gone' => null,
				default => throw new MergeJobStoreUnreadableException('store offline'),
			}
		);
		$controller = $this->controller();

		$found = $controller->show(id: 'j1');
		$this->assertSame([200, ['id' => 'j1', 'status' => 'done']], [$found->getStatus(), $found->getData()]);

		$missing = $controller->show(id: 'gone');
		$this->assertSame([404, ['error' => 'There is no merge with that id.']], [$missing->getStatus(), $missing->getData()]);

		$unreadable = $controller->show(id: 'other');
		$this->assertSame(503, $unreadable->getStatus());
		$this->assertTrue($unreadable->getData()['retryable']);
	}//end testShowAnswersTheJobMissingOrRetryable()
}//end class
