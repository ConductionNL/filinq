<?php

/**
 * Entity Grouping Run
 *
 * One pass that groups a file's anonymised entity rows: the file, the
 * caller's scope-local placeholder map, the groups so far and how many
 * rows had no placeholder. Built and used inside DossierEntityCollector only.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The file, placeholder map, groups and omitted count of one grouping pass.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */
final class EntityGroupingRun {

	/**
	 * The groups so far, by `entity_type:entity_id`.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $grouped = [];

	/**
	 * How many entities had no scope-local placeholder.
	 *
	 * @var int
	 */
	public int $omitted = 0;

	/**
	 * Constructor.
	 *
	 * @param int                   $fileId         The file id (log context).
	 * @param array<string, string> $placeholderMap Scope-local placeholder map.
	 */
	public function __construct(
		public readonly int $fileId,
		public readonly array $placeholderMap,
	) {

	}//end __construct()
}//end class
