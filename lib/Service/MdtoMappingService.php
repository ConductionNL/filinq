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

use DateTimeImmutable;

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
	 * OpenRegister's generator constant whose keys are the appraisals it can map.
	 */
	private const OR_APPRAISAL_MAP = 'OCA\\OpenRegister\\Service\\Edepot\\MdtoXmlGenerator::APPRAISAL_MAP';

	/**
	 * The appraisals OpenRegister's generator maps, for when OpenRegister is
	 * not loaded (pinned at OpenRegister 5db8e48b9; read live otherwise).
	 */
	private const APPRAISALS_FALLBACK = ['bewaren', 'blijvend_bewaren', 'vernietigen', 'nog_niet_bepaald'];

	/**
	 * An xsd:duration, the form OpenRegister requires of `bewaartermijn`.
	 */
	private const XSD_DURATION = '/^-?P(?=\\d|T\\d)(\\d+Y)?(\\d+M)?(\\d+D)?(T(?=\\d)(\\d+H)?(\\d+M)?(\\d+(\\.\\d+)?S)?)?$/';

	/**
	 * An xsd:dateTime, the form OpenRegister requires of a file's checksum date.
	 */
	private const XSD_DATE_TIME = '/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(\\.\\d+)?(Z|[+-]\\d{2}:\\d{2})?$/';

	/**
	 * Constructor.
	 *
	 * @param MdtoRestrictionProposer $proposer Derives the use-restriction proposals.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function __construct(
		private readonly MdtoRestrictionProposer $proposer=new MdtoRestrictionProposer(),
	) {

	}//end __construct()

	/**
	 * The derivable supplement values for a record, each flagged as a proposal.
	 *
	 * `aggregatieniveau` follows the record type. `dekkingInTijd` runs from the
	 * document's creation to its closure; for a dossier from the earliest to
	 * the latest date of its members. `beperkingGebruik` lists the
	 * restrictions the consent, prohibition and anonymisation state propose,
	 * each entry itself flagged `proposed`. A value that cannot be derived is
	 * left out rather than guessed.
	 *
	 * @param string                           $recordType `document` or `dossier`.
	 * @param array<string, mixed>             $record     The record (`created`, `closedAt`).
	 * @param array<int, array<string, mixed>> $members    A dossier's documents; empty for a document.
	 * @param MdtoRestrictionSources|null      $sources    The consent, prohibition and anonymisation state
	 *                                                     `beperkingGebruik` is proposed from; null proposes none.
	 * @param string                           $today      The date (Y-m-d) prohibitions are checked against; '' is today.
	 *
	 * @return array<string, array{value: mixed, proposed: bool}> Field => proposal.
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
	 */
	public function prefill(
		string $recordType,
		array $record,
		array $members=[],
		?MdtoRestrictionSources $sources=null,
		string $today=''
	): array {
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

		if ($sources !== null) {
			if ($today === '') {
				$today = (new DateTimeImmutable())->format('Y-m-d');
			}

			$restrictions = $this->proposer->propose(sources: $sources, today: $today);
			if ($restrictions !== []) {
				$proposals['beperkingGebruik'] = self::proposal(value: $restrictions);
			}
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
	 * The gate never says complete where OpenRegister's MDTO generation or
	 * transfer would refuse (its MdtoPreconditions): the appraisal must be one
	 * the generator maps, `retention.bewaartermijn` must be an xsd:duration
	 * (the transfer refuses a record without one), the organisation identifier
	 * must be set even when the supplement overrides the archiefvormer (the
	 * generator reads it for the identificatie bron), and every file must
	 * carry name, format, size, a sha256 checksum and its date. Those extra
	 * findings are named as OpenRegister names them.
	 *
	 * @param array<string, mixed>             $record         The record (`uuid`, `title`, `retention`).
	 * @param array<string, mixed>             $supplement     Its mdtoSupplement.
	 * @param string                           $organisationId OpenRegister's organisation identifier.
	 * @param array<int, array<string, mixed>> $files          The files the sidecar describes
	 *                                                         (name, format, size, checksum, checksumDate).
	 *
	 * @return array{complete: bool, missing: array<int, string>}
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-completeness-validation-gates-overbrenging-req-ddtmm-004
	 */
	public function validateCompleteness(array $record, array $supplement, string $organisationId, array $files=[]): array {
		$retention = (array) ($record['retention'] ?? []);
		$appraisal = (string) ($retention['archiefnominatie'] ?? '');
		$present   = [
			'identificatie' => self::filled(value: ($record['uuid'] ?? null)),
			'naam' => self::filled(value: ($record['title'] ?? ($record['naam'] ?? null))),
			'waardering' => in_array($appraisal, self::appraisals(), true),
			'classificatie' => self::filled(value: ($retention['classificatie'] ?? null)),
			'archiefvormer' => self::filled(value: ($supplement['archiefvormerOverride'] ?? $organisationId)),
			'aggregatieniveau' => self::confirmed(supplement: $supplement, field: 'aggregatieniveau'),
		];
		if (in_array($appraisal, self::KEEP, true) === true) {
			$present['dekkingInTijd'] = self::confirmed(supplement: $supplement, field: 'dekkingInTijdBegin');
		}

		$present['bewaartermijn'] = preg_match(self::XSD_DURATION, (string) ($retention['bewaartermijn'] ?? '')) === 1;
		$present['organisation_identifier'] = self::filled(value: $organisationId) || $present['archiefvormer'] === false;

		$missing = array_keys(array_filter($present, static fn (bool $filled): bool => $filled === false));
		foreach ($files as $index => $file) {
			$missing = array_merge($missing, self::missingFileInputs(index: (int) $index, file: (array) $file));
		}

		return ['complete' => $missing === [], 'missing' => $missing];

	}//end validateCompleteness()

	/**
	 * The appraisals OpenRegister's generator maps.
	 *
	 * @return array<int, string>
	 */
	private static function appraisals(): array {
		if (defined(self::OR_APPRAISAL_MAP) === true) {
			return array_map('strval', array_keys((array) constant(self::OR_APPRAISAL_MAP)));
		}

		return self::APPRAISALS_FALLBACK;

	}//end appraisals()

	/**
	 * The file inputs OpenRegister's bestand generator requires and this file lacks.
	 *
	 * @param int                  $index The file's position.
	 * @param array<string, mixed> $file  The file facts.
	 *
	 * @return array<int, string> Entries named `file[n].<input>`.
	 */
	private static function missingFileInputs(int $index, array $file): array {
		$checks  = [
			'name' => self::filled(value: ($file['name'] ?? null)),
			'format' => self::filled(value: ($file['format'] ?? null)),
			'size' => preg_match('/^\d+$/', (string) ($file['size'] ?? '')) === 1,
			'checksum' => preg_match('/^[0-9a-fA-F]{64}$/', (string) ($file['checksum'] ?? '')) === 1,
			'checksumDate' => preg_match(self::XSD_DATE_TIME, (string) ($file['checksumDate'] ?? '')) === 1,
		];
		$missing = [];
		foreach (array_keys(array_filter($checks, static fn (bool $ok): bool => $ok === false)) as $input) {
			$missing[] = 'file[' . $index . '].' . $input;
		}

		return $missing;

	}//end missingFileInputs()

	/**
	 * A supplement field that holds a value nobody left as an unconfirmed proposal.
	 *
	 * Unconfirmed is either the prefill shape (`{value, proposed: true}`) or
	 * the stored shape: the field named in the supplement's `proposals`.
	 *
	 * @param array<string, mixed> $supplement The supplement.
	 * @param string               $field      The field.
	 *
	 * @return bool
	 */
	private static function confirmed(array $supplement, string $field): bool {
		if (in_array($field, (array) ($supplement['proposals'] ?? []), true) === true) {
			return false;
		}

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
