<?php

/**
 * Contract of UploadPolicyService
 *
 * The public surface of the UploadPolicyService service, so callers in other
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

use OCA\Filinq\Exception\UploadRefusedException;

/**
 * Contract of UploadPolicyService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface UploadPolicyServiceInterface {
	/**
	 * The schema holding the policies.
	 *
	 * @var string
	 */
	public const SCHEMA = 'uploadPolicy';

	/**
	 * Check one file against the active policy, or refuse it.
	 *
	 * @param string $fileName The name the file arrived under.
	 * @param string $contents The bytes, which is what the media type is read from.
	 *
	 * @return array<string, mixed> What the check found: the detected type and the policy that allowed it.
	 *
	 * @throws UploadRefusedException When the policy refuses the file.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function check(string $fileName, string $contents): array;

	/**
	 * The policy in force, or null when none is declared.
	 *
	 * @return array<string, mixed>|null The active policy.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function activePolicy(): ?array;
}//end interface
