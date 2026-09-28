<?php

/**
 * Portal Signature Assurance
 *
 * The assurance level a signature made through the portal may claim: never
 * above what the portal session's trust supports, and never QES.
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

/**
 * Caps the assurance level recorded on a portal signature.
 *
 * A portal session at `substantial` or `high` trust supports at most AES
 * evidence; any lower or unknown trust supports SES only. A qualified
 * signature needs a qualified trust service provider, which the portal is
 * not, so a QES request is recorded at the cap, never as QES.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/portal-signing-surface/spec.md
 */
class PortalSignatureAssurance {

	/**
	 * The levels a portal signature may record, lowest first.
	 *
	 * @var string[]
	 */
	public const LEVELS = ['SES', 'AES'];

	/**
	 * The portal trust levels that support AES evidence.
	 *
	 * @var string[]
	 */
	private const AES_TRUST = ['substantial', 'high'];

	/**
	 * The level to record for a portal signature.
	 *
	 * @param string $requestedLevel The level the signing request asked for.
	 * @param string $trust          The verified portal session trust.
	 *
	 * @return string SES or AES, never QES.
	 *
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 */
	public function levelFor(string $requestedLevel, string $trust): string {
		$cap = 'SES';
		if (in_array($trust, self::AES_TRUST, true) === true) {
			$cap = 'AES';
		}

		// A QES request is capped, not refused here: the portal never delivers
		// a qualified signature, so the most it can honestly record is the cap.
		$requested = $requestedLevel;
		if ($requested === 'QES') {
			$requested = $cap;
		}

		$requestedIndex = array_search($requested, self::LEVELS, true);
		if ($requestedIndex === false) {
			return self::LEVELS[0];
		}

		$capIndex = (int)array_search($cap, self::LEVELS, true);

		return self::LEVELS[min((int)$requestedIndex, $capIndex)];

	}//end levelFor()

	/**
	 * Record the capped level on a signer a portal actor is signing as.
	 *
	 * A signer acting in Nextcloud has no verified portal actor and gets no
	 * field: the cap describes a portal session, not a Nextcloud login.
	 *
	 * @param array<string, mixed>      $signer        The signer record about to be saved.
	 * @param array<string, mixed>      $request       The signing request.
	 * @param array<string, mixed>|null $verifiedActor The verified portal actor, or null in-app.
	 *
	 * @return array<string, mixed> The signer, with `signatureAssurance` for a portal actor.
	 *
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 */
	public function recordOn(array $signer, array $request, ?array $verifiedActor): array {
		if ($verifiedActor === null) {
			return $signer;
		}

		$signer['signatureAssurance'] = $this->levelFor(
			requestedLevel: (string)($request['signatureLevel'] ?? 'SES'),
			trust: (string)($verifiedActor['trust'] ?? '')
		);

		return $signer;

	}//end recordOn()
}//end class
