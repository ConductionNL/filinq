<?php

/**
 * Nextcloud Session Provider
 *
 * The signer is who their Nextcloud session says they are, at `low`.
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
use DateTimeInterface;
use OCP\IUserSession;
use RuntimeException;

/**
 * The default provider: today's behaviour, named.
 *
 * A Nextcloud login authenticates the account, not the signing act, and says
 * nothing about how strongly; so this provider never asserts more than
 * `low` (design D2). There is nothing to redirect to: the session is the
 * authentication, and completing it means confirming that the session user
 * is the one claiming the act.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class NextcloudSessionProvider implements SignerAuthenticationProviderInterface {

	/**
	 * The provider identifier.
	 *
	 * @var string
	 */
	public const IDENTIFIER = 'nextcloud-session';

	/**
	 * Constructor.
	 *
	 * @param IUserSession $userSession The current session.
	 * @param SubjectPseudonymiser $pseudonymiser Salts the uid before it is stored.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IUserSession $userSession,
		private readonly SubjectPseudonymiser $pseudonymiser,
	) {

	}//end __construct()

	/**
	 * The identifier.
	 *
	 * @return string `nextcloud-session`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;

	}//end getIdentifier()

	/**
	 * The means.
	 *
	 * @return list<string> `['nc-session']`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getSupportedMeans(): array {
		return ['nc-session'];

	}//end getSupportedMeans()

	/**
	 * The assurance.
	 *
	 * @return list<string> `['low']`.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function getSupportedAssurance(): array {
		return ['low'];

	}//end getSupportedAssurance()

	/**
	 * Nothing to do: the session is already the authentication.
	 *
	 * @param SignerAuthContext $context The signing act.
	 *
	 * @return AuthChallenge A `none` challenge.
	 *
	 * @throws RuntimeException When there is no session user.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function initiateAuthentication(SignerAuthContext $context): AuthChallenge {
		$this->sessionUid();

		return new AuthChallenge(provider: self::IDENTIFIER, type: AuthChallenge::TYPE_NONE);

	}//end initiateAuthentication()

	/**
	 * Evidence for the session user, when they are the one claiming the act.
	 *
	 * @param array<string, mixed> $callbackData `requestId`, `signerId` and `userId`.
	 *
	 * @return IdentityEvidence Evidence at `low`, established now.
	 *
	 * @throws RuntimeException When an id is missing or the claimed user is not the session user.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function completeAuthentication(array $callbackData): IdentityEvidence {
		$requestId = (string)($callbackData['requestId'] ?? '');
		$signerId = (string)($callbackData['signerId'] ?? '');
		$claimed = (string)($callbackData['userId'] ?? '');
		if ($requestId === '' || $signerId === '' || $claimed === '') {
			throw new RuntimeException('A session authentication needs the request, the signer and the user');
		}

		$uid = $this->sessionUid();
		if ($uid !== $claimed) {
			throw new RuntimeException('The claimed user is not the session user');
		}

		$now = new DateTimeImmutable();

		return new IdentityEvidence(
			provider: self::IDENTIFIER,
			means: 'nc-session',
			assurance: 'low',
			subjectPseudonym: $this->pseudonymiser->pseudonym(issuer: 'nextcloud', subject: $uid),
			authenticatedAt: $now,
			evidenceHash: hash('sha256', implode("\n", [self::IDENTIFIER, $uid, $requestId, $signerId, $now->format(DateTimeInterface::ATOM)]))
		);

	}//end completeAuthentication()

	/**
	 * The session user's uid.
	 *
	 * @return string The uid.
	 *
	 * @throws RuntimeException When there is no session user.
	 */
	private function sessionUid(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new RuntimeException('No authenticated user');
		}

		return $user->getUID();

	}//end sessionUid()
}//end class
