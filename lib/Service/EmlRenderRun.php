<?php

/**
 * EML Render Run
 *
 * The engine and settings one EML structure is rendered with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The shared mPDF engine, PDF options and attachment settings of one assembly, and whether this is the outermost message.
 *
 * @spec openspec/changes/archive/2026-06-14-eml-pdf-assembly/tasks.md
 */
class EmlRenderRun {

	/**
	 * @param \Mpdf\Mpdf          $mpdf        Shared mPDF instance
	 * @param array<string,mixed> $options     PDF options (for print CSS)
	 * @param bool                $appendPages Whether renderable attachments are appended
	 * @param int                 $maxSize     Max attachment render size in bytes
	 * @param bool                $isRoot      True for the outermost message (no leading page break)
	 */
	public function __construct(
		public readonly \Mpdf\Mpdf $mpdf,
		public readonly array $options,
		public readonly bool $appendPages,
		public readonly int $maxSize,
		public readonly bool $isRoot,
	) {
	}//end __construct()

	/**
	 * The same run for a nested message.
	 *
	 * @return self The run with isRoot false.
	 *
	 * @spec openspec/changes/archive/2026-06-14-eml-pdf-assembly/tasks.md
	 */
	public function nested(): self {
		return new self(
			mpdf: $this->mpdf,
			options: $this->options,
			appendPages: $this->appendPages,
			maxSize: $this->maxSize,
			isRoot: false
		);
	}//end nested()
}//end class
