<?php

/**
 * Contract of PortalSigningDocumentResolver
 *
 * The public surface of the PortalSigningDocumentResolver service, so callers in other
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
 * Contract of PortalSigningDocumentResolver.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PortalSigningDocumentResolverInterface {
	/**
	 * Resolve the target document's File node for viewDocument.
	 *
	 * Returns null — never throws — when the request carries no usable file
	 * reference or the node cannot be read, so the caller can answer
	 * `document_unavailable` without leaking storage internals.
	 *
	 * @param array<string, mixed> $signingRequest The resolved signing request.
	 *
	 * @return File|null The resolved file node, or null when unavailable.
	 *
	 * @spec openspec/specs/portal-signing-actions/spec.md
	 */
	public function resolve(array $signingRequest): ?File;
}//end interface
