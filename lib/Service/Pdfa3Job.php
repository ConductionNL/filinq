<?php

/**
 * PDF/A-3 Job
 *
 * What one PDF/A-3 assembly writes, under which deadline, with which metadata.
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

use Closure;

/**
 * The page builder, deadline, default title, metadata, attachments and page options of one PDF/A-3 assembly.
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class Pdfa3Job {

	/**
	 * @param Closure                        $pageBuilder  `function (Mpdf $mpdf): void`, writes the page content
	 * @param float                          $deadline     Absolute microtime(true) deadline for this conversion
	 * @param string                         $defaultTitle Title used when the metadata has no title
	 * @param array<string,mixed>            $metadata     MDTO/archival metadata
	 * @param array<int,array<string,mixed>> $attachments  Files to embed
	 * @param array<string,mixed>            $options      {format?, orientation?, margin?}
	 */
	public function __construct(
		public readonly Closure $pageBuilder,
		public readonly float $deadline,
		public readonly string $defaultTitle,
		public readonly array $metadata = [],
		public readonly array $attachments = [],
		public readonly array $options = [],
	) {
	}//end __construct()
}//end class
