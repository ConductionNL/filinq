<?php

/**
 * Waarmerk Certificate
 *
 * The public facts of the organisation certificate a waarmerk is made with.
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

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\WaarmerkRefusedException;

/**
 * A parsed X.509 organisation certificate: public material only.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
 */
class WaarmerkCertificate {

	/**
	 * Constructor.
	 *
	 * @param string $pem The certificate, PEM.
	 * @param string $subject The subject, as `O=..., C=...`.
	 * @param string $organisation The organisation name (O, else CN).
	 * @param string $fingerprint The sha256 fingerprint, colon-separated upper hex.
	 * @param int $validFrom Start of validity, unix time.
	 * @param int $validTo End of validity, unix time.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $pem,
		public readonly string $subject,
		public readonly string $organisation,
		public readonly string $fingerprint,
		public readonly int $validFrom,
		public readonly int $validTo,
	) {

	}//end __construct()

	/**
	 * Parse a PEM certificate.
	 *
	 * @param string $pem The certificate, PEM.
	 *
	 * @return self
	 *
	 * @throws WaarmerkRefusedException When the text is not an X.509 certificate or holds a private key.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public static function fromPem(string $pem): self {
		// The certificate field takes public material only: a pasted key is refused, not stored.
		if (str_contains($pem, 'PRIVATE KEY') === true) {
			throw new WaarmerkRefusedException(message: 'The certificate field must not contain a private key', status: 400);
		}

		$parsed = @openssl_x509_parse($pem);
		$fingerprint = @openssl_x509_fingerprint($pem, 'sha256');
		if (is_array($parsed) === false || is_string($fingerprint) === false) {
			throw new WaarmerkRefusedException(message: 'The certificate is not a valid X.509 certificate (PEM)', status: 400);
		}

		$subject = (array) ($parsed['subject'] ?? []);
		$parts = [];
		foreach ($subject as $key => $value) {
			$parts[] = $key . '=' . implode(', ', (array) $value);
		}

		$organisation = (string) ($subject['O'] ?? $subject['CN'] ?? '');

		return new self(
			pem: trim($pem) . "\n",
			subject: implode(', ', $parts),
			organisation: $organisation,
			fingerprint: implode(':', str_split(strtoupper($fingerprint), 2)),
			validFrom: (int) ($parsed['validFrom_time_t'] ?? 0),
			validTo: (int) ($parsed['validTo_time_t'] ?? 0)
		);

	}//end fromPem()

	/**
	 * Whether the certificate has expired (or is not yet valid) at a moment.
	 *
	 * @param DateTimeInterface $at The moment.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function isExpiredAt(DateTimeInterface $at): bool {
		$time = $at->getTimestamp();
		return $time > $this->validTo || $time < $this->validFrom;

	}//end isExpiredAt()

	/**
	 * The public facts, for the admin panel.
	 *
	 * @param DateTimeInterface $now The current moment.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	public function summary(DateTimeInterface $now): array {
		return [
			'subject' => $this->subject,
			'organisation' => $this->organisation,
			'fingerprint' => $this->fingerprint,
			'validFrom' => (new DateTimeImmutable('@' . $this->validFrom))->format(DateTimeInterface::ATOM),
			'validTo' => (new DateTimeImmutable('@' . $this->validTo))->format(DateTimeInterface::ATOM),
			'expired' => $this->isExpiredAt(at: $now),
		];

	}//end summary()
}//end class
