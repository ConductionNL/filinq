<?php

/**
 * Identity Evidence
 *
 * Who a signer is, at what assurance, and when that was established, as a
 * signer-authentication provider asserted it.
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
use Exception;
use InvalidArgumentException;

/**
 * The identity evidence tuple of REQ-DDSIR-004.
 *
 * It holds a pseudonym and a hash, never a national identifier and never the
 * raw token the evidence was derived from: that minimisation is a property of
 * the value itself, so no code path that stores or logs it can leak more.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
final class IdentityEvidence {

	/**
	 * The assurance the evidence carries, normalised.
	 *
	 * @var string
	 */
	public readonly string $assurance;

	/**
	 * Constructor.
	 *
	 * @param string $provider The provider identifier that asserted the identity.
	 * @param string $means The identity means (`nc-session`, `digid`, `eherkenning`, `idin`, ...).
	 * @param string $assurance The assurance level; anything undeclared becomes `low`.
	 * @param string $subjectPseudonym A pairwise or salted-hash pseudonym, never a national identifier.
	 * @param DateTimeImmutable $authenticatedAt When the identity was established.
	 * @param string $evidenceHash The sha256 of the validated raw token or assertion.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $provider,
		public readonly string $means,
		string $assurance,
		public readonly string $subjectPseudonym,
		public readonly DateTimeImmutable $authenticatedAt,
		public readonly string $evidenceHash,
	) {
		$normalised = 'low';
		if (in_array($assurance, AssuranceLevel::LEVELS, true) === true) {
			$normalised = $assurance;
		}

		$this->assurance = $normalised;

	}//end __construct()

	/**
	 * The evidence as the record, the audit entry and the artifact store it.
	 *
	 * @return array{provider: string, means: string, assurance: string, subjectPseudonym: string, authenticatedAt: string, evidenceHash: string}
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function toArray(): array {
		return [
			'provider' => $this->provider,
			'means' => $this->means,
			'assurance' => $this->assurance,
			'subjectPseudonym' => $this->subjectPseudonym,
			'authenticatedAt' => $this->authenticatedAt->format(DateTimeInterface::ATOM),
			'evidenceHash' => $this->evidenceHash,
		];

	}//end toArray()

	/**
	 * Rebuild evidence from its stored form.
	 *
	 * @param array<string, mixed> $data The stored tuple.
	 *
	 * @return self The evidence.
	 *
	 * @throws InvalidArgumentException When the provider is missing or the timestamp does not parse.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public static function fromArray(array $data): self {
		$provider = (string)($data['provider'] ?? '');
		if ($provider === '') {
			throw new InvalidArgumentException('Identity evidence names no provider');
		}

		try {
			$authenticatedAt = new DateTimeImmutable((string)($data['authenticatedAt'] ?? ''));
		} catch (Exception $e) {
			throw new InvalidArgumentException('Identity evidence carries no readable authentication moment', 0, $e);
		}

		return new self(
			provider: $provider,
			means: (string)($data['means'] ?? ''),
			assurance: (string)($data['assurance'] ?? ''),
			subjectPseudonym: (string)($data['subjectPseudonym'] ?? ''),
			authenticatedAt: $authenticatedAt,
			evidenceHash: (string)($data['evidenceHash'] ?? '')
		);

	}//end fromArray()

	/**
	 * Is the evidence no older than a maximum age at a moment.
	 *
	 * Evidence dated in the future (clock skew beyond a minute) is not fresh
	 * either: it cannot have been established yet.
	 *
	 * @param DateTimeImmutable $now The moment of the act.
	 * @param int $maxAgeSeconds The maximum age in seconds.
	 *
	 * @return bool True when the evidence may still be used.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function isFreshAt(DateTimeImmutable $now, int $maxAgeSeconds): bool {
		$age = $now->getTimestamp() - $this->authenticatedAt->getTimestamp();

		return $age >= -60 && $age <= $maxAgeSeconds;

	}//end isFreshAt()
}//end class
