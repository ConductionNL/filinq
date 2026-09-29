<?php

/**
 * Print Job File Store
 *
 * Keeps the PDFs of a print job in the app data folder `print-jobs`, one
 * file per letter. They used to be base64 strings in app configuration, which
 * every request loads.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;

/**
 * Stores and reads the PDFs of print jobs.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 */
class PrintJobFileStore {

	/**
	 * The app data folder holding every print job PDF.
	 */
	public const FOLDER = 'print-jobs';

	/**
	 * Constructor.
	 *
	 * @param IAppData $appData The app data root of this app
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppData $appData,
	) {

	}//end __construct()

	/**
	 * Store one letter of a job.
	 *
	 * @param string $jobId   The job id
	 * @param int    $index   The letter's place in the job
	 * @param string $content The PDF bytes
	 *
	 * @return string The stored file name, to keep on the job.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function put(string $jobId, int $index, string $content): string {
		$name = self::fileName(jobId: $jobId, index: $index);
		$folder = $this->folder();
		try {
			// A rerun (the repair step after a failed write) overwrites.
			$folder->getFile($name)->putContent($content);
		} catch (NotFoundException) {
			$folder->newFile($name, $content);
		}

		return $name;

	}//end put()

	/**
	 * Read one stored file.
	 *
	 * @param string $name The file name kept on the job
	 *
	 * @return string|null The bytes, or null when the file is not there.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function get(string $name): ?string {
		try {
			return $this->folder()->getFile($name)->getContent();
		} catch (NotFoundException) {
			return null;
		}

	}//end get()

	/**
	 * The file name of one letter of a job.
	 *
	 * @param string $jobId The job id
	 * @param int    $index The letter's place in the job
	 *
	 * @return string The file name.
	 */
	public static function fileName(string $jobId, int $index): string {
		return preg_replace('/[^A-Za-z0-9-]/', '', $jobId) . '-' . $index . '.pdf';

	}//end fileName()

	/**
	 * The folder, created on first use.
	 *
	 * @return ISimpleFolder The print job folder.
	 */
	private function folder(): ISimpleFolder {
		try {
			return $this->appData->getFolder(self::FOLDER);
		} catch (NotFoundException) {
			return $this->appData->newFolder(self::FOLDER);
		}

	}//end folder()
}//end class
