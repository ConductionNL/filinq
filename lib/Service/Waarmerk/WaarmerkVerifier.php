<?php

/**
 * Waarmerk Verifier
 *
 * Public, fail-closed verification of a waarmerk by code and hash.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Waarmerk
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

namespace OCA\Filinq\Service\Waarmerk;

use DateTimeInterface;
use OCA\Filinq\Kernel\Contracts\WaarmerkVerifierInterface;
use OCA\Filinq\Service\WaarmerkService;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Answers exactly one of valid, hash-mismatch, revoked, unknown.
 *
 * Fail-closed: only a stored, active seal whose recorded hash equals the
 * verifier's hash AND whose CMS verifies against the recorded certificate is
 * `valid`. Revocation dominates the hash. A broken CMS or a register error
 * answers `unknown`. Every answer has the same keys, and a non-matching
 * code carries no data, so the response is no existence oracle. No document
 * content is read or returned.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
 */
class WaarmerkVerifier implements WaarmerkVerifierInterface {

	/**
	 * Constructor.
	 *
	 * @param WaarmerkStore $store The waarmerk records.
	 * @param WaarmerkCms $cms Verifies the signature.
	 * @param ITimeFactory $time The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WaarmerkStore $store,
		private readonly WaarmerkCms $cms,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Verify a code against the verifier's own sha256 of their file.
	 *
	 * @param string $code The verification code.
	 * @param string $sha256 The verifier's hex sha256.
	 *
	 * @return array<string, mixed> status plus the fixed set of keys.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function verify(string $code, string $sha256): array {
		$code = strtolower(trim($code));
		$sha256 = strtolower(trim($sha256));
		$seal = $this->lookup(code: $code);
		if ($seal === null) {
			return $this->answer(status: self::UNKNOWN);
		}

		if (($seal['status'] ?? '') !== 'active') {
			return $this->answer(status: self::REVOKED, revocation: $seal);
		}

		$recorded = (string) ($seal['documentHash'] ?? '');
		if (preg_match('/^[0-9a-f]{64}$/', $sha256) !== 1 || hash_equals($recorded, $sha256) === false) {
			return $this->answer(status: self::HASH_MISMATCH);
		}

		$signer = $this->cms->verify(
			hash: $recorded,
			cmsBase64: (string) ($seal['cmsSignature'] ?? ''),
			fingerprint: (string) ($seal['certificateFingerprint'] ?? '')
		);
		if ($signer === null) {
			return $this->answer(status: self::UNKNOWN);
		}

		return $this->answer(status: self::VALID, seal: $seal, expired: $this->expired(signerPem: $signer));

	}//end verify()

	/**
	 * What the human page shows for a code: status, organisation, time, document name.
	 *
	 * @param string $code The verification code.
	 *
	 * @return array<string, mixed> status active|revoked|unknown plus the fixed set of keys.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-is-publicly-verifiable-fail-closed-req-ddwmk-004
	 */
	public function page(string $code): array {
		$seal = $this->lookup(code: strtolower(trim($code)));
		if ($seal === null) {
			return $this->answer(status: self::UNKNOWN);
		}

		if (($seal['status'] ?? '') !== 'active') {
			return array_merge($this->answer(status: self::REVOKED, revocation: $seal), $this->facts(seal: $seal));
		}

		return array_merge($this->answer(status: 'active'), $this->facts(seal: $seal));

	}//end page()

	/**
	 * The seal of a well-formed code, or null; register errors count as unknown.
	 *
	 * @param string $code The normalised code.
	 *
	 * @return array<string, mixed>|null
	 */
	private function lookup(string $code): ?array {
		if (WaarmerkService::isWellFormedCode(code: $code) === false) {
			return null;
		}

		try {
			return $this->store->findByCode(code: $code);
		} catch (Throwable) {
			return null;
		}

	}//end lookup()

	/**
	 * Whether the signer certificate has expired by now (a long-term-validation advisory).
	 *
	 * @param string $signerPem The signer certificate.
	 *
	 * @return bool
	 */
	private function expired(string $signerPem): bool {
		$validTo = (int) (@openssl_x509_parse($signerPem)['validTo_time_t'] ?? 0);
		return $this->time->getDateTime()->getTimestamp() > $validTo;

	}//end expired()

	/**
	 * The public facts of a seal.
	 *
	 * @param array<string, mixed> $seal The seal.
	 *
	 * @return array<string, mixed>
	 */
	private function facts(array $seal): array {
		$organisation = '';
		if (preg_match('/(?:^|, )O=([^,]+)/', (string) ($seal['certificateSubject'] ?? ''), $match) === 1) {
			$organisation = $match[1];
		}

		return [
			'organisation' => $organisation,
			'sealedAt' => (string) ($seal['sealedAt'] ?? ''),
			'documentName' => (string) ($seal['documentName'] ?? ''),
			'sealType' => (string) ($seal['sealType'] ?? ''),
		];

	}//end facts()

	/**
	 * One response shape for every outcome.
	 *
	 * @param string $status The outcome.
	 * @param array<string, mixed>|null $seal The seal, only for a valid answer.
	 * @param bool $expired The certificate-expired advisory, only for a valid answer.
	 * @param array<string, mixed>|null $revocation The seal, only for a revoked answer.
	 *
	 * @return array<string, mixed>
	 */
	private function answer(string $status, ?array $seal = null, bool $expired = false, ?array $revocation = null): array {
		$answer = [
			'status' => $status,
			'organisation' => null,
			'sealedAt' => null,
			'documentName' => null,
			'sealType' => null,
			'certificateExpired' => null,
			'revokedAt' => null,
			'revocationReason' => null,
			'checkedAt' => $this->time->getDateTime()->format(DateTimeInterface::ATOM),
		];
		if ($seal !== null) {
			$answer = array_merge($answer, $this->facts(seal: $seal), ['certificateExpired' => $expired]);
		}

		if ($revocation !== null) {
			$answer['revokedAt'] = (string) ($revocation['revokedAt'] ?? '');
			$answer['revocationReason'] = (string) ($revocation['revocationReason'] ?? '');
		}

		return $answer;

	}//end answer()
}//end class
