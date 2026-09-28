<?php

/**
 * OIDC Broker Provider
 *
 * Establishes a signer's identity through any OpenID Connect broker that
 * fronts DigiD, eHerkenning and iDIN, and maps the broker's `acr` onto the
 * eIDAS assurance scale.
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

use DateTimeImmutable;
use RuntimeException;

/**
 * The authorization code flow, with the code exchanged server-side.
 *
 * `initiateAuthentication()` keeps a single-use `state` and a `nonce` in the
 * signer's own server-side session, bound to the request, the signer and the
 * user, and returns the broker's authorize URL. `completeAuthentication()`
 * accepts that state once, for exactly that act, exchanges the code with the
 * secret resolved from the `credentialRef` at that moment, validates the ID
 * token and maps `acr` to assurance. An `acr` nobody mapped is `low`.
 *
 * What is kept: a salted pseudonym of `sub` and the sha256 of the ID token.
 * What is not: the ID token, the access token, the secret, the `sub` itself.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class OidcBrokerProvider implements SignerAuthenticationProviderInterface {

	/**
	 * The provider identifier.
	 *
	 * @var string
	 */
	public const IDENTIFIER = 'oidc-broker';

	/**
	 * The documented default acr mapping (design D2).
	 *
	 * DigiD's levels are the AuthnContextClassRefs Logius publishes (Basis,
	 * Midden, Substantieel, Hoog); eHerkenning's are the eToegang assurance
	 * classes. iDIN has no standard acr: the key below is a placeholder, and
	 * an admin maps their broker's own iDIN value in the settings.
	 *
	 * @var array<string, array{means: string, assurance: string}>
	 */
	public const DEFAULT_ACR_MAPPING = [
		'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport' => ['means' => 'digid', 'assurance' => 'low'],
		'urn:oasis:names:tc:SAML:2.0:ac:classes:MobileTwoFactorContract' => ['means' => 'digid', 'assurance' => 'substantial'],
		'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard' => ['means' => 'digid', 'assurance' => 'substantial'],
		'urn:oasis:names:tc:SAML:2.0:ac:classes:SmartcardPKI' => ['means' => 'digid', 'assurance' => 'high'],
		'urn:etoegang:core:assurance-class:loa2plus' => ['means' => 'eherkenning', 'assurance' => 'low'],
		'urn:etoegang:core:assurance-class:loa3' => ['means' => 'eherkenning', 'assurance' => 'substantial'],
		'urn:etoegang:core:assurance-class:loa4' => ['means' => 'eherkenning', 'assurance' => 'high'],
		'urn:nl:idin:bank-verified' => ['means' => 'idin', 'assurance' => 'substantial'],
	];

	/**
	 * Validates the ID token's claims.
	 *
	 * @var IdTokenValidator
	 */
	private readonly IdTokenValidator $validator;

	/**
	 * The assurance scale.
	 *
	 * @var AssuranceLevel
	 */
	private readonly AssuranceLevel $levels;

	/**
	 * Constructor.
	 *
	 * @param SignerAuthSettings $settings The broker's configuration.
	 * @param OidcPendingAuthentications $pending Single-use states in the signer's session.
	 * @param OidcTokenExchange $exchange Exchanges the code, with the secret resolved then.
	 * @param SubjectPseudonymiser $pseudonymiser Salts `sub` before it is kept.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SignerAuthSettings $settings,
		private readonly OidcPendingAuthentications $pending,
		private readonly OidcTokenExchange $exchange,
		private readonly SubjectPseudonymiser $pseudonymiser,
	) {
		$this->validator = new IdTokenValidator();
		$this->levels = new AssuranceLevel();

	}//end __construct()

	/**
	 * The identifier.
	 *
	 * @return string `oidc-broker`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;

	}//end getIdentifier()

	/**
	 * The means the mapping knows, plus `oidc` for an acr it does not.
	 *
	 * @return list<string>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getSupportedMeans(): array {
		$means = array_column($this->mapping(), 'means');
		$means[] = 'oidc';

		return array_values(array_unique($means));

	}//end getSupportedMeans()

	/**
	 * Every level: which ones a signer reaches depends on the broker's acr.
	 *
	 * @return list<string>
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getSupportedAssurance(): array {
		return AssuranceLevel::LEVELS;

	}//end getSupportedAssurance()

	/**
	 * Open an authentication and return the broker's authorize URL.
	 *
	 * @param SignerAuthContext $context The signing act.
	 *
	 * @return AuthChallenge A redirect challenge.
	 *
	 * @throws RuntimeException When the broker is not configured.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function initiateAuthentication(SignerAuthContext $context): AuthChallenge {
		if ($this->settings->isBrokerConfigured() === false) {
			throw new RuntimeException('The identity broker is not configured');
		}

		['state' => $state, 'nonce' => $nonce] = $this->pending->open(context: $context);

		$query = [
			'response_type' => 'code',
			'client_id' => $this->settings->broker(field: 'clientId'),
			'redirect_uri' => $this->settings->broker(field: 'redirectUri'),
			'scope' => $this->settings->broker(field: 'scopes'),
			'state' => $state,
			'nonce' => $nonce,
			'prompt' => 'login',
		];

		$acrValues = $this->acrValuesMeeting(required: $context->requiredAssurance);
		if ($acrValues !== []) {
			$query['acr_values'] = implode(' ', $acrValues);
		}

		$endpoint = $this->settings->broker(field: 'authorizationEndpoint');
		$separator = '?';
		if (str_contains($endpoint, '?') === true) {
			$separator = '&';
		}

		return new AuthChallenge(
			provider: self::IDENTIFIER,
			type: AuthChallenge::TYPE_REDIRECT,
			url: $endpoint . $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
		);

	}//end initiateAuthentication()

	/**
	 * The act an open state belongs to, without consuming it.
	 *
	 * The callback carries only `code` and `state`; this tells the caller
	 * which request and signer to complete for.
	 *
	 * @param string $state The state from the callback.
	 *
	 * @return array{requestId: string, signerId: string, userId: string}|null The bound act, or null.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function boundAct(string $state): ?array {
		$entry = $this->pending->peek(state: $state);
		if ($entry === null) {
			return null;
		}

		return ['requestId' => $entry['requestId'], 'signerId' => $entry['signerId'], 'userId' => $entry['userId']];

	}//end boundAct()

	/**
	 * Accept the callback once, exchange the code and derive the evidence.
	 *
	 * @param array<string, mixed> $callbackData `code`, `state`, and the `requestId`,
	 *                                           `signerId` and `userId` it must be bound to.
	 *
	 * @return IdentityEvidence The evidence.
	 *
	 * @throws RuntimeException When the state is unknown, used, expired or bound to another act,
	 *                          or the exchange or the token fails.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function completeAuthentication(array $callbackData): IdentityEvidence {
		$code = (string)($callbackData['code'] ?? '');
		// Single use: the state is gone whatever happens next.
		$entry = $this->pending->take(state: (string)($callbackData['state'] ?? ''));
		if ($code === '' || $entry === null) {
			throw new RuntimeException('Unknown or already used authentication state');
		}

		$bound = [$entry['requestId'], $entry['signerId'], $entry['userId']];
		$claimed = [
			(string)($callbackData['requestId'] ?? ''),
			(string)($callbackData['signerId'] ?? ''),
			(string)($callbackData['userId'] ?? ''),
		];
		if ($bound !== $claimed) {
			throw new RuntimeException('This authentication was started for another signing act');
		}

		$idToken = $this->exchange->idTokenFor(code: $code);
		$issuer = $this->settings->broker(field: 'issuer');
		$claims = $this->validator->validate(
			idToken: $idToken,
			issuer: $issuer,
			clientId: $this->settings->broker(field: 'clientId'),
			nonce: $entry['nonce'],
			now: time()
		);

		$mapped = $this->mapping()[(string)($claims['acr'] ?? '')] ?? ['means' => 'oidc', 'assurance' => 'low'];

		return new IdentityEvidence(
			provider: self::IDENTIFIER,
			means: $mapped['means'],
			assurance: $mapped['assurance'],
			subjectPseudonym: $this->pseudonymiser->pseudonym(issuer: $issuer, subject: (string)$claims['sub']),
			authenticatedAt: $this->authenticatedAt(claims: $claims),
			evidenceHash: hash('sha256', $idToken)
		);

	}//end completeAuthentication()

	/**
	 * When the signer authenticated at the broker.
	 *
	 * `auth_time` when the broker sends a plausible one, else now. An old
	 * `auth_time` (a broker single sign-on session) makes the evidence stale
	 * at the gate, which is the point: the act needs a fresh login.
	 *
	 * @param array<string, mixed> $claims The validated claims.
	 *
	 * @return DateTimeImmutable The moment.
	 */
	private function authenticatedAt(array $claims): DateTimeImmutable {
		$now = time();
		$authTime = $claims['auth_time'] ?? null;
		if (is_numeric($authTime) === true && (int)$authTime <= $now + 60) {
			return (new DateTimeImmutable())->setTimestamp((int)$authTime);
		}

		return (new DateTimeImmutable())->setTimestamp($now);

	}//end authenticatedAt()

	/**
	 * The effective acr mapping: the defaults, with the admin's entries on top.
	 *
	 * @return array<string, array{means: string, assurance: string}>
	 */
	private function mapping(): array {
		return array_merge(self::DEFAULT_ACR_MAPPING, $this->settings->acrMapping());

	}//end mapping()

	/**
	 * The acr values that meet a required assurance.
	 *
	 * @param string $required The required assurance.
	 *
	 * @return list<string>
	 */
	private function acrValuesMeeting(string $required): array {
		$values = [];
		foreach ($this->mapping() as $acr => $entry) {
			if ($this->levels->meets(held: $entry['assurance'], required: $this->levels->normalise(value: $required)) === true) {
				$values[] = $acr;
			}
		}

		return $values;

	}//end acrValuesMeeting()

}//end class
