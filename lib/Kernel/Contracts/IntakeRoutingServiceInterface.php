<?php

/**
 * Contract of IntakeRoutingService
 *
 * The public surface of the IntakeRoutingService service, so callers in other
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

use RuntimeException;

/**
 * Contract of IntakeRoutingService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakeRoutingServiceInterface {
	/**
	 * The schema holding the declarations.
	 *
	 * @var string
	 */
	public const SCHEMA = 'intakeRoutingRule';

	/**
	 * Store one declaration, replacing the app's earlier one for that type.
	 *
	 * @param string $declaringApp The app id making the declaration.
	 * @param string $typeReference The record type, in that app's vocabulary.
	 * @param string $routeTo The group inbound documents go to, or an empty string.
	 * @param bool $requiresAcceptance Whether the group has to accept the document.
	 *
	 * @return array<string, mixed> The stored declaration.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function declare(string $declaringApp, string $typeReference, string $routeTo, bool $requiresAcceptance): array;

	/**
	 * The routing that applies to one record type.
	 *
	 * @param string $declaringApp The app that owns the record type.
	 * @param string $typeReference The record type.
	 *
	 * @return array<string, mixed>|null The declaration, or null when there is none.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function find(string $declaringApp, string $typeReference): ?array;

	/**
	 * Apply the declaration of one record type to a document being filed.
	 *
	 * @param array<string, mixed> $document The intake document being assigned.
	 * @param string $declaringApp The app that owns the target record type.
	 * @param string $typeReference The target record type.
	 *
	 * @return array<string, mixed> The document, routed or deliberately not.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function apply(array $document, string $declaringApp, string $typeReference): array;
}//end interface
