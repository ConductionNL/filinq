<?php

/**
 * DocuDesk SigningCancellationService
 *
 * Withdraws a signing request: authorise, call the provider, record the outcome.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Signing;

use OCA\DocuDesk\Exception\SigningCancellationNotSupportedException;
use OCA\DocuDesk\Service\SigningService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * The one place a signing request is withdrawn.
 *
 * ## Only the creator, decided 2026-08-16
 *
 * Not an app administrator. Not a holder of write access to the document.
 *
 * Write permission on a file is not authority to withdraw a legal process from
 * every signatory. The two coincide often, which is exactly what makes the
 * conflation easy and wrong. An administrator administers an application; they are
 * not a party to an agreement between a requester and its signatories.
 *
 * **The accepted consequence:** a creator who has left the organisation
 * permanently blocks cancellation of their requests. There is no in-app override,
 * deliberately. The refusal names the creator so a blocked user knows who to ask
 * rather than concluding the feature is broken. An "absent creator" escape hatch is
 * a separate change with its own authorisation argument — adding one here on
 * operational grounds is how the administrator path returns through the back door.
 *
 * ## Order of checks is load-bearing
 *
 * Authorisation runs before the provider is contacted (so no partial cancellation)
 * AND before the request id is resolved — otherwise an unauthorised caller could
 * distinguish "no such request" from "not allowed" and enumerate valid ids from the
 * error text.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */
class SigningCancellationService {

	/**
	 * Constructor.
	 *
	 * @param SigningProviderFactory $providers Resolves the configured provider.
	 * @param SigningService         $requests  Signing request lookup.
	 * @param LoggerInterface        $logger    The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningProviderFactory $providers,
		private readonly SigningService $requests,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Withdraw a signing request.
	 *
	 * @param string $uid       The acting user id.
	 * @param string $requestId The signing request id.
	 *
	 * @return array{requestId: string, status: string, alreadyCancelled: bool}
	 *
	 * @throws RuntimeException When the actor is not the creator, or the withdrawal fails.
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function cancel(string $uid, string $requestId): array {
		// Scoped lookup: getRequest() already collapses access-denied to null and
		// throws an id-free message for a genuine miss, so neither shape leaks
		// which request ids exist.
		$request = null;
		try {
			$request = $this->requests->getRequest(requestId: $requestId, callerUserId: $uid);
		} catch (Throwable $e) {
			$request = null;
		}

		// ONE refusal for "not yours" and "does not exist". Distinguishing them
		// would let an unauthorised caller enumerate valid request ids from the
		// error text, so the two cases must be indistinguishable from outside.
		// A SIGNER reaches this point too — getRequest() admits signers — and is
		// refused here, because signing a document is not authority to withdraw it.
		if ($request === null || $this->isCreator(uid: $uid, request: $request) === false) {
			$this->recordAttempt(
				uid: $uid,
				requestId: $requestId,
				outcome: 'refused-not-creator',
				detail: 'actor is not the creator, or the request does not exist'
			);

			throw new RuntimeException($this->refusalMessage(request: $request));
		}

		$externalId = (string)($request['externalId'] ?? '');
		if ($externalId === '') {
			throw new RuntimeException(
				'This signing request has no provider reference, so it cannot be withdrawn at the provider.'
			);
		}

		try {
			$this->providers->getActiveProvider()->cancelSigning($externalId);
		} catch (SigningCancellationNotSupportedException $e) {
			// The provider CANNOT cancel. The request is still live, and the caller
			// is told so rather than being shown a success they cannot rely on.
			$this->recordAttempt(
				uid: $uid,
				requestId: $requestId,
				outcome: 'failed-unsupported',
				detail: $e->getMessage()
			);
			throw $e;
		} catch (Throwable $e) {
			// A failed cancellation is recorded AS FAILED. "Attempted, provider
			// refused, request still live" is the record that stops someone later
			// concluding the document was withdrawn when it was not.
			$this->recordAttempt(
				uid: $uid,
				requestId: $requestId,
				outcome: 'failed',
				detail: $e->getMessage()
			);
			throw new RuntimeException(
				'The signing request could not be withdrawn: ' . $e->getMessage()
				. ' It is still live and its signatories can still sign.',
				0,
				$e
			);
		}//end try

		$already = (($request['status'] ?? '') === NativeSigningProvider::STATUS_CANCELLED);

		$this->recordAttempt(uid: $uid, requestId: $requestId, outcome: 'cancelled', detail: '');

		return [
			'requestId'        => $requestId,
			'status'           => NativeSigningProvider::STATUS_CANCELLED,
			'alreadyCancelled' => $already,
		];
	}//end cancel()

	/**
	 * Whether the actor created this request.
	 *
	 * The ONLY authorisation predicate. Kept as one method so a second caller
	 * cannot introduce a second, laxer rule — and so that widening it is a visible
	 * edit to a named thing rather than a condition drifting at a call site.
	 *
	 * @param string $uid     The acting user id.
	 * @param array  $request The signing request.
	 *
	 * @return bool True when the actor is the creator.
	 */
	private function isCreator(string $uid, array $request): bool {
		$creator = (string)($request['initiatorUserId'] ?? '');

		return ($creator !== '' && $creator === $uid);
	}//end isCreator()

	/**
	 * Build the refusal.
	 *
	 * Names the creator when there is one. A bare "not permitted" leaves the user
	 * concluding the feature is broken, when what they need is to know who to ask —
	 * which matters more here than usual, because an absent creator blocks
	 * cancellation permanently and by design.
	 *
	 * @param array|null $request The request, or null when it does not exist.
	 *
	 * @return string The refusal message.
	 */
	private function refusalMessage(?array $request): string {
		$creator = '';
		if ($request !== null) {
			$creator = (string)($request['initiatorUserId'] ?? '');
		}

		if ($creator !== '') {
			return sprintf(
				'Only the person who created this signing request can withdraw it: %s. '
				. 'Administrators cannot withdraw it on their behalf.',
				$creator
			);
		}

		return 'Only the person who created this signing request can withdraw it.';
	}//end refusalMessage()

	/**
	 * Record an attempt and its outcome.
	 *
	 * Every attempt, not only the successes. A withdrawn signing process is exactly
	 * the event someone will later need to reconstruct.
	 *
	 * @param string $uid       The acting user id.
	 * @param string $requestId The signing request id.
	 * @param string $outcome   What happened.
	 * @param string $detail    Why, when it failed.
	 *
	 * @return void
	 */
	private function recordAttempt(string $uid, string $requestId, string $outcome, string $detail): void {
		$this->logger->warning(
			sprintf('[DocuDesk] signing cancellation %s', $outcome),
			[
				'app'       => 'docudesk',
				'actor'     => $uid,
				'requestId' => $requestId,
				'outcome'   => $outcome,
				'detail'    => $detail,
			]
		);
	}//end recordAttempt()
}//end class
