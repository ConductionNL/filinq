<?php

/**
 * Unit tests for MailArchiveImportJob
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
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

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\MailArchiveImportJob;
use OCA\Filinq\Service\MailArchive\MailArchiveImportService;
use OCA\Filinq\Service\Support\ActingUser;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * One work unit per run; a failing unit is logged and retried next run.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 */
class MailArchiveImportJobTest extends TestCase {

	/**
	 * Run the job once.
	 *
	 * @param MailArchiveImportService $imports The imports.
	 * @param LoggerInterface          $logger  The logger.
	 *
	 * @return void
	 */
	private function runJob(MailArchiveImportService $imports, LoggerInterface $logger, ?ActingUser $acting=null): void {
		$acting ??= new ActingUser(users: $this->createMock(IUserManager::class), session: $this->createMock(IUserSession::class));
		$job      = new MailArchiveImportJob(clock: $this->createMock(ITimeFactory::class), imports: $imports, logger: $logger, acting: $acting);
		(new ReflectionMethod($job, 'run'))->invoke($job, null);

	}//end runJob()

	/**
	 * A run takes exactly one unit and logs where the import is.
	 *
	 * @return void
	 */
	public function testARunTakesOneUnit(): void {
		$imports = $this->createMock(MailArchiveImportService::class);
		$imports->expects($this->once())->method('runUnit')->willReturn(['uuid' => 'import-1', 'status' => 'importing']);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('info')->with($this->anything(), ['uuid' => 'import-1', 'status' => 'importing']);

		$this->runJob(imports: $imports, logger: $logger);

	}//end testARunTakesOneUnit()

	/**
	 * Nothing importing logs nothing.
	 *
	 * @return void
	 */
	public function testAnIdleRunLogsNothing(): void {
		$imports = $this->createMock(MailArchiveImportService::class);
		$imports->expects($this->once())->method('runUnit')->willReturn(null);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');
		$logger->expects($this->never())->method('error');

		$this->runJob(imports: $imports, logger: $logger);

	}//end testAnIdleRunLogsNothing()

	/**
	 * A unit that throws is logged and does not escape, so cron goes on.
	 *
	 * @return void
	 */
	public function testAFailingUnitIsLogged(): void {
		$imports = $this->createMock(MailArchiveImportService::class);
		$imports->method('runUnit')->willThrowException(new RuntimeException('register down'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with($this->anything(), ['exception' => 'register down']);

		$this->runJob(imports: $imports, logger: $logger);

	}//end testAFailingUnitIsLogged()
	/**
	 * The unit runs as the user who started the import, and the previous user is restored after.
	 *
	 * Seen live: run without a user, every message failed on OpenRegister's RBAC ("User 'Anonymous'").
	 *
	 * @return void
	 */
	public function testTheUnitRunsAsTheUserWhoStartedTheImport(): void {
		$anna  = $this->createMock(originalClassName: IUser::class);
		$users = $this->createMock(originalClassName: IUserManager::class);
		$users->method('get')->with('anna')->willReturn($anna);
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$active = [];
		$session->method('setVolatileActiveUser')->willReturnCallback(
			function (?IUser $user) use (&$active): void {
				$active[] = $user;
			}
		);
		$imports = $this->createMock(originalClassName: MailArchiveImportService::class);
		$imports->method('nextRequester')->willReturn('anna');
		$imports->expects($this->once())->method('runUnit')->willReturnCallback(
			function () use (&$active, $anna): ?array {
				$this->assertSame(expected: [$anna], actual: $active, message: 'the unit runs as anna');
				return null;
			}
		);

		$this->runJob(imports: $imports, logger: $this->createMock(originalClassName: LoggerInterface::class), acting: new ActingUser(users: $users, session: $session));

		$this->assertSame(expected: [$anna, null], actual: $active);
	}//end testTheUnitRunsAsTheUserWhoStartedTheImport()

}//end class
