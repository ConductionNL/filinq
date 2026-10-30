<?php

/**
 * Agent File Request
 *
 * Which file an agent call addresses, as whom, and against which version.
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

/**
 * The acting user, the file id, the codec kind and, for a write, the version and requested output mode.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
class AgentFileRequest {

	/**
	 * @param string      $uid           The acting user id
	 * @param int         $fileId        The Nextcloud file id
	 * @param string      $version       The version returned by the read a write is based on; empty for a read
	 * @param string|null $requestedMode An output mode narrowing request, or null for the configured mode
	 * @param DocumentKind|null $kind    The codec kind the file must match, or null for the text codec
	 */
	public function __construct(
		public readonly string $uid,
		public readonly int $fileId,
		public readonly string $version = '',
		public readonly ?string $requestedMode = null,
		public readonly ?DocumentKind $kind = null,
	) {
	}//end __construct()
}//end class
