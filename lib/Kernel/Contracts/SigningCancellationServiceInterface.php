<?php

/**
 * Contract of SigningCancellationService
 *
 * The public surface of SigningCancellationService that other components call, so they depend
 * on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction b.v. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://github.com/ConductionNL/filinq
 *
 * @spec openspec/specs/method-decomposition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use RuntimeException;

/**
 * Contract of SigningCancellationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction b.v. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/filinq
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningCancellationServiceInterface {

	/**
	 * Withdraw a signing request.
	 *
	 * @param string $uid The acting user id.
	 * @param string $requestId The signing request id.
	 *
	 * @return array{requestId: string, status: string, alreadyCancelled: bool}
	 *
	 * @throws RuntimeException When the actor is not the creator, or the withdrawal fails.
	 *
	 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
	 *
	 * The order is fixed: authorise, then the state check, then the provider,
	 * then the request's own transition. Nothing reaches the provider for an
	 * actor who may not cancel or for a request that can no longer be withdrawn,
	 * and the request is only marked CANCELLED once the provider has withdrawn it.
	 */
	public function cancel(string $uid, string $requestId): array;
}//end interface
