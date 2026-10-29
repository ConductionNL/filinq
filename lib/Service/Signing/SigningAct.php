<?php

/**
 * Signing Act
 *
 * Who signs which signer record on which request, and when.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;

/**
 * The resolved actor and target of one signature.
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
 */
class SigningAct {

	/**
	 * @param string                    $requestId        The signing request ID
	 * @param string                    $signerId         The signer record ID
	 * @param array<string, mixed>|null $verifiedActor    The verified external actor, or null for the session user
	 * @param string                    $actorUserId      The acting user ID
	 * @param string                    $actorDisplayName The acting user's display name
	 * @param DateTimeImmutable         $now              The moment of the act
	 */
	public function __construct(
		public readonly string $requestId,
		public readonly string $signerId,
		public readonly ?array $verifiedActor,
		public readonly string $actorUserId,
		public readonly string $actorDisplayName,
		public readonly DateTimeImmutable $now,
	) {
	}//end __construct()
}//end class
