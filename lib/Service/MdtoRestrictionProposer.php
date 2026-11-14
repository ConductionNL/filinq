<?php

/**
 * MDTO Restriction Proposer
 *
 * Derives the beperkingGebruik proposals Filinq can make from what it knows
 * about why a record's use is restricted.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;

/**
 * Design D2: an unresolved or objected consent proposes an AVG restriction
 * with its legal basis; an active matching prohibition proposes a Woo
 * exception with its legal authority; an anonymised rendition proposes a note
 * that the public rendition is the anonymised one. Every entry is flagged
 * `proposed` and names its source; an operator confirms it before export.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
 */
final class MdtoRestrictionProposer {

	/**
	 * Consent states that leave the record's personal data restricted:
	 * an objection, or no answer yet.
	 */
	private const RESTRICTING_CONSENT = ['objection_received', 'pending', 'no_response'];

	/**
	 * The grondslag used when a consent record names none.
	 */
	private const AVG_FALLBACK = 'AVG';

	/**
	 * The grondslag used when a prohibition names no legal authority.
	 */
	private const WOO_FALLBACK = 'Woo art. 5.1';

	/**
	 * The proposed restrictions, without duplicates, in a stable order.
	 *
	 * @param MdtoRestrictionSources $sources The record's consent, prohibition and anonymisation state.
	 * @param string                 $today   The reference date (Y-m-d) prohibitions are checked against.
	 *
	 * @return array<int, array<string, mixed>> beperkingGebruik entries flagged `proposed`.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function propose(MdtoRestrictionSources $sources, string $today): array {
		$entries = [];
		foreach ($sources->consents as $consent) {
			if (in_array((string) ($consent['consentStatus'] ?? ''), self::RESTRICTING_CONSENT, true) === true) {
				$entries[] = self::entry(
					type: 'avg-persoonsgegevens',
					grondslag: self::textOr(value: ($consent['legalBasis'] ?? null), fallback: self::AVG_FALLBACK),
					source: 'consent'
				);
			}
		}

		foreach ($sources->prohibitions as $prohibition) {
			if (self::isActive(prohibition: $prohibition, today: $today) === true) {
				$entries[] = self::entry(
					type: 'woo-uitzondering',
					grondslag: self::textOr(value: ($prohibition['legalAuthority'] ?? null), fallback: self::WOO_FALLBACK),
					source: 'prohibition'
				);
			}
		}

		foreach ($sources->renditions as $rendition) {
			$name = self::textOr(value: ($rendition['anonymizedFileName'] ?? null), fallback: '');
			if ($name !== '') {
				$entry = self::entry(type: 'avg-persoonsgegevens', grondslag: self::AVG_FALLBACK, source: 'anonymisation');
				$entry['omschrijving'] = 'De openbare versie is de geanonimiseerde afleiding ' . $name . '.';
				$entries[] = $entry;
			}
		}

		return array_values(array_unique($entries, SORT_REGULAR));

	}//end propose()

	/**
	 * Whether a prohibition is active on the reference date.
	 *
	 * @param array<string, mixed> $prohibition The prohibition.
	 * @param string               $today       Y-m-d.
	 *
	 * @return bool
	 */
	private static function isActive(array $prohibition, string $today): bool {
		if (($prohibition['active'] ?? false) !== true) {
			return false;
		}

		$from  = self::dateOf(value: ($prohibition['validFrom'] ?? null));
		$until = self::dateOf(value: ($prohibition['validUntil'] ?? null));

		return ($from === null || $from <= $today) && ($until === null || $until >= $today);

	}//end isActive()

	/**
	 * The Y-m-d date a value starts with, or null.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string|null
	 */
	private static function dateOf(mixed $value): ?string {
		if (is_string($value) === false || preg_match('/^\d{4}-\d{2}-\d{2}/', $value) !== 1) {
			return null;
		}

		$date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
		if ($date === false) {
			return null;
		}

		return $date->format('Y-m-d');

	}//end dateOf()

	/**
	 * A trimmed text, or the fallback when the value is blank or no text.
	 *
	 * @param mixed  $value    The value.
	 * @param string $fallback The fallback.
	 *
	 * @return string
	 */
	private static function textOr(mixed $value, string $fallback): string {
		if (is_string($value) === true && trim($value) !== '') {
			return trim($value);
		}

		return $fallback;

	}//end textOr()

	/**
	 * One proposed restriction.
	 *
	 * @param string $type      The restriction type.
	 * @param string $grondslag The legal ground.
	 * @param string $source    What it was derived from.
	 *
	 * @return array<string, mixed>
	 */
	private static function entry(string $type, string $grondslag, string $source): array {
		return ['type' => $type, 'grondslag' => $grondslag, 'proposed' => true, 'source' => $source];

	}//end entry()
}//end class
