<?php

/**
 * Identity Evidence Store
 *
 * Holds the evidence a step-up produced until the signing act uses it.
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

use InvalidArgumentException;
use OCP\ISession;

/**
 * Evidence in the signer's own session, keyed by request and signer.
 *
 * The key binds the evidence to one signing act: a broker login done for
 * one request cannot be spent on another. The store holds the minimised
 * tuple only; the gate still checks its age at the act.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class IdentityEvidenceStore {

	/**
	 * The session key.
	 *
	 * @var string
	 */
	public const SESSION_KEY = 'filinq_signer_auth_evidence';

	/**
	 * Constructor.
	 *
	 * @param ISession $session The signer's session.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ISession $session,
	) {

	}//end __construct()

	/**
	 * Keep evidence for one signing act.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 * @param IdentityEvidence $evidence The evidence.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function put(string $requestId, string $signerId, IdentityEvidence $evidence): void {
		$all = $this->all();
		$all[$this->key(requestId: $requestId, signerId: $signerId)] = $evidence->toArray();
		$this->session->set(self::SESSION_KEY, array_slice($all, -10, null, true));

	}//end put()

	/**
	 * The evidence kept for one signing act.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 *
	 * @return IdentityEvidence|null The evidence, or null when there is none or it is unreadable.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) IdentityEvidence::fromArray() is the value
	 * object's named constructor; there is no state to inject.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function get(string $requestId, string $signerId): ?IdentityEvidence {
		$stored = $this->all()[$this->key(requestId: $requestId, signerId: $signerId)] ?? null;
		if (is_array($stored) === false) {
			return null;
		}

		try {
			return IdentityEvidence::fromArray(data: $stored);
		} catch (InvalidArgumentException) {
			return null;
		}

	}//end get()

	/**
	 * Forget the evidence of one signing act, once the act used it.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function clear(string $requestId, string $signerId): void {
		$all = $this->all();
		unset($all[$this->key(requestId: $requestId, signerId: $signerId)]);
		$this->session->set(self::SESSION_KEY, $all);

	}//end clear()

	/**
	 * Everything in the store.
	 *
	 * @return array<string, mixed>
	 */
	private function all(): array {
		$all = $this->session->get(self::SESSION_KEY);
		if (is_array($all) === false) {
			return [];
		}

		return $all;

	}//end all()

	/**
	 * The key of one signing act.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 *
	 * @return string
	 */
	private function key(string $requestId, string $signerId): string {
		return $requestId . '|' . $signerId;

	}//end key()
}//end class
