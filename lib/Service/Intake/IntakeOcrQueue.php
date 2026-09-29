<?php

/**
 * Intake OCR Queue
 *
 * Decides whether an arriving intake document needs its text read, marks it
 * `queued`, and queues the job that reads it. Every channel comes through
 * IntakeService::receive(), so this one door covers them all.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-1.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCA\Filinq\BackgroundJob\IntakeOcrJob;
use OCA\Filinq\Service\OcrService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use Throwable;

/**
 * Reading on arrival: the mark before the save, the job after it.
 *
 * @spec openspec/specs/intake-ocr-on-arrival/spec.md
 */
class IntakeOcrQueue {

	/**
	 * The admin setting that turns reading on arrival on or off.
	 *
	 * @var string
	 */
	public const SETTING = 'ocr_on_arrival';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config The app settings.
	 * @param OcrService $ocr Which files need OCR.
	 * @param IRootFolder $rootFolder Resolves the file, with or without a session.
	 * @param IJobList $jobs The background job queue.
	 * @param IntakeReadingProgress $progress The reading states.
	 * @param ITimeFactory $time The clock.
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly OcrService $ocr,
		private readonly IRootFolder $rootFolder,
		private readonly IJobList $jobs,
		private readonly IntakeReadingProgress $progress,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Mark a new document `queued` when its file needs reading.
	 *
	 * @param array<string, mixed> $document The document about to be stored.
	 *
	 * @return array<string, mixed> The document, with readingState when it will be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-1.3
	 */
	public function mark(array $document): array {
		if ($this->config->getValueString('filinq', self::SETTING, '1') !== '1') {
			return $document;
		}

		$fileId = (int) ($document['file'] ?? 0);
		if ($fileId <= 0) {
			return $document;
		}

		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			return $document;
		}

		if ($node instanceof File === false || $this->ocr->needsOcr(mimeType: $node->getMimeType()) === false) {
			return $document;
		}

		return array_merge(
			$document,
			$this->progress->progressFor(
				state: IntakeReadingProgress::QUEUED,
				moment: $this->time->getDateTime()->format(DATE_ATOM)
			)
		);

	}//end mark()

	/**
	 * Queue the reading job for a stored document marked `queued`.
	 *
	 * @param array<string, mixed> $document The stored document, with its uuid.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-1.3
	 */
	public function queue(array $document): void {
		if (($document['readingState'] ?? null) !== IntakeReadingProgress::QUEUED || ($document['uuid'] ?? '') === '') {
			return;
		}

		$this->jobs->add(
			IntakeOcrJob::class,
			['uuid' => (string) $document['uuid'], 'fileId' => (int) $document['file']]
		);

	}//end queue()
}//end class
