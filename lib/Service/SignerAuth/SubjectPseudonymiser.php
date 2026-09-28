<?php

/**
 * Subject Pseudonymiser
 *
 * Turns a subject identifier into a per-instance pseudonym before anything
 * stores it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
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

namespace OCA\Filinq\Service\SignerAuth;

use OCP\IConfig;
use RuntimeException;

/**
 * A keyed hash of issuer and subject, salted with the instance secret.
 *
 * Filinq cannot tell whether a broker's `sub` is pairwise or embeds a BSN,
 * so every subject is hashed, always (REQ-DDSIR-004: "providers MUST hash
 * any non-pairwise subject with a per-instance salt"). The output is letters
 * only, so no stored pseudonym can ever look like a nine-digit BSN. The same
 * signer at the same broker gets the same pseudonym on this instance, which
 * is what a dispute needs; another instance gets a different one.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SubjectPseudonymiser {

	/**
	 * Constructor.
	 *
	 * @param IConfig $config System config (holds the instance secret).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IConfig $config,
	) {

	}//end __construct()

	/**
	 * The pseudonym for a subject at an issuer.
	 *
	 * @param string $issuer The asserting party (an OIDC issuer, `nextcloud`, `portaliq`).
	 * @param string $subject The subject identifier as the issuer gave it.
	 *
	 * @return string `ps-` followed by 40 lowercase letters.
	 *
	 * @throws RuntimeException When the subject is empty or the instance has no secret.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function pseudonym(string $issuer, string $subject): string {
		if ($subject === '') {
			throw new RuntimeException('Cannot pseudonymise an empty subject');
		}

		$secret = $this->config->getSystemValueString('secret', '');
		if ($secret === '') {
			throw new RuntimeException('This instance has no secret to salt signer pseudonyms with');
		}

		$key = hash_hmac('sha256', 'filinq-signer-pseudonym', $secret);
		$digest = substr(hash_hmac('sha256', $issuer . "\n" . $subject, $key), 0, 40);

		return 'ps-' . strtr($digest, '0123456789abcdef', 'ghijklmnopabcdef');

	}//end pseudonym()
}//end class
