<?php

/**
 * Document Generation Record
 *
 * What one generation made, from what, and for whom: the part of a
 * generatedDocument audit entry that does not depend on how it went.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The template, data, format and user of one generation, plus extra entry fields.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */
final class DocumentGenerationRecord {

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $template The template identity: {id: string, version: int|null, name: string}.
	 * @param array<mixed>         $dataRefs The data references used.
	 * @param string               $format   The output format.
	 * @param string               $userId   The generating user's UID.
	 * @param array<string, mixed> $extra    Extra fields for the entry; the canonical fields win over them.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly array $template,
		public readonly array $dataRefs,
		public readonly string $format,
		public readonly string $userId,
		public readonly array $extra = [],
	) {

	}//end __construct()
}//end class
