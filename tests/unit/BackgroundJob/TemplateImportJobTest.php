<?php

/**
 * The template import job runs the import as the user who started it and
 * gives the session back afterwards, also when the import fails.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\TemplateImportJob;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * @covers \OCA\Filinq\BackgroundJob\TemplateImportJob
 */
class TemplateImportJobTest extends TestCase {

	/**
	 * The import runs as its starter; the previous user is put back.
	 *
	 * @return void
	 */
	public function testTheImportRunsAsItsStarter(): void {
		$anne = $this->createMock(IUser::class);
		$previous = $this->createMock(IUser::class);
		$users = $this->createMock(IUserManager::class);
		$users->method('get')->with('anne')->willReturn($anne);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($previous);
		$set = [];
		$session->method('setVolatileActiveUser')->willReturnCallback(static function (?IUser $user) use (&$set): void {
			$set[] = $user;
		});
		$imports = $this->createMock(TemplateImportService::class);
		$imports->expects($this->once())->method('run')->with('job-1')
			->willReturnCallback(static function () use (&$set, $anne): array {
				TestCase::assertSame([$anne], $set);
				return ['imported' => 2, 'totalFiles' => 3, 'failed' => 1];
			});

		$this->runJob(job: new TemplateImportJob($this->createMock(ITimeFactory::class), $imports, $users, $session, $this->createMock(LoggerInterface::class)), argument: ['jobId' => 'job-1', 'userId' => 'anne']);

		$this->assertSame([$anne, $previous], $set);
	}//end testTheImportRunsAsItsStarter()

	/**
	 * An unknown user runs nothing; a failing import is logged and the
	 * session still given back.
	 *
	 * @return void
	 */
	public function testAnUnknownUserRunsNothingAndAFailureIsLogged(): void {
		$users = $this->createMock(IUserManager::class);
		$users->method('get')->willReturnCallback(fn (string $uid): ?IUser => ($uid === 'anne' ? $this->createMock(IUser::class) : null));
		$session = $this->createMock(IUserSession::class);
		$session->expects($this->exactly(2))->method('setVolatileActiveUser');
		$imports = $this->createMock(TemplateImportService::class);
		$imports->expects($this->once())->method('run')->willThrowException(new Exception('boom'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->exactly(2))->method('error');
		$job = new TemplateImportJob($this->createMock(ITimeFactory::class), $imports, $users, $session, $logger);

		$this->runJob(job: $job, argument: ['jobId' => 'job-1', 'userId' => 'nobody']);
		$this->runJob(job: $job, argument: ['jobId' => 'job-1', 'userId' => 'anne']);
	}//end testAnUnknownUserRunsNothingAndAFailureIsLogged()

	/**
	 * Call the job's protected run().
	 *
	 * @param TemplateImportJob    $job      The job.
	 * @param array<string, mixed> $argument The argument.
	 *
	 * @return void
	 */
	private function runJob(TemplateImportJob $job, array $argument): void {
		(new ReflectionMethod($job, 'run'))->invoke($job, $argument);
	}//end runJob()
}//end class
