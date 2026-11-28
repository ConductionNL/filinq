<?php

/**
 * CV Profile Parser
 *
 * Pure, side-effect-free heuristic reader of a candidate profile in the text of
 * a CV: the highest education level on the Dutch qualification scale (NLQF 1 to
 * 8), the years of experience under a work-experience heading, and the
 * competence codes the caller lists (REQ-CVP-001).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

use DateTimeImmutable;

/**
 * Reads education level, years of experience and listed competences from CV text.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
 */
class CvProfileParser {

	/**
	 * Education patterns per NLQF level, highest first. Short acronyms are
	 * case-sensitive so a Dutch word like "ma" is not read as a degree.
	 *
	 * @var array<int, list<string>>
	 */
	private const EDUCATION = [
		8 => ['/\b(phd|ph\.d\.?|doctoraat|gepromoveerd|promotieonderzoek)\b/iu'],
		7 => ['/\b(master|wo[- ]master|doctoraal|drs\.|ir\.|mr\.)/iu', '/\b(MSc|M\.Sc\.?|MA|LLM|MBA|WO)\b/u'],
		6 => ['/\b(bachelor|hbo|wo[- ]bachelor|ing\.|bc\.)/iu', '/\b(BSc|B\.Sc\.?|BA)\b/u'],
		5 => ['/\b(associate degree)\b/iu'],
		4 => ['/\b(mbo|niveau)[- ]?4\b/iu', '/\b(havo|vwo|atheneum|gymnasium)\b/iu'],
		3 => ['/\b(mbo|niveau)[- ]?3\b/iu'],
		2 => ['/\b(mbo|niveau)[- ]?2\b/iu', '/\b(vmbo)\b/iu'],
		1 => ['/\b(mbo|niveau)[- ]?1\b/iu', '/\b(entreeopleiding|entree-opleiding)\b/iu'],
	];

	/**
	 * A heading that opens the work-experience section.
	 */
	private const EXPERIENCE_HEADING = '/^\s*(werkervaring|relevante werkervaring|work experience|professional experience|'
		.'employment history|experience|ervaring|loopbaan)\s*:?\s*$/iu';

	/**
	 * A heading that closes it.
	 */
	private const OTHER_HEADING = '/^\s*(opleidingen?|education|vaardigheden|skills|competenties|competences|talen|languages|'
		.'cursussen|courses|certificaten|certifications|hobby\'?s|interests|referenties|references|'
		.'persoonlijke gegevens|personal details|profiel|profile|nevenactiviteiten)\s*:?\s*$/iu';

	/**
	 * Dutch and English month names and abbreviations, to month numbers.
	 *
	 * @var array<string, int>
	 */
	private const MONTHS = [
		'jan' => 1, 'januari' => 1, 'january' => 1, 'feb' => 2, 'februari' => 2, 'february' => 2,
		'mrt' => 3, 'maart' => 3, 'mar' => 3, 'march' => 3, 'apr' => 4, 'april' => 4,
		'mei' => 5, 'may' => 5, 'jun' => 6, 'juni' => 6, 'june' => 6, 'jul' => 7, 'juli' => 7, 'july' => 7,
		'aug' => 8, 'augustus' => 8, 'august' => 8, 'sep' => 9, 'sept' => 9, 'september' => 9,
		'okt' => 10, 'oct' => 10, 'oktober' => 10, 'october' => 10, 'nov' => 11, 'november' => 11,
		'dec' => 12, 'december' => 12,
	];

	/**
	 * One moment of a period: an optional month (number or name) and a year.
	 */
	private const MOMENT = '(?:(\d{1,2})[\/\-.]|([a-z]{3,9})\.?\s+)?((?:19|20)\d{2})';

	/**
	 * Read the profile.
	 *
	 * @param string             $text            The CV text.
	 * @param list<string>       $competenceCodes The competence codes to look for.
	 * @param DateTimeImmutable  $today           The day an open period runs to.
	 *
	 * @return array{educationLevel: int|null, experienceYears: float|null, competenceCodes: list<string>}
	 *
	 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
	 */
	public function parse(string $text, array $competenceCodes, DateTimeImmutable $today): array {
		return [
			'educationLevel' => $this->educationLevel(text: $text),
			'experienceYears' => $this->experienceYears(text: $text, today: $today),
			'competenceCodes' => $this->competences(text: $text, codes: $competenceCodes),
		];
	}//end parse()

	/**
	 * The highest education level named in the text, or null.
	 *
	 * @param string $text The CV text.
	 *
	 * @return int|null NLQF 1 to 8.
	 *
	 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
	 */
	public function educationLevel(string $text): ?int {
		foreach (self::EDUCATION as $level => $patterns) {
			foreach ($patterns as $pattern) {
				if (preg_match($pattern, $text) === 1) {
					return $level;
				}
			}
		}

		return null;
	}//end educationLevel()

