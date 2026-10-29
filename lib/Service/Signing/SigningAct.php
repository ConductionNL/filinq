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
	 * @param string                    $requestId     The signing request ID
	 * @param string                    $signerId      The signer record ID
	 * @param array<string, mixed>      $request       The signing request
	 * @param array<string, mixed>|null $verifiedActor The verified external actor, or null for the session user
	 * @param array{userId: string, displayName: string} $actor The acting user
	 * @param array<string, mixed>|null $signatureData Consent and optional drawn signature, never used for identity
	 * @param DateTimeImmutable         $now           The moment of the act
	 */
	public function __construct(
		public readonly string $requestId,
		public readonly string $signerId,
		public readonly array $request,
		public readonly ?array $verifiedActor,
		public readonly array $actor,
		public readonly ?array $signatureData,
		public readonly DateTimeImmutable $now,
	) {
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
