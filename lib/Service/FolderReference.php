<?php

/**
 * Folder Reference
 *
 * How a request names the folder a batch is created from: by node id or by
 * path relative to the user's root. Exactly one of the two must be given;
 * an empty path counts as none.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * A folder named by id or by path.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
 */
final class FolderReference {

	/**
	 * Constructor.
	 *
	 * @param int|null    $folderId   Node ID of the folder, or null.
	 * @param string|null $folderPath Relative path of the folder, or null.
	 */
	public function __construct(
		public readonly ?int $folderId,
		public readonly ?string $folderPath,
	) {

	}//end __construct()

	/**
	 * Whether an id was given.
	 *
	 * @return bool True when the folder id is set.
	 *
	 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
	 */
	public function hasId(): bool {
		return $this->folderId !== null;

	}//end hasId()

	/**
	 * Whether a non-empty path was given.
	 *
	 * @return bool True when the folder path is set and not empty.
	 *
	 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
	 */
	public function hasPath(): bool {
		return $this->folderPath !== null && $this->folderPath !== '';

	}//end hasPath()
}//end class
