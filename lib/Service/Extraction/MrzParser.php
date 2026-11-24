<?php

/**
 * MRZ Parser
 *
 * Pure reader of the machine-readable zone of an identity document (ICAO
 * 9303): the two 44-character lines of a passport (TD3) and the three
 * 30-character lines of an identity card (TD1). Every field carries a
 * confidence: high when the zone's check digits hold, low when they do not
 * (REQ-EDE-001).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

use DateTimeImmutable;

/**
 * Reads names, dates and numbers from a machine-readable zone.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-001
 */
class MrzParser {

	/**
	 * Confidence of a field when the check digits hold.
	 */
	public const CHECKED = 0.95;

	/**
	 * Confidence of a field when a check digit fails (an OCR misread).
	 */
	public const UNCHECKED = 0.5;

	/**
	 * Read the zone in the text, or null when there is none.
	 *
	 * @param string            $text  The document text (OCR).
	 * @param DateTimeImmutable $today To place a two-digit birth year in its century.
	 *
	 * @return array<string, array{value: string, confidence: float}>|null
	 *
	 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-001
	 */
	public function parse(string $text, DateTimeImmutable $today): ?array {
		$lines = $this->zoneLines(text: $text);
		$passport = $this->runOf(lines: $lines, length: 44, count: 2);
		if ($passport !== null) {
			return $this->passport(lines: $passport, today: $today);
		}

		$card = $this->runOf(lines: $lines, length: 30, count: 3);
		if ($card !== null) {
			return $this->card(lines: $card, today: $today);
		}

		return null;
	}//end parse()

	/**
	 * The ICAO 9303 check digit of a field: weights 7, 3, 1; digits as
	 * themselves, A to Z as 10 to 35, the filler as 0.
	 *
	 * @param string $field The field.
	 *
	 * @return int
	 *
	 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-001
	 */
	public static function checkDigit(string $field): int {
		$weights = [7, 3, 1];
		$sum = 0;
		foreach (str_split($field) as $index => $char) {
			$value = 0;
			if (ctype_digit($char) === true) {
				$value = (int)$char;
			}

			if (ctype_upper($char) === true) {
				$value = (ord($char) - 55);
			}

			$sum += ($value * $weights[($index % 3)]);
		}

		return ($sum % 10);
	}//end checkDigit()

	/**
	 * The text's lines that look like zone lines: capitals, digits and
	 * fillers only, spaces taken out.
	 *
	 * @param string $text The text.
	 *
	 * @return list<string>
	 */
	private function zoneLines(string $text): array {
		$lines = [];
		foreach (preg_split('/\R/u', strtoupper($text)) as $line) {
			$line = str_replace(' ', '', trim($line));
			if (preg_match('/^[A-Z0-9<]{28,46}$/', $line) === 1 && str_contains($line, '<') === true) {
				$lines[] = $line;
			}
		}

		return $lines;
	}//end zoneLines()

	/**
	 * The first run of `$count` consecutive lines of `$length`.
	 *
	 * @param list<string> $lines  The zone-like lines.
	 * @param int          $length The line length.
	 * @param int          $count  How many lines.
	 *
	 * @return list<string>|null
	 */
	private function runOf(array $lines, int $length, int $count): ?array {
		for ($start = 0; $start + $count <= count($lines); $start++) {
			$run = array_slice($lines, $start, $count);
			if (count(array_filter($run, static fn (string $line): bool => strlen($line) === $length)) === $count) {
				return $run;
			}
		}

		return null;
	}//end runOf()

	/**
	 * A passport zone (TD3).
	 *
	 * @param list<string>      $lines The two lines.
	 * @param DateTimeImmutable $today Today.
	 *
	 * @return array<string, array{value: string, confidence: float}>
	 */
	private function passport(array $lines, DateTimeImmutable $today): array {
		[$top, $bottom] = $lines;
		$number = substr($bottom, 0, 9);
		$birth = substr($bottom, 13, 6);
		$expiry = substr($bottom, 21, 6);
		$personal = substr($bottom, 28, 14);
		$composite = substr($bottom, 0, 10).substr($bottom, 13, 7).substr($bottom, 21, 22);
		$checks = [
			[$number, $bottom[9]],
			[$birth, $bottom[19]],
			[$expiry, $bottom[27]],
			[$composite, $bottom[43]],
		];

		return $this->fields(
			names: substr($top, 5),
			parts: [
				'documentNumber' => $number,
				'nationality' => substr($bottom, 10, 3),
				'birth' => $birth,
				'expiry' => $expiry,
				'personal' => $personal,
			],
			checked: $this->allHold(checks: $checks),
			today: $today
		);
	}//end passport()