	/**
	 * The years of experience in the periods under a work-experience heading,
	 * overlaps counted once and an open period up to today; null without
	 * such a heading or without a period under it.
	 *
	 * @param string            $text  The CV text.
	 * @param DateTimeImmutable $today The day an open period runs to.
	 *
	 * @return float|null Years, one decimal.
	 *
	 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
	 */
	public function experienceYears(string $text, DateTimeImmutable $today): ?float {
		$section = $this->experienceSection(text: $text);
		if ($section === null) {
			return null;
		}

		$now = (((int)$today->format('Y')) * 12 + ((int)$today->format('n')) - 1);
		$pattern = '/'.self::MOMENT.'\s*(?:-|–|—|tot|to|t\/m)\s*(?:'.self::MOMENT.'|(heden|nu|present|current|now|today))/iu';
		if (preg_match_all($pattern, $section, $matches, PREG_SET_ORDER) === 0) {
			return null;
		}

		$periods = [];
		foreach ($matches as $match) {
			$start = $this->month(number: $match[1], name: $match[2], year: $match[3]);
			$end = $now;
			if (($match[6] ?? '') !== '') {
				$end = $this->month(number: $match[4], name: $match[5], year: $match[6]);
			}

			if ($end >= $start && $start <= $now) {
				$periods[] = [$start, min($end, $now)];
			}
		}

		if ($periods === []) {
			return null;
		}

		return round(($this->monthsCovered(periods: $periods) / 12), 1);
	}//end experienceYears()

	/**
	 * The caller's competence codes that occur in the text as words, in the
	 * caller's order. A hyphen, an underscore or a space in a code matches
	 * any of the three.
	 *
	 * @param string       $text  The CV text.
	 * @param list<string> $codes The codes to look for.
	 *
	 * @return list<string>
	 *
	 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
	 */
	public function competences(string $text, array $codes): array {
		$found = [];
		foreach ($codes as $code) {
			$code = trim((string)$code);
			if ($code === '' || in_array($code, $found, true) === true) {
				continue;
			}

			$words = preg_split('/[-_\s]+/u', $code);
			$quoted = array_map(static fn (string $word): string => preg_quote(str: $word, delimiter: '/'), $words);
			$pattern = '/(?<![\p{L}\p{N}])'.implode('[-_\s]+', $quoted).'(?![\p{L}\p{N}])/iu';
			if (preg_match($pattern, $text) === 1) {
				$found[] = $code;
			}
		}

		return $found;
	}//end competences()

	/**
	 * The lines under the work-experience heading, up to the next heading.
	 *
	 * @param string $text The CV text.
	 *
	 * @return string|null Null without the heading.
	 */
	private function experienceSection(string $text): ?string {
		$lines = preg_split('/\R/u', $text);
		$inside = false;
		$section = [];
		foreach ($lines as $line) {
			if (preg_match(self::EXPERIENCE_HEADING, $line) === 1) {
				$inside = true;
				continue;
			}

			if ($inside === true && preg_match(self::OTHER_HEADING, $line) === 1) {
				$inside = false;
			}

			if ($inside === true) {
				$section[] = $line;
			}
		}

		if ($section === []) {
			return null;
		}

		return implode("\n", $section);
	}//end experienceSection()

	/**
	 * A moment as a month index (year * 12 + month - 1); January when no
	 * month is given or the name is not a month.
	 *
	 * @param string $number The month number, or ''.
	 * @param string $name   The month name, or ''.
	 * @param string $year   The four-digit year.
	 *
	 * @return int
	 */
	private function month(string $number, string $name, string $year): int {
		$month = 1;
		if ($number !== '' && (int)$number >= 1 && (int)$number <= 12) {
			$month = (int)$number;
		}

		$name = strtolower($name);
		if ($number === '' && isset(self::MONTHS[$name]) === true) {
			$month = self::MONTHS[$name];
		}

		return ((int)$year * 12 + $month - 1);
	}//end month()

	/**
	 * The months the periods cover together, overlaps once.
	 *
	 * @param list<array{0: int, 1: int}> $periods Start and end month indexes.
	 *
	 * @return int
	 */
	private function monthsCovered(array $periods): int {
		usort($periods, static fn (array $one, array $two): int => ($one[0] <=> $two[0]));
		$total = 0;
		[$start, $end] = $periods[0];
		foreach (array_slice($periods, 1) as [$nextStart, $nextEnd]) {
			if ($nextStart <= $end) {
				$end = max($end, $nextEnd);
				continue;
			}

			$total += ($end - $start);
			[$start, $end] = [$nextStart, $nextEnd];
		}

		return ($total + $end - $start);
	}//end monthsCovered()
}//end class
