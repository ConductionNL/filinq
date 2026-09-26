<?php

/**
 * The nightly sweep of what interrupted uploads left behind.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\UploadFragmentReaper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sweeps every seen user's documents folder once a night.
 *
 * 🔑 THE AGE IS DECLARED, NOT HARDCODED. REQ-CDF-05 says "older than a declared
 * age", and an instance that uploads 4 GB scans over a slow line needs a longer
 * one than the default. A declared age of zero would reap a fragment the moment
 * it appeared, taking every in-flight upload with it, so the floor is one hour
 * and a lower declaration is raised to it rather than honoured.
 *
 * 🔑 THE TOTALS ARE ACROSS THE INSTANCE, and each user's sweep is also logged by
 * the reaper itself. One instance-wide line answers "is this worth running",
 * the per-user lines answer "whose upload keeps breaking".
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class UploadFragmentReaperJob extends TimedJob {

	/**
	 * The app config key declaring how old a fragment must be, in hours.
	 *
	 * @var string
	 */
	public const AGE_KEY = 'uploadFragmentMaxAgeHours';

	/**
	 * The default age, in hours.
	 *
	 * @var int
	 */
	public const DEFAULT_AGE_HOURS = 24;

	/**
	 * The shortest age that is honoured, in hours.
	 *
	 * @var int
	 */
	public const MINIMUM_AGE_HOURS = 1;

	/**
	 * The folder filinq's documents live in.
	 *
	 * ⚠️ The folder name stays `DocuDesk` across the filinq rename; see
	 * FileUploadService::getFilinqFolder() for why moving it orphans every
	 * document silently.
	 *
	 * @var string
	 */
	public const DOCUMENTS_FOLDER = 'DocuDesk';

	/**
	 * Collaborators.
	 *
	 * @param ITimeFactory         $time        The clock the scheduler reads.
	 * @param UploadFragmentReaper $reaper      Sweeps one tree.
	 * @param IRootFolder          $rootFolder  The file tree.
	 * @param IUserManager         $userManager Every user with a folder.
	 * @param IAppConfig           $config      Where the age is declared.
	 * @param LoggerInterface      $logger      Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly UploadFragmentReaper $reaper,
		private readonly IRootFolder $rootFolder,
		private readonly IUserManager $userManager,
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);
		// Nightly, as REQ-CDF-05 asks.
		$this->setInterval(seconds: 86400);

	}//end __construct()

	/**
	 * The declared age, in seconds, never below the floor.
	 *
	 * @return int The age in seconds.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function maxAgeSeconds(): int {
		$hours = $this->config->getValueInt('filinq', self::AGE_KEY, self::DEFAULT_AGE_HOURS);

		if ($hours < self::MINIMUM_AGE_HOURS) {
			$hours = self::MINIMUM_AGE_HOURS;
		}

		return ($hours * 3600);

	}//end maxAgeSeconds()

	/**
	 * Sweep every seen user's documents folder once.
	 *
	 * @param mixed $argument Job arguments. The job is registered bare and takes none.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud's
	 * TimedJob signature, not a parameter this job chose. Dropping it changes the
	 * override into a different method and the job stops running.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	protected function run(mixed $argument): void {
		$maxAge = $this->maxAgeSeconds();
		$now = time();
		$removed = 0;
		$bytes = 0;
		$refused = 0;

		$this->userManager->callForSeenUsers(
			// The closure answers `true` rather than nothing: callForSeenUsers()
			// reads a falsy answer as "stop walking", so a void closure would end
			// the sweep after the first user on a strict reading of the contract.
			function (IUser $user) use ($maxAge, $now, &$removed, &$bytes, &$refused): bool {
				$folder = $this->documentsFolder(userId: $user->getUID());
				if ($folder === null) {
					return true;
				}

				$outcome = $this->reaper->reap(folder: $folder, maxAgeSeconds: $maxAge, now: $now);
				$removed += (int)$outcome['removed'];
				$bytes += (int)$outcome['bytes'];
				$refused += count($outcome['refused']);

				return true;
			}
		);

		$this->logger->info(
			'filinq.upload-fragments.swept',
			['removed' => $removed, 'bytes' => $bytes, 'refused' => $refused, 'maxAgeSeconds' => $maxAge]
		);

	}//end run()

	/**
	 * One user's documents folder, or null when they have none.
	 *
	 * @param string $userId The user.
	 *
	 * @return Folder|null The folder.
	 *
	 * @spec exclude Lookup helper; the sweep's behaviour is in the reaper.
	 */
	private function documentsFolder(string $userId): ?Folder {
		try {
			$userFolder = $this->rootFolder->getUserFolder($userId);

			// A user who never uploaded has no folder, which is not a failure
			// and is deliberately not created here: making a folder during a
			// reap would leave an empty directory in every account on the
			// instance the first night this job runs.
			if ($userFolder->nodeExists(self::DOCUMENTS_FOLDER) === false) {
				return null;
			}

			$node = $userFolder->get(self::DOCUMENTS_FOLDER);
			if ($node instanceof Folder === false) {
				return null;
			}

			return $node;
		} catch (Throwable $e) {
			$this->logger->warning(
				'filinq.upload-fragments.folder-unreachable',
				['user' => $userId, 'error' => $e->getMessage()]
			);

			return null;
		}

	}//end documentsFolder()
}//end class
