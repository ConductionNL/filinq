<?php

/**
 * Contract of ExternalMountValidator
 *
 * The public surface of the ExternalMountValidator service, so callers in other
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
 * Contract of ExternalMountValidator.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ExternalMountValidatorInterface {
	/**
	 * A requirement the mount cannot meet.
	 *
	 * @var string
	 */
	public const CANNOT = 'cannot';

	/**
	 * A requirement nobody could find out about.
	 *
	 * @var string
	 */
	public const UNKNOWN = 'unknown';

	/**
	 * Validate the store a domain would live on.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return array{path: string, ok: bool,
	 *               findings: array<int, array{requirement: string, verdict: string, message: string}>}
	 *               What it can and cannot keep.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function validate(string $path): array;
}//end interface
