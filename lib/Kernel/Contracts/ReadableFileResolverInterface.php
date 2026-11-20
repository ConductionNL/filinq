<?php

/**
 * Contract of ReadableFileResolver
 *
 * The public surface of the ReadableFileResolver service, so callers in other
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

use OCP\Files\Node;

/**
 * Contract of ReadableFileResolver.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ReadableFileResolverInterface {
	/**
	 * The file behind one id, or null when the signed-in reader may not read it.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return Node|null The node, or null when there is no reader or the reader may not read it.
	 *
	 * @spec openspec/specs/document-register/spec.md
	 */
	public function node(int $fileId): ?Node;
}
