<?php

/**
 * Contract of SigningRetentionCheck
 *
 * The public surface of the SigningRetentionCheck service, so callers in other
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

/**
 * Contract of SigningRetentionCheck.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningRetentionCheckInterface {
	/**
	 * The legal basis the minimum comes from, named in every failure.
	 */
	public const LEGAL_BASIS = 'Archiefwet 1995';

	/**
	 * Evaluate one schema's retention.
	 *
	 * @param string $schemaSlug The schema being checked, named in the message.
	 * @param mixed $archive The schema's `archive` block.
	 * @param mixed $configuration The schema's configuration.
	 *
	 * @return array{schema: string, passed: bool, source: string, duration: string|null, days: int|null, message: string}
	 *
	 * @spec openspec/changes/signing-trust-rebuild/specs/signing-audit-via-or/spec.md
	 */
	public function evaluate(string $schemaSlug, mixed $archive, mixed $configuration): array;
}//end interface
