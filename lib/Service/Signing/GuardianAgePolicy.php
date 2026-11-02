<?php

/**
 * Guardian Age Policy
 *
 * Answers the two age questions the guardian consent rules ask: which age of
 * consent applies to this request, and was this signer under it at a given
 * moment.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;
use Exception;
use OCA\Filinq\Service\SettingsService;

/**
 * The age of consent that applies, and whether a signer was under it.
 *
 * The default is 16, the age of consent in Dutch law (UAVG article 5 on AVG
 * article 8). An administrator can set another age; a request can raise it
 * for its own signers (18 on a praktijkovereenkomst, where civil-law minority
 * runs to 18) and never lower it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class GuardianAgePolicy {

	/**
	 * The age of consent when nobody configured one.
	 *
	 * @var int
	 */
	public const DEFAULT_AGE = 16;

	/**
	 * The feature-toggle key holding the configured age.
	 *
	 * @var string
	 */
	public const SETTING = 'signing_guardian_consent_age';

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settingsService Reads the configured age.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settingsService,
	) {

	}//end __construct()

	/**
	 * The age of consent that applies to a request.
	 *
	 * @param array<string, mixed> $data The request (or its creation payload).
	 *
	 * @return int The configured age, raised to the request's own age when that is higher.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function appliedAge(array $data): int {
		$requested = $this->positiveInt(value: ($data['guardianConsentAge'] ?? null));

		return max($this->configuredAge(), ($requested ?? 0));

	}//end appliedAge()

	/**
	 * Was a person born on this date under the age at this moment.
	 *
	 * A birth date nobody can read, or one in the future, counts as under the
	 * age: the rule fails towards asking for a guardian, never away from it.
	 *
	 * @param string $birthDate The birth date (YYYY-MM-DD).
	 * @param int $age The age of consent.
	 * @param DateTimeImmutable $moment The moment of the act.
	 *
	 * @return bool True when under the age.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function isUnderAge(string $birthDate, int $age, DateTimeImmutable $moment): bool {
		$born = $this->parseBirthDate(value: $birthDate);
		if ($born === null || $born > $moment) {
			return true;
		}

		return $born->diff($moment)->y < $age;

	}//end isUnderAge()

	/**
	 * Parse a birth date, strictly.
	 *
	 * @param string $value The candidate date.
	 *
	 * @return DateTimeImmutable|null Midnight UTC on that date, or null when it is not a real YYYY-MM-DD date.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function parseBirthDate(string $value): ?DateTimeImmutable {
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
			return null;
		}

		if (checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]) === false) {
			return null;
		}

		return new DateTimeImmutable($value . 'T00:00:00+00:00');

	}//end parseBirthDate()

	/**
	 * The moment a recorded act took place.
	 *
	 * @param string $value The recorded ISO 8601 moment, or ''.
	 *
	 * @return DateTimeImmutable That moment, or now when none was recorded or it cannot be read.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function moment(string $value): DateTimeImmutable {
		if ($value === '') {
			return new DateTimeImmutable();
		}

		try {
			return new DateTimeImmutable($value);
		} catch (Exception) {
			return new DateTimeImmutable();
		}

	}//end moment()

	/**
	 * The age an administrator configured, or the default.
	 *
	 * @return int The configured age, or 16 when unset, non-numeric or not positive.
	 */
	private function configuredAge(): int {
		$toggles = $this->settingsService->getFeatureToggles();

		return ($this->positiveInt(value: ($toggles[self::SETTING] ?? null)) ?? self::DEFAULT_AGE);

	}//end configuredAge()

	/**
	 * Read a positive whole number from an integer or a digit string.
	 *
	 * @param mixed $value The candidate.
	 *
	 * @return int|null The number, or null when it is not a positive whole number.
	 */
	private function positiveInt(mixed $value): ?int {
		if (is_string($value) === true && preg_match('/^\d+$/', trim($value)) === 1) {
			$value = (int)trim($value);
		}

		if (is_int($value) === false || $value < 1) {
			return null;
		}

		return $value;

	}//end positiveInt()
}//end class
