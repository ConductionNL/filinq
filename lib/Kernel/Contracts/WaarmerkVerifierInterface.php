<?php

/**
 * Contract of WaarmerkVerifier
 *
 * The public surface of the WaarmerkVerifier service, so callers in other
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
 * Contract of WaarmerkVerifier.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface WaarmerkVerifierInterface {
	public const VALID = 'valid';

	public const HASH_MISMATCH = 'hash-mismatch';

	public const REVOKED = 'revoked';

	public const UNKNOWN = 'unknown';

	/**
	 * Verify a code against the verifier's own sha256 of their file.
	 *
	 * @param string $code The verification code.
	 * @param string $sha256 The verifier's hex sha256.
	 *
	 * @return array<string, mixed> status plus the fixed set of keys.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function verify(string $code, string $sha256): array;

	/**
	 * What the human page shows for a code: status, organisation, time, document name.
	 *
	 * @param string $code The verification code.
	 *
	 * @return array<string, mixed> status active|revoked|unknown plus the fixed set of keys.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function page(string $code): array;
}
