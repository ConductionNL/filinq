<?php

/**
 * Contract of SessionUserFile
 *
 * The public surface of the SessionUserFile service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use OCP\Files\File;

/**
 * Contract of SessionUserFile.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SessionUserFileInterface {
	/**
	 * The first node with this id in the signed-in user's own folder, when it
	 * is a file; null without a session, for an unknown id or for a folder.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file, or null.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function find(int $fileId): ?File;
}//end interface
