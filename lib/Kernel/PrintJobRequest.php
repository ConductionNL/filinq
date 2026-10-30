<?php

/**
 * Print job request
 *
 * What a print job is made from apart from its letters: the template, the
 * print options, the requesting user and the download name.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel;

/**
 * The template, options, user and download name of one print job.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
 */
final class PrintJobRequest {

	/**
	 * Constructor.
	 *
	 * @param string               $templateId Template UUID to render
	 * @param array<string, mixed> $options    Print options: format, orientation, pdfa, duplex, color, paperTray, stapling
	 * @param string               $userId     UID of the requesting user
	 * @param string|null          $filename   Download name, or null for the default of the kind of job
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $templateId,
		public readonly array $options = [],
		public readonly string $userId = '',
		public readonly ?string $filename = null,
	) {

	}//end __construct()

	/**
	 * The download name, or the given default when none was asked for.
	 *
	 * @param string $default The default of the kind of job
	 *
	 * @return string The download name.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function filenameOr(string $default): string {
		return $this->filename ?? $default;

	}//end filenameOr()

	/**
	 * The same request under another download name.
	 *
	 * @param string $filename The download name
	 *
	 * @return self The request.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function named(string $filename): self {
		return new self(templateId: $this->templateId, options: $this->options, userId: $this->userId, filename: $filename);

	}//end named()
}//end class
