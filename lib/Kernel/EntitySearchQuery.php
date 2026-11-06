<?php

/**
 * Entity search query
 *
 * What a catalogue search looks for and which page of it: the value text,
 * the type and category filters, the page size and the page start.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel;

use OCA\Filinq\Kernel\Contracts\EntitySearchServiceInterface;

/**
 * The text, filters and page of one entity search.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
 */
final class EntitySearchQuery {

	/**
	 * Constructor.
	 *
	 * @param string $text     Case-insensitive substring of the value, '' for any
	 * @param string $type     Exact type, '' for any
	 * @param string $category Exact category, '' for any
	 * @param int    $limit    Page size
	 * @param int    $offset   Page start
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $text = '',
		public readonly string $type = '',
		public readonly string $category = '',
		public readonly int $limit = EntitySearchServiceInterface::DEFAULT_LIMIT,
		public readonly int $offset = 0,
	) {

	}//end __construct()

	/**
	 * The same query with trimmed text and filters, a page size between 1
	 * and the maximum, and a page start of at least 0.
	 *
	 * @param int $maxLimit The largest page size
	 *
	 * @return self The query.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
	 */
	public function normalised(int $maxLimit): self {
		return new self(
			text: trim($this->text),
			type: trim($this->type),
			category: trim($this->category),
			limit: max(1, min($maxLimit, $this->limit)),
			offset: max(0, $this->offset)
		);

	}//end normalised()
}//end class
