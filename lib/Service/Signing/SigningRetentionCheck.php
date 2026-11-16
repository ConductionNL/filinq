<?php

/**
 * Signing Retention Check
 *
 * Decides whether a signing schema's retention keeps its audit trail for the
 * Archiefwet 1995 minimum of 3650 days.
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

use DateInterval;
use DateTimeImmutable;
use Throwable;

/**
 * Evaluates a signing schema's retention against the Archiefwet 1995 minimum.
 *
 * OpenRegister stamps each audit row's expiry from the retention of the object
 * the row describes; for an object without its own retention block that is the
 * schema's, in this order: `archive.bewaartermijnOverride`,
 * `archive.defaultBewaartermijn`, then the `x-openregister-archival`
 * annotation's `retention.default`. The first duration that parses wins; when
 * none does, OpenRegister keeps the rows indefinitely. This check reads the same
 * candidates in the same order, so it judges the horizon the audit rows really
 * get, not the one a single field suggests.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-trust-rebuild/specs/signing-audit-via-or/spec.md
 */
class SigningRetentionCheck {

	/**
	 * The minimum retention, in days, Archiefwet 1995 requires for signing records.
	 */
	public const MINIMUM_DAYS = 3650;

	/**
	 * The legal basis the minimum comes from, named in every failure.
	 */
	public const LEGAL_BASIS = 'Archiefwet 1995';

	/**
	 * Source token when no schema duration applies (rows are kept indefinitely).
	 */
	public const SOURCE_INDEFINITE = 'no-retention-policy:indefinite';

	/**
	 * The fixed instant durations are measured from.
	 *
	 * A fixed start keeps the verdict independent of the day the check runs.
	 * 2001-01-01 starts a non-leap year, so P10Y measures 3652 days.
	 */
	private const REFERENCE_INSTANT = '2001-01-01T00:00:00+00:00';

	/**
	 * Evaluate one schema's retention.
	 *
	 * @param string $schemaSlug The schema being checked, named in the message.
	 * @param mixed $archive The schema's `archive` block.
	 * @param mixed $configuration The schema's configuration.
	 *
	 * @return array{schema: string, passed: bool, source: string, duration: string|null, days: int|null, message: string}
	 *
	 * @spec openspec/changes/signing-trust-rebuild/specs/signing-audit-via-or/spec.md
	 */
	public function evaluate(string $schemaSlug, mixed $archive, mixed $configuration): array {
		foreach ($this->candidates(archive: $archive, configuration: $configuration) as $candidate) {
			$days = $this->daysOf(duration: $candidate['duration']);
			if ($days === null) {
				continue;
			}

			return $this->verdict(schemaSlug: $schemaSlug, source: $candidate['source'], duration: $candidate['duration'], days: $days);
		}

		return [
			'schema' => $schemaSlug,
			'passed' => true,
			'source' => self::SOURCE_INDEFINITE,
			'duration' => null,
			'days' => null,
			'message' => 'Signing register schema "' . $schemaSlug . '" declares no retention; '
				. 'OpenRegister keeps its audit trail indefinitely, which meets ' . self::LEGAL_BASIS . '.',
		];
	}//end evaluate()

	/**
	 * Build the verdict for a resolved duration.
	 *
	 * @param string $schemaSlug The schema being checked.
	 * @param string $source Where the duration came from.
	 * @param string $duration The ISO-8601 duration.
	 * @param int $days The duration in days.
	 *
	 * @return array{schema: string, passed: bool, source: string, duration: string, days: int, message: string}
	 */
	private function verdict(string $schemaSlug, string $source, string $duration, int $days): array {
		$passed = $days >= self::MINIMUM_DAYS;

		$message = 'Signing register schema "' . $schemaSlug . '" keeps its audit trail for ' . $duration
			. ' (' . $days . ' days, from ' . $source . ')';
		if ($passed === true) {
			$message .= ', which meets the ' . self::LEGAL_BASIS . ' minimum of ' . self::MINIMUM_DAYS . ' days.';
		} else {
			$message .= '; ' . self::LEGAL_BASIS . ' requires at least ' . self::MINIMUM_DAYS . ' days for the signing register.';
		}

		return [
			'schema' => $schemaSlug,
			'passed' => $passed,
			'source' => $source,
			'duration' => $duration,
			'days' => $days,
			'message' => $message,
		];
	}//end verdict()

	/**
	 * The schema's retention durations, in the order OpenRegister reads them.
	 *
	 * @param mixed $archive The schema's `archive` block.
	 * @param mixed $configuration The schema's configuration.
	 *
	 * @return array<int, array{duration: mixed, source: string}>
	 */
	private function candidates(mixed $archive, mixed $configuration): array {
		$candidates = [];
		if (is_array($archive) === true) {
			$candidates[] = ['duration' => ($archive['bewaartermijnOverride'] ?? null), 'source' => 'schema.archive.bewaartermijnOverride'];
			$candidates[] = ['duration' => ($archive['defaultBewaartermijn'] ?? null), 'source' => 'schema.archive.defaultBewaartermijn'];
		}

		if (is_array($configuration) === true) {
			$candidates[] = [
				'duration' => ($configuration['x-openregister-archival']['retention']['default'] ?? null),
				'source' => 'schema.x-openregister-archival.retention.default',
			];
		}

		return $candidates;
	}//end candidates()

	/**
	 * The number of days an ISO-8601 duration spans, or null when it does not parse.
	 *
	 * @param mixed $duration The candidate duration.
	 *
	 * @return int|null The days, or null for an absent or unparseable duration.
	 */
	private function daysOf(mixed $duration): ?int {
		if (is_string($duration) === false || $duration === '') {
			return null;
		}

		try {
			$interval = new DateInterval($duration);
		} catch (Throwable) {
			return null;
		}

		$start = new DateTimeImmutable(self::REFERENCE_INSTANT);
		$days  = $start->diff($start->add($interval))->days;
		if ($days === false) {
			return null;
		}

		return $days;
	}//end daysOf()
}//end class