	/**
	 * An identity card zone (TD1).
	 *
	 * @param list<string>      $lines The three lines.
	 * @param DateTimeImmutable $today Today.
	 *
	 * @return array<string, array{value: string, confidence: float}>
	 */
	private function card(array $lines, DateTimeImmutable $today): array {
		[$top, $middle, $bottom] = $lines;
		$number = substr($top, 5, 9);
		$birth = substr($middle, 0, 6);
		$expiry = substr($middle, 8, 6);
		$composite = substr($top, 5, 25).substr($middle, 0, 7).substr($middle, 8, 7).substr($middle, 18, 11);
		$checks = [
			[$number, $top[14]],
			[$birth, $middle[6]],
			[$expiry, $middle[14]],
			[$composite, $middle[29]],
		];

		return $this->fields(
			names: $bottom,
			parts: [
				'documentNumber' => $number,
				'nationality' => substr($middle, 15, 3),
				'birth' => $birth,
				'expiry' => $expiry,
				'personal' => substr($top, 15, 15),
			],
			checked: $this->allHold(checks: $checks),
			today: $today
		);
	}//end card()

	/**
	 * Whether every check digit holds.
	 *
	 * @param list<array{0: string, 1: string}> $checks Field and its check digit.
	 *
	 * @return bool
	 */
	private function allHold(array $checks): bool {
		foreach ($checks as [$field, $digit]) {
			if (ctype_digit($digit) === false || self::checkDigit(field: $field) !== (int)$digit) {
				return false;
			}
		}

		return true;
	}//end allHold()

	/**
	 * The fields, each with the zone's confidence; empty parts are left out.
	 *
	 * @param string                $names   The name field (surname, `<<`, given names).
	 * @param array<string, string> $parts   The raw number, nationality, dates and personal number.
	 * @param bool                  $checked Whether the check digits hold.
	 * @param DateTimeImmutable     $today   Today.
	 *
	 * @return array<string, array{value: string, confidence: float}>
	 */
	private function fields(string $names, array $parts, bool $checked, DateTimeImmutable $today): array {
		$halves = explode('<<', rtrim($names, '<'), 2);
		$values = [
			'lastName' => trim(str_replace('<', ' ', $halves[0])),
			'firstName' => trim(str_replace('<', ' ', ($halves[1] ?? ''))),
			'dateOfBirth' => $this->date(raw: $parts['birth'], today: $today, past: true),
			'documentNumber' => rtrim($parts['documentNumber'], '<'),
			'expiryDate' => $this->date(raw: $parts['expiry'], today: $today, past: false),
			'nationality' => rtrim($parts['nationality'], '<'),
			'personalNumber' => trim(str_replace('<', '', $parts['personal'])),
		];
		$confidence = self::UNCHECKED;
		if ($checked === true) {
			$confidence = self::CHECKED;
		}

		$fields = [];
		foreach ($values as $name => $value) {
			if ($value !== '') {
				$fields[$name] = ['value' => $value, 'confidence' => $confidence];
			}
		}

		return $fields;
	}//end fields()

	/**
	 * A YYMMDD date as Y-m-d: a birth date in the past century when it would
	 * lie ahead, an expiry date in this century; '' when not a date.
	 *
	 * @param string            $raw   The six digits.
	 * @param DateTimeImmutable $today Today.
	 * @param bool              $past  Whether the date lies before today (a birth date).
	 *
	 * @return string
	 */
	private function date(string $raw, DateTimeImmutable $today, bool $past): string {
		if (preg_match('/^(\d{2})(\d{2})(\d{2})$/', $raw, $match) !== 1 || checkdate((int)$match[2], (int)$match[3], 2000) === false) {
			return '';
		}

		$year = (2000 + (int)$match[1]);
		if ($past === true && $year > (int)$today->format('Y')) {
			$year -= 100;
		}

		return sprintf('%04d-%s-%s', $year, $match[2], $match[3]);
	}//end date()
}//end class
