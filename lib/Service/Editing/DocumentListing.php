<?php

/**
 * Document Listing
 *
 * How one "open for an agent" read lists a document's items.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Editing
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

namespace OCA\Filinq\Service\Editing;

use Closure;

/**
 * The envelope keys and item reader of one agent read.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
 */
class DocumentListing {

	/**
	 * @param string            $countKey The envelope key for the total count
	 * @param string            $itemsKey The envelope key for the items
	 * @param Closure           $read     `fn (string $bytes, string $extension): array{items: array, extra: array}`
	 */
	public function __construct(
		public readonly string $countKey,
		public readonly string $itemsKey,
		public readonly Closure $read,
	) {
	}//end __construct()
}//end class
