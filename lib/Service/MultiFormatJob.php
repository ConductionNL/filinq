<?php

/**
 * Multi Format Job
 *
 * One request to generate a document in several formats.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The template (with its id), the data references, the validated formats and
 * the request options of one multi-format generation.
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.4
 */
final class MultiFormatJob {

	/**
	 * @param array<string, mixed> $template The template, with its id.
	 * @param array                $dataRefs The data references.
	 * @param string[]             $formats  The formats, validated and deduplicated.
	 * @param array<string, mixed> $options  The request options (userId, caseId, ...).
	 */
	public function __construct(
		public readonly array $template,
		public readonly array $dataRefs,
		public readonly array $formats,
		public readonly array $options,
	) {
	}//end __construct()

	/**
	 * Whose Files the outputs go in.
	 *
	 * @return string The user id.
	 */
	public function userId(): string {
		return (string) $this->options['userId'];
	}//end userId()
}//end class
