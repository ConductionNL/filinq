<?php

/**
 * Signer Step-Up Service
 *
 * Starts a signer's identity step-up for one signing act, and finishes it
 * when the broker sends the signer back.
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

use OCA\Filinq\Service\SigningActorResolver;
use OCA\Filinq\Service\SigningService;
use RuntimeException;

/**
 * The step-up flow behind the sign dialog (REQ-DDSIR-003).
 *
 * Starting it goes through the same ownership check as signing: only the
 * signer a record belongs to can start a step-up for it. Finishing it takes
 * the act from the state the broker returns, completes the authentication in
 * the same session that started it, and keeps the evidence for that act
 * alone. The signature itself is a separate call to `sign()`, which runs the
 * gate again.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SignerStepUpService {

	/**
	 * Constructor.
	 *
	 * @param SignerAuthProviderFactory $providers The registered providers.
	 * @param OidcBrokerProvider $broker The broker, for the callback's bound act.
	 * @param SigningAssuranceGate $gate Knows the assurance each signer needs.
	 * @param IdentityEvidenceStore $store Keeps the evidence for the act.
	 * @param SigningActorResolver $actors The ownership check signing uses.
	 * @param SigningService $signing Loads the request.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SignerAuthProviderFactory $providers,
		private readonly OidcBrokerProvider $broker,
		private readonly SigningAssuranceGate $gate,
		private readonly IdentityEvidenceStore $store,
		private readonly SigningActorResolver $actors,
		private readonly SigningService $signing,
	) {

	}//end __construct()

	/**
	 * Start a step-up for one signer on one request.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $signerId The signer record id.
	 *
	 * @return array{provider: string, type: string, url: string, requiredAssurance: string}
	 *
	 * @throws RuntimeException When there is no session user, the signer is not theirs, or the provider cannot start.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function start(string $requestId, string $signerId): array {
		[$userId] = $this->actors->resolveActingIdentity();
		$signer = $this->actors->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: null,
			actorUserId: $userId,
			action: 'authenticate'
		);

		$request = (array)$this->signing->getRequest(requestId: $requestId);
		$required = $this->gate->requiredFor(request: $request, signer: $signer);
		$challenge = $this->providers->configured()->initiateAuthentication(
			new SignerAuthContext(requestId: $requestId, signerId: $signerId, requiredAssurance: $required, userId: $userId)
		);

		return $challenge->toArray() + ['requiredAssurance' => $required];

	}//end start()

	/**
	 * Authorise a broker callback against this session, then keep the evidence for its act.
	 *
	 * The state must be one this session opened, for this session's user;
	 * anything else is refused before the broker is called.
	 *
	 * @param string $code The authorization code.
	 * @param string $state The state the broker returned.
	 *
	 * @return array{requestId: string, signerId: string, assurance: string}
	 *
	 * @throws RuntimeException When the state is unknown or used, belongs to another user, or the broker fails.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function authorizeCallback(string $code, string $state): array {
		[$userId] = $this->actors->resolveActingIdentity();
		$act = $this->broker->boundAct(state: $state);
		if ($act === null) {
			throw new RuntimeException('Unknown or already used authentication state');
		}

		$evidence = $this->broker->completeAuthentication(
			[
				'code' => $code,
				'state' => $state,
				'requestId' => $act['requestId'],
				'signerId' => $act['signerId'],
				'userId' => $userId,
			]
		);
		$this->store->put(requestId: $act['requestId'], signerId: $act['signerId'], evidence: $evidence);

		return ['requestId' => $act['requestId'], 'signerId' => $act['signerId'], 'assurance' => $evidence->assurance];

	}//end authorizeCallback()
}//end class
