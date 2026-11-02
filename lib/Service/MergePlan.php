<?php

/**
 * Merge Plan
 *
 * What a merge combines and the folder its result goes in.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\Folder;

/**
 * A checked merge: the selection and the writable folder the result goes in.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
final class MergePlan {

	/**
	 * @param MergeSelection $selection The inputs and options.
	 * @param Folder $folder The folder the result goes in, checked for write.
	 */
	public function __construct(
		public readonly MergeSelection $selection,
		public readonly Folder $folder,
	) {
	}//end __construct()
}//end class
