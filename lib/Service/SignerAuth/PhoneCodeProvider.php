<?php

/**
 * Verifies a signer with a one-time code sent to their phone
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
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use RuntimeException;

/**
 * A signer-authentication provider that sends a six-digit code by text
 * message to the phone number recorded on the signer record.
 *
 * The code is kept only as a hash, and the number only as a pseudonym, for {@see self::TTL_SECONDS}, bound to one
 * request and one signer. It works once: the right code consumes it, and
 * {@see self::MAX_ATTEMPTS} wrong codes void it. The evidence says a phone
 * code was the means and never carries the code or the number.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
 */
class PhoneCodeProvider implements SignerAuthenticationProviderInterface {

	/**
	 * The provider identifier.
	 */
	public const IDENTIFIER = 'phone-code';

	/**
	 * The means it establishes.
	 */
	public const MEANS = 'phone-code';

	/**
	 * How long a code stays valid.
	 */
	public const TTL_SECONDS = 300;

	/**
	 * Wrong codes after which a code is void.
	 */
	public const MAX_ATTEMPTS = 5;

	/**
	 * The signer record property holding the phone number.
	 */
	public const PHONE_FIELD = 'phoneNumber';

	/**
	 * Constructor.
	 *
	 * @param SettingsService               $settings      Reads the signer record
	 * @param ICacheFactory                 $caches        Holds the pending code hashes
	 * @param ITimeFactory                  $clock         Now
	 * @param SubjectPseudonymiser          $pseudonymiser Salts the number before it is stored
	 * @param PhoneCodeSenderInterface|null $sender        The text message gateway; null when none is bound
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settings,
		private readonly ICacheFactory $caches,
		private readonly ITimeFactory $clock,
		private readonly SubjectPseudonymiser $pseudonymiser,
		private readonly ?PhoneCodeSenderInterface $sender = null,
	) {

	}//end __construct()

	/**
	 * The identifier.
	 *
	 * @return string `phone-code`.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;

	}//end getIdentifier()

	/**
	 * The means.
	 *
	 * @return list<string> `['phone-code']`.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	public function getSupportedMeans(): array {
		return [self::MEANS];

	}//end getSupportedMeans()

	/**
	 * The assurance: a code to a phone shows possession of the phone only.
	 *
	 * @return list<string> `['low']`.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	public function getSupportedAssurance(): array {
		return ['low'];

	}//end getSupportedAssurance()

	/**
	 * Send a fresh code to the signer's phone, replacing any pending one.
	 *
	 * @param SignerAuthContext $context The request and signer.
	 *
	 * @return AuthChallenge A `code` challenge: the signer enters what they received.
	 *
	 * @throws RuntimeException When no gateway is bound or the signer has no phone number.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	public function initiateAuthentication(SignerAuthContext $context): AuthChallenge {
		if ($this->sender === null) {
			throw new RuntimeException('No text message gateway is configured for phone codes');
		}

		$phone = $this->phoneOf(requestId: $context->requestId, signerId: $context->signerId);
		$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

		$pending = [
			'hash' => $this->hash(requestId: $context->requestId, signerId: $context->signerId, code: $code),
			'expiresAt' => ($this->clock->getTime() + self::TTL_SECONDS),
			'attempts' => 0,
			'subject' => $this->pseudonymiser->pseudonym(issuer: 'phone', subject: $phone),
		];
		$this->cache()->set($this->key(requestId: $context->requestId, signerId: $context->signerId), $pending, self::TTL_SECONDS);

		$minutes = intdiv(self::TTL_SECONDS, 60);
		$this->sender->send(phoneNumber: $phone, message: 'Your signing code is ' . $code . '. It is valid for ' . $minutes . ' minutes.');

		return new AuthChallenge(provider: self::IDENTIFIER, type: AuthChallenge::TYPE_CODE);

	}//end initiateAuthentication()

	/**
	 * Check the code the signer entered and derive the evidence.
	 *
	 * @param array<string, mixed> $callbackData `requestId`, `signerId` and `code`.
	 *
	 * @return IdentityEvidence Evidence at `low`, naming the phone code as the means.
	 *
	 * @throws RuntimeException When the code is missing, wrong, expired, used or for another act.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	public function completeAuthentication(array $callbackData): IdentityEvidence {
		$requestId = (string) ($callbackData['requestId'] ?? '');
		$signerId = (string) ($callbackData['signerId'] ?? '');
		$code = trim((string) ($callbackData['code'] ?? ''));
		if ($requestId === '' || $signerId === '' || preg_match('/^\d{6}$/', $code) !== 1) {
			throw new RuntimeException('A phone code authentication needs the request, the signer and a six-digit code');
		}

		$key = $this->key(requestId: $requestId, signerId: $signerId);
		$now = $this->clock->getTime();
		$pending = $this->pendingCode(key: $key, now: $now);

		if (hash_equals((string) ($pending['hash'] ?? ''), $this->hash(requestId: $requestId, signerId: $signerId, code: $code)) === false) {
			$this->countWrongAttempt(key: $key, pending: $pending, now: $now);
			throw new RuntimeException('The phone code is wrong');
		}

		// A code works once.
		$this->cache()->remove($key);
		$at = (new DateTimeImmutable())->setTimestamp($now);

		return new IdentityEvidence(
			provider: self::IDENTIFIER,
			means: self::MEANS,
			assurance: 'low',
			subjectPseudonym: (string) ($pending['subject'] ?? ''),
			authenticatedAt: $at,
			evidenceHash: hash('sha256', implode("\n", [self::IDENTIFIER, $requestId, $signerId, $at->format(DateTimeInterface::ATOM)]))
		);

	}//end completeAuthentication()

	/**
	 * The code pending for this signer and act, refused when there is none or it has expired.
	 *
	 * @param string $key The cache key of the signer and act
	 * @param int    $now Now, as a timestamp
	 *
	 * @return array<string, mixed> The pending code.
	 *
	 * @throws RuntimeException When no code is pending, or it has expired (and is then dropped).
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	private function pendingCode(string $key, int $now): array {
		$pending = $this->cache()->get($key);
		if (is_array($pending) === false) {
			throw new RuntimeException('No phone code is pending for this signer, or it was used');
		}

		if ((int) ($pending['expiresAt'] ?? 0) < $now) {
			$this->cache()->remove($key);
			throw new RuntimeException('The phone code has expired');
		}

		return $pending;

	}//end pendingCode()

	/**
	 * Count a wrong code: the code is dropped after the last allowed attempt, else kept until it expires.
	 *
	 * @param string               $key     The cache key of the signer and act
	 * @param array<string, mixed> $pending The pending code
	 * @param int                  $now     Now, as a timestamp
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	private function countWrongAttempt(string $key, array $pending, int $now): void {
		$pending['attempts'] = ((int) ($pending['attempts'] ?? 0) + 1);
		if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
			$this->cache()->remove($key);
			return;
		}

		$this->cache()->set($key, $pending, max(1, ((int) $pending['expiresAt'] - $now)));

	}//end countWrongAttempt()

	/**
	 * The phone number on the signer record of this request.
	 *
	 * @param string $requestId The request
	 * @param string $signerId  The signer record
	 *
	 * @return string The number.
	 *
	 * @throws RuntimeException When the record is not this request's, or carries no number.
	 */
	private function phoneOf(string $requestId, string $signerId): string {
		$record = $this->signerRecord(signerId: $signerId);
		if ((string) ($record['signingRequestId'] ?? '') !== $requestId) {
			throw new RuntimeException('Signer record does not belong to this signing request');
		}

		$phone = trim((string) ($record[self::PHONE_FIELD] ?? ''));
		if (preg_match('/^\+?[0-9 ()-]{6,20}$/', $phone) !== 1) {
			throw new RuntimeException('No phone number is recorded for this signer');
		}

		return $phone;

	}//end phoneOf()

