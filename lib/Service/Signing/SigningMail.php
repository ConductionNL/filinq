<?php

/**
 * Signing Mail
 *
 * Mails a signer when it is their turn and the initiator when a signer
 * declines, bound to the signing request so OpenRegister links each mail to it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use OCA\Filinq\Service\ObjectBoundMail;
use OCA\Filinq\Service\ObjectBoundMailTarget;
use OCA\Filinq\Service\ObjectBoundMessage;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The two mails a signing request sends itself.
 *
 * Both go out through OpenRegister bound to the signing request, so the
 * request's Emails tab lists them and filinq keeps no record of its own. Only
 * a request filinq signs natively sends them: an external provider mails its
 * own signers. The order of signing is untouched; this only says who is told.
 * A mail that cannot go never stops the signing act it follows.
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
 */
class SigningMail {

	/**
	 * The provider whose requests filinq mails itself.
	 */
	private const NATIVE = 'native';

	/**
	 * Constructor.
	 *
	 * @param ObjectBoundMail $mail   Sends a mail bound to the signing request.
	 * @param IL10N           $l10n   Words the mails.
	 * @param LoggerInterface $logger Logs a mail that did not go.
	 */
	public function __construct(
		private readonly ObjectBoundMail $mail,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Tell each signer whose turn it now is.
	 *
	 * @param array<string, mixed>                $request The signing request, with its id.
	 * @param array<string, array<string, mixed>> $signers Every signer record, by id.
	 * @param ObjectBoundMailTarget               $target  The signing request as a mail target.
	 * @param string|null                         $after   The signer who just acted, or null at creation.
	 *
	 * @return list<string> The outcome per signer told, keyed `<signerId>:<outcome>`.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) SigningTurn is the stateless rule for whose turn it is.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	public function turnStarted(array $request, array $signers, ObjectBoundMailTarget $target, ?string $after = null): array {
		if ($this->mailsItself(request: $request) === false) {
			return [];
		}

		$document = $this->documentOf(request: $request);
		$message  = new ObjectBoundMessage(
			subject: $this->l10n->t('A document is waiting for your signature: %s', [$document]),
			body: $this->l10n->t('It is your turn to act on %1$s. Open the signing request in Filinq to sign or decline it.', [$document]),
			object: $target
		);
		$outcomes = [];
		foreach (SigningTurn::due(request: $request, signers: $signers, after: $after) as $signerId) {
			$outcome = $this->deliver(signer: $signers[$signerId], message: $message);
			$this->logUndelivered(outcome: $outcome, requestId: $target->uuid);
			$outcomes[] = $signerId . ':' . $outcome;
		}

		return $outcomes;
	}//end turnStarted()

	/**
	 * Tell the initiator that a signer declined, and why.
	 *
	 * @param array<string, mixed>  $request    The signing request.
	 * @param string                $declinedBy The name of the signer who declined.
	 * @param string                $reason     The reason they gave.
	 * @param ObjectBoundMailTarget $target     The signing request as a mail target.
	 *
	 * @return string The outcome, or '' when nobody is told.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	public function declined(array $request, string $declinedBy, string $reason, ObjectBoundMailTarget $target): string {
		$initiator = trim((string)($request['initiatorUserId'] ?? ''));
		if ($initiator === '' || $this->mailsItself(request: $request) === false) {
			return '';
		}

		$document = $this->documentOf(request: $request);
		$reason   = trim($reason);
		if ($reason === '') {
			$reason = $this->l10n->t('No reason was given.');
		}

		try {
			$outcome = $this->mail->toUser(
				uid: $initiator,
				message: new ObjectBoundMessage(
					subject: $this->l10n->t('%1$s declined to sign %2$s', [$declinedBy, $document]),
					body: $this->l10n->t('%1$s declined to sign %2$s. The reason given: %3$s', [$declinedBy, $document, $reason]),
					object: $target
				)
			);
		} catch (Throwable $e) {
			$outcome = 'failed';
		}

		$this->logUndelivered(outcome: $outcome, requestId: $target->uuid);

		return $outcome;
	}//end declined()

	/**
	 * Mail one signer: at the address on their record, else at their account's.
	 *
	 * @param array<string, mixed> $signer  The signer record.
	 * @param ObjectBoundMessage   $message The mail.
	 *
	 * @return string The outcome; `no-address` when the record names neither, `failed` when sending threw.
	 */
	private function deliver(array $signer, ObjectBoundMessage $message): string {
		$email  = trim((string)($signer['email'] ?? ''));
		$userId = trim((string)($signer['userId'] ?? ''));

		try {
			if ($email !== '') {
				return $this->mail->toAddress(address: $email, displayName: trim((string)($signer['displayName'] ?? '')), message: $message);
			}

			if ($userId !== '') {
				return $this->mail->toUser(uid: $userId, message: $message);
			}
		} catch (Throwable $e) {
			return 'failed';
		}

		return 'no-address';
	}//end deliver()

	/**
	 * Whether filinq mails this request's signers itself: a native request.
	 *
	 * @param array<string, mixed> $request The signing request.
	 *
	 * @return bool
	 */
	private function mailsItself(array $request): bool {
		$provider = strtolower(trim((string)($request['provider'] ?? self::NATIVE)));

		return $provider === '' || $provider === self::NATIVE;
	}//end mailsItself()

	/**
	 * The document's name, or a neutral word when the request names none.
	 *
	 * @param array<string, mixed> $request The signing request.
	 *
	 * @return string The name.
	 */
	private function documentOf(array $request): string {
		$name = trim((string)($request['documentName'] ?? ''));
		if ($name === '') {
			return $this->l10n->t('a document');
		}

		return $name;
	}//end documentOf()

	/**
	 * Log a mail that did not go, without its address or body.
	 *
	 * @param string $outcome   The outcome.
	 * @param string $requestId The signing request.
	 *
	 * @return void
	 */
	private function logUndelivered(string $outcome, string $requestId): void {
		if ($outcome === ObjectBoundMail::OUTCOME_DISPATCHED) {
			return;
		}

		$this->logger->info(
			message: 'A signing mail was not sent: ' . $outcome,
			context: ['signingRequestId' => $requestId]
		);
	}//end logUndelivered()
}//end class
