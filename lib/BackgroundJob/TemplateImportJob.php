<?php

/**
 * Template import job
 *
 * Runs one queued bulk import of office templates and text fragments.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

/**
 * Queued job that runs a template import.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
 */
class TemplateImportJob extends QueuedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory          $time    Time factory.
	 * @param TemplateImportService $imports Runs the import.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly TemplateImportService $imports,
	) {
		parent::__construct(time: $time);

	}//end __construct()

	/**
	 * Run the import named in the argument.
	 *
	 * @param mixed $argument {jobId}
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
	 */
	protected function run(mixed $argument): void {
		$jobId = (string) ($argument['jobId'] ?? '');
		if ($jobId !== '') {
			$this->imports->run(jobId: $jobId);
		}

	}//end run()
}//end class
