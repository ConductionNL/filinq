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
use DateTimeInterface;

/**
 * The resolved actor and target of one signature, and the stamp it puts on a signer record.
 *
 * @spec openspec/changes/digital-signing-integration/tasks.md#3-3
 */
class SigningAct {

	/**
	 * The signing request ID.
	 *
	 * @var string
	 */
	public readonly string $requestId;

	/**
	 * The signer record ID.
	 *
	 * @var string
	 */
	public readonly string $signerId;

	/**
	 * The verified external actor, or null for the session user.
	 *
	 * @var array<string, mixed>|null
	 */
	public readonly ?array $verifiedActor;

	/**
	 * The moment of the act.
	 *
	 * @var DateTimeImmutable
	 */
	public readonly DateTimeImmutable $now;

	/**
	 * @param array<string, mixed>                       $request       The signing request
	 * @param array{requestId: string, signerId: string} $target        The request and signer record IDs
	 * @param array<string, mixed>                       $actor         userId, displayName and the verified external actor (verified, or null)
	 * @param array<string, mixed>|null                  $signatureData Consent and optional drawn signature, never used for identity
	 */
	public function __construct(
		public readonly array $request,
		array $target,
		public readonly array $actor,
		public readonly ?array $signatureData,
	) {
		$this->requestId     = $target['requestId'];
		$this->signerId      = $target['signerId'];
		$this->verifiedActor = $actor['verified'];
		$this->now           = new DateTimeImmutable();
	}//end __construct()


	/**
	 * Mark a signer record SIGNED at this act's moment from the given address.
	 *
	 * @param array<string, mixed> $signer    The signer record
	 * @param string               $ipAddress The client address of the act
	 *
	 * @return array<string, mixed> The signed record
	 *
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 */
	public function stampSigned(array $signer, string $ipAddress): array {
		$signer['status']    = 'SIGNED';
		$signer['signedAt']  = $this->now->format(DateTimeInterface::ATOM);
		$signer['ipAddress'] = $ipAddress;
		if ($this->signatureData !== null) {
			$signer['signatureData'] = $this->signatureData;
		}

		return $signer;
	}//end stampSigned()
}//end class
