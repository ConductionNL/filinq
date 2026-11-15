<?php

/**
 * Unit tests for the bulk-send runner's guards
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\BulkSigning
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\BulkSigning;

use OCA\Filinq\Service\BulkSigning\BulkSigningBatchRepository;
use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCA\Filinq\Service\SigningService;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The runner never acts without its initiator, never touches a batch that is
 * not creating, and always drops the borrowed session.
 */
#[CoversClass(BulkSigningRunner::class)]
class BulkSigningRunnerTest extends TestCase {

	/**
	 * The batch store.
	 *
	 * @var BulkSigningBatchRepository&MockObject
	 */
	private BulkSigningBatchRepository $repository;

	/**
	 * The single-request path.
	 *
	 * @var SigningService&MockObject
	 */
	private SigningService $signing;

	/**
	 * The session the rows run under.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession $session;

	/**
	 * The user manager.
	 *
	 * @var IUserManager&MockObject
	 */
	private IUserManager $users;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->repository = $this->createMock(BulkSigningBatchRepository::class);
		$this->signing = $this->createMock(SigningService::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->users = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The runner under test.
	 *
	 * @return BulkSigningRunner
	 */
	private function runner(): BulkSigningRunner {
		return new BulkSigningRunner(
			repository: $this->repository,
			signing: $this->signing,
			userSession: $this->session,
			userManager: $this->users,
			logger: $this->logger
		);

	}//end runner()

	/**
	 * A user manager that knows exactly alice.
	 *
	 * @return IUser
	 */
	private function alice(): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->users->method('get')->willReturnCallback(static fn (string $uid): ?IUser => $uid === 'alice' ? $user : null);

		return $user;

	}//end alice()

	/**
	 * An initiator who no longer exists stops the run before anything is read or created.
	 *
	 * @return void
	 */
	public function testAVanishedInitiatorRunsNothing(): void {
		$this->users->method('get')->with('ghost')->willReturn(null);
		$this->logger->expects($this->once())->method('warning')->with(
			'Bulk send {batch} not run: its initiator {user} no longer exists',
			['batch' => 'batch-1', 'user' => 'ghost']
		);
		$this->session->expects($this->never())->method('setVolatileActiveUser');
		$this->repository->expects($this->never())->method('find');
		$this->repository->expects($this->never())->method('save');
		$this->signing->expects($this->never())->method('createRequest');

		$this->assertSame([], $this->runner()->run(batchId: 'batch-1', userId: 'ghost'));

	}//end testAVanishedInitiatorRunsNothing()

	/**
	 * A batch that is not creating (here: cancelled before the job ran) is returned untouched.
	 *
	 * @return void
	 */
	public function testABatchThatIsNotCreatingIsReturnedUnchanged(): void {
		$user = $this->alice();
		$batch = ['uuid' => 'batch-2', 'status' => 'cancelled', 'recipients' => [['row' => 2, 'userId' => 'an']]];
		$this->repository->method('find')->with('batch-2')->willReturn($batch);
		$this->repository->expects($this->never())->method('save');
		$this->signing->expects($this->never())->method('createRequest');

		$active = [];
		$this->session->method('setVolatileActiveUser')->willReturnCallback(
			static function (?IUser $u) use (&$active): void {
				$active[] = $u;
			}
		);

		$this->assertSame($batch, $this->runner()->run(batchId: 'batch-2', userId: 'alice'));
		$this->assertSame([$user, null], $active);

	}//end testABatchThatIsNotCreatingIsReturnedUnchanged()

	/**
	 * A missing batch reads as empty, is not saved, and still drops the session.
	 *
	 * @return void
	 */
	public function testAMissingBatchEndsEmpty(): void {
		$this->alice();
		$this->repository->method('find')->willReturn(null);
		$this->repository->expects($this->never())->method('save');

		$this->assertSame([], $this->runner()->run(batchId: 'gone', userId: 'alice'));

	}//end testAMissingBatchEndsEmpty()

