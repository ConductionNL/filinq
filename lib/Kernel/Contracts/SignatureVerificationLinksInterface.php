<?php

/**
 * Contract of SignatureVerificationLinkService
 *
 * The public surface of the SignatureVerificationLinkService service, so callers in other
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
 * Contract of SignatureVerificationLinkService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SignatureVerificationLinksInterface {
	/**
	 * The public verdict for a token: the verdict of the record it names, or
	 * the `unknown` verdict when it names none.
	 *
	 * @param string $token The token from the verification link.
	 *
	 * @return array<string, mixed> The verdict.
	 *
	 * @throws RuntimeException When the records cannot be read.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	public function verdictFor(string $token): array;

	/**
	 * The verdict for a token that resolves to nothing, in the shape of every other verdict.
	 *
	 * @return array<string, mixed> The neutral `unknown` verdict.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.3
	 */
	public static function unknownVerdict(): array;
}//end interface