	/**
	 * The signer record, read without RBAC from the configured signer register.
	 *
	 * @param string $signerId The signer record
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @throws RuntimeException When signer records are not configured.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	private function signerRecord(string $signerId): array {
		$binding = $this->settings->resolveSignerRecordBinding();
		$objects = $this->settings->getObjectService();
		if ($binding === null || $objects === null) {
			throw new RuntimeException('Signer records are not configured');
		}

		$record = $objects->find(
			id: $signerId,
			options: new ObjectFindOptions(register: $binding['register'], schema: $binding['schema']),
			_rbac: false
		);
		if (is_object($record) === true && method_exists($record, 'jsonSerialize') === true) {
			$record = $record->jsonSerialize();
		}

		return (array) $record;

	}//end signerRecord()

	/**
	 * The cache holding pending codes.
	 *
	 * @return ICache
	 */
	private function cache(): ICache {
		return $this->caches->createDistributed('filinq-phone-code');

	}//end cache()

	/**
	 * The cache key of one act.
	 *
	 * @param string $requestId The request
	 * @param string $signerId  The signer record
	 *
	 * @return string
	 */
	private function key(string $requestId, string $signerId): string {
		return hash('sha256', $requestId . "\n" . $signerId);

	}//end key()

	/**
	 * The stored hash of a code, bound to its act.
	 *
	 * @param string $requestId The request
	 * @param string $signerId  The signer record
	 * @param string $code      The code
	 *
	 * @return string
	 */
	private function hash(string $requestId, string $signerId, string $code): string {
		return hash('sha256', implode("\n", [$requestId, $signerId, $code]));

	}//end hash()
}//end class