	/**
	 * The borrowed session is dropped even when the store fails mid-run.
	 *
	 * @return void
	 */
	public function testTheSessionIsDroppedWhenTheStoreFails(): void {
		$user = $this->alice();
		$this->repository->method('find')->willThrowException(new RuntimeException('The bulk send could not be read: down'));

		$active = [];
		$this->session->method('setVolatileActiveUser')->willReturnCallback(
			static function (?IUser $u) use (&$active): void {
				$active[] = $u;
			}
		);

		try {
			$this->runner()->run(batchId: 'batch-3', userId: 'alice');
			$this->fail('Expected the store failure to propagate');
		} catch (RuntimeException $e) {
			$this->assertSame('The bulk send could not be read: down', $e->getMessage());
		}

		$this->assertSame([$user, null], $active);

	}//end testTheSessionIsDroppedWhenTheStoreFails()

	/**
	 * Each row becomes one single-signer request; ids fall back to uuid; a clean run completes.
	 *
	 * @return void
	 */
	public function testRowsBecomeSingleSignerRequestsAndTheBatchCompletes(): void {
		$this->alice();
		$batch = [
			'status' => 'creating',
			'documentFileId' => '4711',
			'documentName' => 'verklaring.pdf',
			'signatureLevel' => 'SES',
			'signingMode' => 'parallel',
			'provider' => 'native',
			'recipients' => [
				['row' => 2, 'userId' => 'an'],
				['row' => 3, 'email' => 'piet@example.org', 'displayName' => 'Piet'],
			],
		];
		$this->repository->method('find')->willReturn($batch);
		$saved = [];
		$this->repository->method('save')->willReturnCallback(
			static function (array $b, ?string $uuid = null) use (&$saved): array {
				$saved[] = $b;
				return $b;
			}
		);

		$signers = [];
		$this->signing->method('createRequest')->willReturnCallback(
			static function (array $data) use (&$signers): array {
				$signers[] = $data['signers'];
				return count($signers) === 1 ? ['id' => 'req-1'] : ['uuid' => 'req-2'];
			}
		);

		$result = $this->runner()->run(batchId: 'batch-4', userId: 'alice');

		$this->assertSame(
			[
				[['userId' => 'an', 'email' => '', 'displayName' => '']],
				[['userId' => '', 'email' => 'piet@example.org', 'displayName' => 'Piet']],
			],
			$signers
		);
		$this->assertSame('completed', $result['status']);
		$this->assertSame(['req-1', 'req-2'], $result['requestRefs']);
		$this->assertSame(2, $result['processedRows']);
		$this->assertArrayHasKey('completedAt', $result);
		$this->assertCount(3, $saved);

	}//end testRowsBecomeSingleSignerRequestsAndTheBatchCompletes()

	/**
	 * A failed row's message is recorded, truncated to 500 characters.
	 *
	 * @return void
	 */
	public function testAFailedRowIsRecordedWithATruncatedReason(): void {
		$this->alice();
		$this->repository->method('find')->willReturn(
			[
				'status' => 'creating',
				'documentFileId' => '1',
				'documentName' => 'd.pdf',
				'signatureLevel' => 'SES',
				'signingMode' => 'parallel',
				'provider' => 'native',
				'recipients' => [['row' => 7, 'userId' => 'an']],
			]
		);
		$this->repository->method('save')->willReturnArgument(0);
		$this->signing->method('createRequest')->willThrowException(new RuntimeException(str_repeat('x', 600)));

		$result = $this->runner()->run(batchId: 'batch-5', userId: 'alice');

		$this->assertSame('completed_with_errors', $result['status']);
		$this->assertSame(7, $result['rejectedRows'][0]['row']);
		$this->assertSame('creation-failed', $result['rejectedRows'][0]['reason']);
		$this->assertSame(500, mb_strlen($result['rejectedRows'][0]['detail']));

	}//end testAFailedRowIsRecordedWithATruncatedReason()
}//end class
