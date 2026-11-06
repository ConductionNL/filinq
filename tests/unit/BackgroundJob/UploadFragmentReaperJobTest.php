<?php

/**
 * The nightly sweep's clock, and the floor under the declared age.
 *
 * 🔴 A DECLARED AGE OF ZERO WOULD REAP EVERY IN-FLIGHT UPLOAD. It is the one
 * value an administrator can type that turns this job from housekeeping into
 * data loss, and "0 means no limit" is a common enough convention that somebody
 * will try it. The floor is asserted here rather than trusted to a comment.
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
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\UploadFragmentReaperJob;
use OCA\Filinq\Service\UploadFragmentReaper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;

/**
 * `UploadFragmentReaperJob`.
 *
 * @covers \OCA\Filinq\BackgroundJob\UploadFragmentReaperJob
 */
class UploadFragmentReaperJobTest extends TestCase {

	/**
	 * A job whose collaborators behave as given.
	 *
	 * @param int                  $ageHours    The declared age.
	 * @param UploadFragmentReaper $reaper      The reaper double.
	 * @param IRootFolder          $rootFolder  The file tree double.
	 * @param IUserManager         $userManager The user manager double.
	 *
	 * @return UploadFragmentReaperJob The job.
	 */
	private function job(
		int $ageHours,
		UploadFragmentReaper $reaper,
		IRootFolder $rootFolder,
		IUserManager $userManager,
	): UploadFragmentReaperJob {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueInt')->willReturn($ageHours);

		return new UploadFragmentReaperJob(
			$this->createMock(ITimeFactory::class),
			$reaper,
			$rootFolder,
			$userManager,
			$config,
			new NullLogger()
		);
	}//end job()

	/**
	 * A reaper double.
	 *
	 * Built with `onlyMethods` so it cannot invent a `reap()` the real class
	 * lacks: a sweep calling a method nothing implements would still pass here
	 * if the double were free to answer anything.
	 *
	 * @param array<string, mixed> $outcome What reap() answers.
	 *
	 * @return UploadFragmentReaper The double.
	 */
	private function reaper(array $outcome): UploadFragmentReaper {
		$reaper = $this->getMockBuilder(UploadFragmentReaper::class)
			->disableOriginalConstructor()
			->onlyMethods(['reap'])
			->getMock();
		$reaper->method('reap')->willReturn($outcome);

		return $reaper;
	}//end reaper()

	/**
	 * A user manager walking the given uids.
	 *
	 * @param array<int, string> $uids The users.
	 *
	 * @return IUserManager The double.
	 */
	private function users(array $uids): IUserManager {
		$manager = $this->createMock(IUserManager::class);
		$manager->method('callForSeenUsers')->willReturnCallback(
			function (callable $callback) use ($uids): void {
				foreach ($uids as $uid) {
					$user = $this->createMock(IUser::class);
					$user->method('getUID')->willReturn($uid);
					$callback($user);
				}
			}
		);

		return $manager;
	}//end users()

	/**
	 * A root folder whose users have a documents folder, or do not.
	 *
	 * @param bool $exists Whether the folder is there.
	 *
	 * @return IRootFolder The double.
	 */
	private function tree(bool $exists): IRootFolder {
		$documents = $this->createMock(Folder::class);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn($exists);
		$userFolder->method('get')->willReturn($documents);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);

		return $root;
	}//end tree()

	/**
	 * The declared age is honoured.
	 *
	 * @return void
	 */
	public function testHonoursTheDeclaredAge(): void {
		$job = $this->job(
			72,
			$this->reaper(['removed' => 0, 'bytes' => 0, 'kept' => 0, 'refused' => []]),
			$this->tree(true),
			$this->users([])
		);

		self::assertSame((72 * 3600), $job->maxAgeSeconds());
	}//end testHonoursTheDeclaredAge()

	/**
	 * A declared age below the floor is raised to it, never honoured.
	 *
	 * @return void
	 */
	public function testAnAgeBelowTheFloorIsRaised(): void {
		$job = $this->job(
			0,
			$this->reaper(['removed' => 0, 'bytes' => 0, 'kept' => 0, 'refused' => []]),
			$this->tree(true),
			$this->users([])
		);

		self::assertSame((UploadFragmentReaperJob::MINIMUM_AGE_HOURS * 3600), $job->maxAgeSeconds());
	}//end testAnAgeBelowTheFloorIsRaised()

	/**
	 * Every seen user's documents folder is swept.
	 *
	 * @return void
	 */
	public function testSweepsEverySeenUser(): void {
		$reaper = $this->getMockBuilder(UploadFragmentReaper::class)
			->disableOriginalConstructor()
			->onlyMethods(['reap'])
			->getMock();
		$reaper->expects(self::exactly(3))
			->method('reap')
			->willReturn(['removed' => 1, 'bytes' => 10, 'kept' => 0, 'refused' => []]);

		$job = $this->job(24, $reaper, $this->tree(true), $this->users(['anne', 'bram', 'chris']));

		$this->invokeRun(job: $job);
	}//end testSweepsEverySeenUser()

	/**
	 * A user with no documents folder is passed over, and no folder is made.
	 *
	 * @return void
	 */
	public function testAUserWithoutADocumentsFolderIsPassedOver(): void {
		$reaper = $this->getMockBuilder(UploadFragmentReaper::class)
			->disableOriginalConstructor()
			->onlyMethods(['reap'])
			->getMock();
		$reaper->expects(self::never())->method('reap');

		$job = $this->job(24, $reaper, $this->tree(false), $this->users(['anne']));

		$this->invokeRun(job: $job);
	}//end testAUserWithoutADocumentsFolderIsPassedOver()

	/**
	 * Call the protected run(), which is what the scheduler calls.
	 *
	 * @param UploadFragmentReaperJob $job The job.
	 *
	 * @return void
	 */
	private function invokeRun(UploadFragmentReaperJob $job): void {
		$method = (new ReflectionClass($job))->getMethod('run');
		$method->setAccessible(true);
		$method->invoke($job, null);
	}//end invokeRun()
}//end class
