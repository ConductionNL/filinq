<?php

/**
 * MDTO Mapping Service
 *
 * Fills what Filinq can derive of a record's MDTO supplement, as proposals,
 * and tells whether a record carries the MDTO minimum set before it may be
 * transferred to the e-depot.
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
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Prefill (design D2) and the completeness gate (design D3).
 *
 * The six core archival attributes come from the record's `retention`,
 * which OpenRegister owns; the attributes OpenRegister does not model come
 * from the record's `mdtoSupplement`. Prefilled values are proposals: they
 * are flagged and an operator confirms or overwrites them.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
 */
class MdtoMappingService {

	/**
	 * The appraisal values that mean the record is kept permanently.
	 */
	private const KEEP = ['bewaren', 'blijvend_bewaren'];

	/**
	 * What a record type is as an MDTO aggregation level.
	 */
	private const AGGREGATION = ['document' => 'archiefstuk', 'dossier' => 'dossier'];

	/**
	 * The derivable supplement values for a record, each flagged as a proposal.
	 *
	 * `aggregatieniveau` follows the record type. `dekkingInTijd` runs from the
	 * document's creation to its closure; for a dossier from the earliest to
	 * the latest date of its members. A value that cannot be derived is left
	 * out rather than guessed.
	 *
	 * @param string                           $recordType `document` or `dossier`.
	 * @param array<string, mixed>             $record     The record (`created`, `closedAt`).
	 * @param array<int, array<string, mixed>> $members    A dossier's documents; empty for a document.
	 *
	 * @return array<string, array{value: mixed, proposed: bool}> Field => proposal.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function prefill(string $recordType, array $record, array $members=[]): array {
		$proposals = [];
		if (isset(self::AGGREGATION[$recordType]) === true) {
			$proposals['aggregatieniveau'] = self::proposal(value: self::AGGREGATION[$recordType]);
		}

		$dates = self::datesOf(record: $record);
		if ($recordType === 'dossier') {
			$dates = [];
			foreach ($members as $member) {
				$dates = array_merge($dates, self::datesOf(record: $member));
			}
		}

		if ($dates !== []) {
			sort($dates);
			$proposals['dekkingInTijdBegin'] = self::proposal(value: $dates[0]);
			$proposals['dekkingInTijdEind']  = self::proposal(value: $dates[count($dates) - 1]);
		}

		return $proposals;

	}//end prefill()

	/**
	 * Whether a record carries the MDTO minimum set, and which fields it lacks.
	 *
	 * The set: identificatie, naam, waardering (`retention.archiefnominatie`),
	 * classificatie, archiefvormer (the organisation identifier or the
	 * supplement's override) and aggregatieniveau; for a record that is kept
	 * permanently also dekkingInTijd. A proposal nobody confirmed does not
	 * count.
	 *
	 * @param array<string, mixed> $record         The record (`uuid`, `title`, `retention`).
	 * @param array<string, mixed> $supplement     Its mdtoSupplement.
	 * @param string               $organisationId OpenRegister's organisation identifier.
	 *
	 * @return array{complete: bool, missing: array<int, string>}
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
	 */
	public function validateCompleteness(array $record, array $supplement, string $organisationId): array {
		$retention = (array) ($record['retention'] ?? []);
		$present   = [
			'identificatie' => self::filled(value: ($record['uuid'] ?? null)),
			'naam' => self::filled(value: ($record['title'] ?? ($record['naam'] ?? null))),
			'waardering' => self::filled(value: ($retention['archiefnominatie'] ?? null)),
			'classificatie' => self::filled(value: ($retention['classificatie'] ?? null)),
			'archiefvormer' => self::filled(value: ($supplement['archiefvormerOverride'] ?? $organisationId)),
			'aggregatieniveau' => self::confirmed(supplement: $supplement, field: 'aggregatieniveau'),
		];
		if (in_array((string) ($retention['archiefnominatie'] ?? ''), self::KEEP, true) === true) {
			$present['dekkingInTijd'] = self::confirmed(supplement: $supplement, field: 'dekkingInTijdBegin');
		}

		$missing = array_keys(array_filter($present, static fn (bool $filled): bool => $filled === false));

		return ['complete' => $missing === [], 'missing' => $missing];

	}//end validateCompleteness()

	/**
	 * A supplement field that holds a value nobody left as an unconfirmed proposal.
	 *
	 * @param array<string, mixed> $supplement The supplement.
	 * @param string               $field      The field.
	 *
	 * @return bool
	 */
	private static function confirmed(array $supplement, string $field): bool {
		$value = ($supplement[$field] ?? null);
		if (is_array($value) === true && array_key_exists('value', $value) === true) {
			return ($value['proposed'] ?? false) !== true && self::filled(value: $value['value']);
		}

		return self::filled(value: $value);

	}//end confirmed()

	/**
	 * Whether a value is present: not null and not blank.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	private static function filled(mixed $value): bool {
		if (is_string($value) === true) {
			return trim($value) !== '';
		}

		return $value !== null && $value !== [];

	}//end filled()

	/**
	 * A value flagged as a proposal.
	 *
	 * @param mixed $value The derived value.
	 *
	 * @return array{value: mixed, proposed: bool}
	 */
	private static function proposal(mixed $value): array {
		return ['value' => $value, 'proposed' => true];

	}//end proposal()

	/**
	 * A record's creation and closure dates that can be read, as Y-m-d.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return array<int, string>
	 */
	private static function datesOf(array $record): array {
		$dates = [];
		foreach (['created', 'closedAt'] as $field) {
			$value = ($record[$field] ?? null);
			if (is_string($value) === true && preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $match) === 1) {
				$dates[] = $match[1];
			}
		}

		return $dates;

	}//end datesOf()
}//end class
