<?php

/**
 * OIDC Pending Authentications
 *
 * The open OIDC authentications of one signer's session: state, nonce and
 * the signing act each one is bound to.
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

use OCP\ISession;
use OCP\Security\ISecureRandom;

/**
 * Single-use states in the server-side session.
 *
 * The session is the signer's own, so a state started in one browser cannot
 * be completed in another (CSRF protection for the callback). A state lives
 * ten minutes and answers once; at most five are open at a time.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class OidcPendingAuthentications {

	/**
	 * The session key.
	 *
	 * @var string
	 */
	public const SESSION_KEY = 'filinq_signer_auth_pending';

	/**
	 * How long a state waits for its callback, in seconds.
	 *
	 * @var int
	 */
	private const TTL = 600;

	/**
	 * Constructor.
	 *
	 * @param ISession $session The signer's session.
	 * @param ISecureRandom $secureRandom Generates state and nonce.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ISession $session,
		private readonly ISecureRandom $secureRandom,
	) {

	}//end __construct()

	/**
	 * Open an authentication for one signing act.
	 *
	 * @param SignerAuthContext $context The signing act.
	 *
	 * @return array{state: string, nonce: string}
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function open(SignerAuthContext $context): array {
		$state = $this->secureRandom->generate(32, ISecureRandom::CHAR_ALPHANUMERIC);
		$nonce = $this->secureRandom->generate(32, ISecureRandom::CHAR_ALPHANUMERIC);

		$pending = $this->all();
		$pending[$state] = [
			'requestId' => $context->requestId,
			'signerId' => $context->signerId,
			'userId' => $context->userId,
			'nonce' => $nonce,
			'createdAt' => time(),
		];
		$this->session->set(self::SESSION_KEY, array_slice($pending, -5, null, true));

		return ['state' => $state, 'nonce' => $nonce];

	}//end open()

	/**
	 * The open authentication for a state, left open.
	 *
	 * @param string $state The state.
	 *
	 * @return array{requestId: string, signerId: string, userId: string, nonce: string}|null
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function peek(string $state): ?array {
		$entry = $this->all()[$state] ?? null;
		if ($entry === null) {
			return null;
		}

		return [
			'requestId' => (string)($entry['requestId'] ?? ''),
			'signerId' => (string)($entry['signerId'] ?? ''),
			'userId' => (string)($entry['userId'] ?? ''),
			'nonce' => (string)($entry['nonce'] ?? ''),
		];

	}//end peek()

	/**
	 * The open authentication for a state, closed for good.
	 *
	 * @param string $state The state.
	 *
	 * @return array{requestId: string, signerId: string, userId: string, nonce: string}|null
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function take(string $state): ?array {
		$entry = $this->peek(state: $state);
		$pending = $this->all();
		unset($pending[$state]);
		$this->session->set(self::SESSION_KEY, $pending);

		return $entry;

	}//end take()

	/**
	 * Every open authentication that has not expired.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function all(): array {
		$pending = $this->session->get(self::SESSION_KEY);
		if (is_array($pending) === false) {
			return [];
		}

		$cutoff = time() - self::TTL;

		return array_filter(
			$pending,
			static fn (mixed $entry): bool => is_array($entry) === true && (int)($entry['createdAt'] ?? 0) >= $cutoff
		);

	}//end all()
}//end class
