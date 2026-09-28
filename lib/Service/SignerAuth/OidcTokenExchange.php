<?php

/**
 * OIDC Token Exchange
 *
 * Exchanges an authorization code for an ID token at the broker's token
 * endpoint, server-side.
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

use OCP\Http\Client\IClientService;
use RuntimeException;
use Throwable;

/**
 * The one place the broker's client secret is used.
 *
 * The secret is resolved from the `credentialRef` for this request only
 * and goes nowhere but the token endpoint's form body. Errors name the
 * exception class, never the response or the secret.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class OidcTokenExchange {

	/**
	 * Constructor.
	 *
	 * @param SignerAuthSettings $settings The broker's configuration.
	 * @param IClientService $clientService HTTP client factory.
	 * @param BrokerCredentialResolver $credentials Resolves the client secret now.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SignerAuthSettings $settings,
		private readonly IClientService $clientService,
		private readonly BrokerCredentialResolver $credentials,
	) {

	}//end __construct()

	/**
	 * The raw ID token for an authorization code.
	 *
	 * @param string $code The authorization code.
	 *
	 * @return string The ID token, for validation and hashing only.
	 *
	 * @throws RuntimeException When the secret cannot be resolved, the call fails, or no ID token comes back.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function idTokenFor(string $code): string {
		$secret = $this->credentials->resolve(credentialRef: $this->settings->broker(field: 'credentialRef'));

		try {
			$response = $this->clientService->newClient()->post(
				$this->settings->broker(field: 'tokenEndpoint'),
				[
					'form_params' => [
						'grant_type' => 'authorization_code',
						'code' => $code,
						'redirect_uri' => $this->settings->broker(field: 'redirectUri'),
						'client_id' => $this->settings->broker(field: 'clientId'),
						'client_secret' => $secret,
					],
					'timeout' => 15,
				]
			);
			$status = $response->getStatusCode();
			$body = json_decode((string)$response->getBody(), true);
		} catch (Throwable $e) {
			throw new RuntimeException('The identity broker token exchange failed (' . get_class($e) . ')');
		}

		if ($status !== 200 || is_array($body) === false || is_string($body['id_token'] ?? null) === false) {
			throw new RuntimeException('The identity broker returned no ID token');
		}

		return $body['id_token'];

	}//end idTokenFor()
}//end class
