<?php

/**
 * Contract of SignerStepUpService
 *
 * The public surface of the SignerStepUpService service, so callers in other
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
 * Contract of SignerStepUpService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SignerStepUpServiceInterface {
	/**
	 * Start a step-up for one signer on one request.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 *
	 * @return array{provider: string, type: string, url: string, requiredAssurance: string}
	 *
	 * @throws RuntimeException When there is no session user, the signer is not theirs, or the provider cannot start.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function start(string $requestId, string $signerId): array;

	/**
	 * Authorise a broker callback against this session, then keep the evidence for its act.
	 *
	 * The state must be one this session opened, for this session's user;
	 * anything else is refused before the broker is called.
	 *
	 * @param string $code The authorization code.
	 * @param string $state The state the broker returned.
	 *
	 * @return array{requestId: string, signerId: string, assurance: string}
	 *
	 * @throws RuntimeException When the state is unknown or used, belongs to another user, or the broker fails.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function authorizeCallback(string $code, string $state): array;
}//end interface
