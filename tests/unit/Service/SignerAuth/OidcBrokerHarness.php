<?php

/**
 * OIDC broker test harness
 *
 * Builds an `oidc-broker` provider against in-memory doubles: app config,
 * session, HTTP client and the OpenRegister credential broker. Shared by the
 * provider's own test and its run through the provider contract.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SignerAuth;

use OCA\Filinq\Service\SignerAuth\BrokerCredentialResolver;
use OCA\Filinq\Service\SignerAuth\OidcBrokerProvider;
use OCA\Filinq\Service\SignerAuth\OidcPendingAuthentications;
use OCA\Filinq\Service\SignerAuth\OidcTokenExchange;
use OCA\Filinq\Service\SignerAuth\SignerAuthSettings;
use OCA\Filinq\Service\SignerAuth\SubjectPseudonymiser;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\ISession;
use OCP\Security\ISecureRandom;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Doubles and builders for the OIDC broker provider.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait OidcBrokerHarness {

	/**
	 * App config values, as the IAppConfig double serves them.
	 *
	 * @var array<string, string>
	 */
	protected array $appConfig = [];

	/**
	 * Session values, as the ISession double serves them.
	 *
	 * @var array<string, mixed>
	 */
	protected array $sessionData = [];

	/**
	 * The claims the next token response carries in its ID token.
	 *
	 * @var array<string, mixed>
	 */
	protected array $nextClaims = [];

	/**
	 * The raw ID token the last token response carried.
	 *
	 * @var string
	 */
	protected string $lastIdToken = '';

	/**
	 * Every token request the provider made: url and options.
	 *
	 * @var list<array{url: string, options: array<string, mixed>}>
	 */
	protected array $tokenRequests = [];

	/**
	 * Every credential the broker was asked for.
	 *
	 * @var list<string>
	 */
	public array $resolvedRefs = [];

	/**
	 * Everything the logger received.
	 *
	 * @var list<string>
	 */
	protected array $logLines = [];

	/**
	 * The secret the credential broker holds behind the reference.
	 *
	 * @var string
	 */
	public string $brokerSecret = 'the-broker-client-secret-value';

	/**
	 * The credential reference the settings hold.
	 *
	 * @var string
	 */
	protected string $credentialRef = '3f1c2b9a-7d4e-4f5a-9b8c-1a2b3c4d5e6f';

	/**
	 * A configured broker.
	 *
	 * @return void
	 */
	protected function configureBroker(): void {
		$this->appConfig = [
			'signer_auth_oidc_issuer' => 'https://broker.example.nl',
			'signer_auth_oidc_client_id' => 'filinq-client',
			'signer_auth_oidc_redirect_uri' => 'https://cloud.example.nl/apps/filinq/api/signing/identity/callback',
			'signer_auth_oidc_authorization_endpoint' => 'https://broker.example.nl/authorize',
			'signer_auth_oidc_token_endpoint' => 'https://broker.example.nl/token',
			'signer_auth_oidc_credential_ref' => $this->credentialRef,
		];

	}//end configureBroker()

	/**
	 * The settings, over the in-memory app config.
	 *
	 * @return SignerAuthSettings
	 */
	protected function settings(): SignerAuthSettings {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => $this->appConfig[$key] ?? $default
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->appConfig[$key] = $value;
				return true;
			}
		);

		return new SignerAuthSettings(config: $config);

	}//end settings()

	/**
	 * The instance-salted pseudonymiser.
	 *
	 * @return SubjectPseudonymiser
	 */
	protected function pseudonymiser(): SubjectPseudonymiser {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')->willReturnCallback(
			fn (string $key, string $default = ''): string => $key === 'secret' ? 'instance-secret-for-tests' : $default
		);

		return new SubjectPseudonymiser(config: $config);

	}//end pseudonymiser()

	/**
	 * The resolver, over a fake OpenRegister credential broker.
	 *
	 * @param object|null $broker The broker double; a working one when null.
	 *
	 * @return BrokerCredentialResolver
	 */
	protected function resolver(?object $broker = null): BrokerCredentialResolver {
		$test = $this;
		$broker ??= new class ($test) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test that records the calls.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * Resolve an inject-only credential.
			 *
			 * @param string $credentialId The credential id.
			 * @param string $appId The calling app.
			 *
			 * @return string|null
			 */
			public function resolveInjectable(string $credentialId, string $appId): ?string {
				$this->test->resolvedRefs[] = $credentialId . '@' . $appId;
				return $this->test->brokerSecret;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('has')->willReturn(true);
		$container->method('get')->willReturn($broker);

		return new BrokerCredentialResolver(container: $container, logger: $this->logger());

	}//end resolver()

	/**
	 * A logger that keeps every line.
	 *
	 * @return LoggerInterface
	 */
	protected function logger(): LoggerInterface {
		$logger = $this->createMock(LoggerInterface::class);
		foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string $message, array $context = []): void {
					$this->logLines[] = $message . ' ' . json_encode($context);
				}
			);
		}

		return $logger;

	}//end logger()

	/**
	 * The session double.
	 *
	 * @return ISession
	 */
	protected function session(): ISession {
		$session = $this->createMock(ISession::class);
		$session->method('get')->willReturnCallback(fn (string $key): mixed => $this->sessionData[$key] ?? null);
		$session->method('set')->willReturnCallback(
			function (string $key, mixed $value): void {
				$this->sessionData[$key] = $value;
			}
		);
		$session->method('remove')->willReturnCallback(
			function (string $key): void {
				unset($this->sessionData[$key]);
			}
		);

		return $session;

	}//end session()

	/**
	 * The HTTP client double: every POST answers a token response.
	 *
	 * @return IClientService
	 */
	protected function clientService(): IClientService {
		$client = $this->createMock(IClient::class);
		$client->method('post')->willReturnCallback(
			function (string $url, array $options = []): IResponse {
				$this->tokenRequests[] = ['url' => $url, 'options' => $options];
				$this->lastIdToken = $this->idToken(claims: $this->nextClaims);
				$response = $this->createMock(IResponse::class);
				$response->method('getStatusCode')->willReturn(200);
				$response->method('getBody')->willReturn(
					(string)json_encode(['access_token' => 'at-not-kept', 'id_token' => $this->lastIdToken, 'token_type' => 'Bearer'])
				);
				return $response;
			}
		);

		$service = $this->createMock(IClientService::class);
		$service->method('newClient')->willReturn($client);

		return $service;

	}//end clientService()

	/**
	 * A secure random double that counts.
	 *
	 * @return ISecureRandom
	 */
	protected function secureRandom(): ISecureRandom {
		$random = $this->createMock(ISecureRandom::class);
		$counter = 0;
		$random->method('generate')->willReturnCallback(
			function (int $length) use (&$counter): string {
				$counter++;
				return str_pad('r' . $counter, $length, 'x');
			}
		);

		return $random;

	}//end secureRandom()

	/**
	 * The provider, over every double.
	 *
	 * @param object|null $broker The credential broker double; a working one when null.
	 *
	 * @return OidcBrokerProvider
	 */
	protected function broker(?object $broker = null): OidcBrokerProvider {
		$settings = $this->settings();

		return new OidcBrokerProvider(
			settings: $settings,
			pending: new OidcPendingAuthentications(session: $this->session(), secureRandom: $this->secureRandom()),
			exchange: new OidcTokenExchange(
				settings: $settings,
				clientService: $this->clientService(),
				credentials: $this->resolver(broker: $broker)
			),
			pseudonymiser: $this->pseudonymiser()
		);

	}//end broker()

	/**
	 * Valid ID token claims for a nonce.
	 *
	 * @param string $nonce The nonce the authorize URL carried.
	 * @param string $acr The acr the broker reports.
	 *
	 * @return array<string, mixed>
	 */
	protected function claimsFor(string $nonce, string $acr): array {
		return [
			'iss' => 'https://broker.example.nl',
			'aud' => 'filinq-client',
			'sub' => 'pairwise-subject-abc',
			'exp' => time() + 300,
			'iat' => time(),
			'nonce' => $nonce,
			'acr' => $acr,
		];

	}//end claimsFor()

	/**
	 * An unsigned-looking compact JWT with the given claims.
	 *
	 * @param array<string, mixed> $claims The payload.
	 *
	 * @return string
	 */
	protected function idToken(array $claims): string {
		$encode = static fn (array $part): string => rtrim(strtr(base64_encode((string)json_encode($part)), '+/', '-_'), '=');

		return $encode(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $encode($claims) . '.c2lnbmF0dXJl';

	}//end idToken()

	/**
	 * A query parameter of a URL.
	 *
	 * @param string $url The URL.
	 * @param string $name The parameter.
	 *
	 * @return string
	 */
	protected function queryParam(string $url, string $name): string {
		parse_str((string)parse_url($url, PHP_URL_QUERY), $query);

		return (string)($query[$name] ?? '');

	}//end queryParam()
}//end trait
