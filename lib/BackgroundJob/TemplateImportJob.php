<?php

/**
 * Template Import Job
 *
 * Background job that runs a template import started through
 * POST /api/templates/import. It runs as the user who started the import, so
 * the templates and fragments are created with that user's access, as during
 * the request.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs one queued template import.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 */
class TemplateImportJob extends QueuedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory          $time        The clock.
	 * @param TemplateImportService $imports     Runs the import.
	 * @param IUserManager          $users       Finds the user who started it.
	 * @param IUserSession          $userSession Runs the import as that user.
	 * @param LoggerInterface       $logger      Logs a failed run.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly TemplateImportService $imports,
		private readonly IUserManager $users,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);
	}//end __construct()

	/**
	 * Run the import named by the argument, as the user who started it.
	 *
	 * @param mixed $argument `{jobId, userId}`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	protected function run(mixed $argument): void {
		$jobId = (string) ($argument['jobId'] ?? '');
		$user = $this->users->get((string) ($argument['userId'] ?? ''));
		if ($jobId === '' || $user === null) {
			$this->logger->error(message: 'TemplateImportJob: missing job or unknown user', context: ['argument' => $argument]);
			return;
		}

		$previous = $this->userSession->getUser();
		try {
			$this->userSession->setVolatileActiveUser($user);
			$job = $this->imports->run(jobId: $jobId);
			$this->logger->info(
				message: 'TemplateImportJob imported '.(int) ($job['imported'] ?? 0).' of '.(int) ($job['totalFiles'] ?? 0).' files',
				context: ['jobId' => $jobId, 'failed' => (int) ($job['failed'] ?? 0)]
			);
		} catch (Throwable $e) {
			$this->logger->error(message: 'TemplateImportJob failed: '.$e->getMessage(), context: ['jobId' => $jobId, 'exception' => $e]);
		} finally {
			$this->userSession->setVolatileActiveUser($previous);
		}
	}//end run()
}//end class
