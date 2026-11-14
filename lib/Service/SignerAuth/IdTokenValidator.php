<?php

/**
 * ID Token Validator
 *
 * Checks the claims of an ID token received from a broker's token endpoint.
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

use RuntimeException;

/**
 * `iss`, `aud`, `azp`, `exp`, `iat`, `nonce` and `sub`, fail-closed.
 *
 * The token arrives over a direct, server-side TLS call to the configured
 * https token endpoint, never through the browser. OpenID Connect Core 1.0
 * section 3.1.3.7 allows the TLS server validation to take the place of the
 * token signature check in exactly that case, which is why this class checks
 * claims and not a JWKS signature. The endpoints are refused unless they are
 * https (SignerAuthSettings::isBrokerConfigured()).
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class IdTokenValidator {

	/**
	 * Tolerated clock skew, in seconds.
	 *
	 * @var int
	 */
	private const SKEW = 60;

	/**
	 * The validated claims of a compact ID token.
	 *
	 * @param string $idToken The raw token.
	 * @param string $issuer The configured issuer.
	 * @param string $clientId The configured client id.
	 * @param string $nonce The nonce sent in the authorize request.
	 * @param int $now The current Unix time.
	 *
	 * @return array<string, mixed> The claims.
	 *
	 * @throws RuntimeException When the token is malformed or any check fails; the message names the check.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function validate(string $idToken, string $issuer, string $clientId, string $nonce, int $now): array {
		$parts = explode('.', $idToken);
		if (count($parts) !== 3) {
			throw new RuntimeException('ID token is not a compact JWT');
		}

		$header = $this->decodePart(part: $parts[0]);
		if (strtolower((string)($header['alg'] ?? 'none')) === 'none') {
			throw new RuntimeException('ID token declares no algorithm');
		}

		$claims = $this->decodePart(part: $parts[1]);

		$this->check(passed: ($claims['iss'] ?? null) === $issuer, what: 'issuer');
		$this->check(passed: $this->audienceIsUs(claims: $claims, clientId: $clientId), what: 'audience');
		$this->check(passed: is_numeric($claims['exp'] ?? null) === true && (int)$claims['exp'] > $now - self::SKEW, what: 'expiry');
		$this->check(passed: is_numeric($claims['iat'] ?? null) === true && (int)$claims['iat'] <= $now + self::SKEW, what: 'issued-at');
		$this->check(passed: $nonce !== '' && hash_equals($nonce, (string)($claims['nonce'] ?? '')) === true, what: 'nonce');
		$this->check(passed: is_string($claims['sub'] ?? null) === true && $claims['sub'] !== '', what: 'subject');

		return $claims;

	}//end validate()

	/**
	 * Is this client the audience, and the authorised party when there are several.
	 *
	 * @param array<string, mixed> $claims The claims.
	 * @param string $clientId The configured client id.
	 *
	 * @return bool True when the token was issued to this client.
	 */
	private function audienceIsUs(array $claims, string $clientId): bool {
		$audience = $claims['aud'] ?? null;
		if (is_string($audience) === true) {
			return $audience === $clientId;
		}

		if (is_array($audience) === false || in_array($clientId, $audience, true) === false) {
			return false;
		}

		return count($audience) === 1 || ($claims['azp'] ?? null) === $clientId;

	}//end audienceIsUs()

	/**
	 * Throw when a check fails.
	 *
	 * @param bool $passed The outcome.
	 * @param string $what The check's name.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When `$passed` is false.
	 */
	private function check(bool $passed, string $what): void {
		if ($passed === false) {
			throw new RuntimeException('ID token failed the ' . $what . ' check');
		}

	}//end check()

	/**
	 * Decode one base64url JSON part.
	 *
	 * @param string $part The encoded part.
	 *
	 * @return array<string, mixed> The decoded object.
	 *
	 * @throws RuntimeException When it is not base64url JSON.
	 */
	private function decodePart(string $part): array {
		$json = base64_decode(strtr($part, '-_', '+/'), true);
		$decoded = json_decode((string)$json, true);
		if (is_array($decoded) === false) {
			throw new RuntimeException('ID token part is not JSON');
		}

		return $decoded;

	}//end decodePart()
}//end class
