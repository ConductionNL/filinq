<?php

/**
 * Unit tests for CvProfileParser
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use DateTimeImmutable;
use OCA\Filinq\Service\Extraction\CvProfileParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CvProfileParser.
 */
class CvProfileParserTest extends TestCase {

	/**
	 * The parser under test.
	 *
	 * @var CvProfileParser
	 */
	private CvProfileParser $parser;

	/**
	 * The day an open period runs to.
	 *
	 * @var DateTimeImmutable
	 */
	private DateTimeImmutable $today;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->parser = new CvProfileParser();
		$this->today = new DateTimeImmutable('2026-11-15');

	}//end setUp()

	/**
	 * The spec's CV: a master's degree and two jobs, the second still running.
	 *
	 * @return void
	 */
	public function testACvWithAMastersDegreeAndTwoJobs(): void {
		$text = implode(
			"\n",
			[
				'Sanne de Vries',
				'',
				'Opleiding',
				'Master Bedrijfskunde, Universiteit Utrecht 2014 - 2016',
				'HBO Bedrijfseconomie 2010 - 2014',
				'',
				'Werkervaring',
				'Salarisadministrateur, Acme BV 2018 - 2021',
				'Payroll specialist, Beta NV 2021 - heden',
				'Verwerkt loonheffing en jaaropgaven.',
				'',
				'Vaardigheden',
				'Excel, AFAS',
			]
		);

		$profile = $this->parser->parse(text: $text, competenceCodes: ['loonheffing', 'excel', 'sap'], today: $this->today);

		$this->assertSame(expected: 7, actual: $profile['educationLevel']);
		// January 2018 to November 2026: 106 months.
		$this->assertSame(expected: 8.8, actual: $profile['experienceYears']);
		$this->assertSame(expected: ['loonheffing', 'excel'], actual: $profile['competenceCodes']);
	}//end testACvWithAMastersDegreeAndTwoJobs()

	/**
	 * Education levels by the words a CV uses.
	 *
	 * @return array<string, array{0: string, 1: int|null}>
	 */
	public static function educations(): array {
		return [
			'phd' => ['PhD in Economics, 2012', 8],
			'msc' => ['MSc Computer Science', 7],
			'drs.' => ['drs. J. Jansen', 7],
			'bachelor' => ['Bachelor of Laws', 6],
			'hbo' => ['hbo-opleiding Personeel en Arbeid', 6],
			'associate degree' => ['Associate degree HRM', 5],
			'mbo 4' => ['MBO-4 Juridisch medewerker', 4],
			'havo' => ['Diploma HAVO 2008', 4],
			'mbo niveau 3' => ['Niveau 3 Administratief medewerker', 3],
			'vmbo' => ['vmbo-tl', 2],
			'entree' => ['Entreeopleiding Assistent logistiek', 1],
			'nothing' => ['Geen diploma genoemd', null],
			'a dutch "ma" is no degree' => ['mijn ma werkte in de zorg', null],
		];
	}//end educations()

	/**
	 * @param string   $text     The CV text.
	 * @param int|null $expected The level.
	 *
	 * @return void
	 */
	#[DataProvider('educations')]
	public function testTheEducationLevelFollowsTheDegree(string $text, ?int $expected): void {
		$this->assertSame(expected: $expected, actual: $this->parser->educationLevel(text: $text));
	}//end testTheEducationLevelFollowsTheDegree()

	/**
	 * The highest level wins when a CV lists several.
	 *
	 * @return void
	 */
	public function testTheHighestLevelWins(): void {
		$this->assertSame(expected: 6, actual: $this->parser->educationLevel(text: "MBO 4 Bedrijfsadministratie\nHBO Accountancy"));
	}//end testTheHighestLevelWins()

	/**
	 * Without a work-experience heading the years are unknown, not guessed
	 * from the education dates.
	 *
	 * @return void
	 */
	public function testYearsWithoutAnExperienceHeadingAreUnknown(): void {
		$this->assertNull(actual: $this->parser->experienceYears(text: "Opleiding\nHBO 2010 - 2014\n", today: $this->today));
	}//end testYearsWithoutAnExperienceHeadingAreUnknown()

	/**
	 * Overlapping jobs count once; months and month names are read; the
	 * education section after the experience is not counted.
	 *
	 * @return void
	 */
	public function testOverlappingPeriodsCountOnce(): void {
		$text = "Work experience\n03/2019 - 06/2020 Analyst\njan 2020 - dec 2020 Freelance\n\nEducation\n2010 - 2014 Bachelor\n";

		// March 2019 to December 2020: 21 months.
		$this->assertSame(expected: 1.8, actual: $this->parser->experienceYears(text: $text, today: $this->today));
	}//end testOverlappingPeriodsCountOnce()

	/**
	 * Separate periods add up, and a period in the future is left out.
	 *
	 * @return void
	 */
	public function testSeparatePeriodsAddUp(): void {
		$text = "Werkervaring\n2010 - 2012 Junior\n2015 - 2016 Medior\n2030 - 2031 Toekomst\n";

		$this->assertSame(expected: 3.0, actual: $this->parser->experienceYears(text: $text, today: $this->today));
	}//end testSeparatePeriodsAddUp()

	/**
	 * A heading without periods under it is unknown.
	 *
	 * @return void
	 */
	public function testAHeadingWithoutPeriodsIsUnknown(): void {
		$this->assertNull(actual: $this->parser->experienceYears(text: "Werkervaring\nVeel ervaring in salarisadministratie.\n", today: $this->today));
	}//end testAHeadingWithoutPeriodsIsUnknown()

	/**
	 * Competences match whole words only, with hyphens, underscores and
	 * spaces alike, and none is invented.
	 *
	 * @return void
	 */
	public function testCompetencesMatchWholeWords(): void {
		$text = 'Ervaring met loon heffing, Power BI en javascript.';

		$found = $this->parser->competences(text: $text, codes: ['loon-heffing', 'power_bi', 'java', '', 'loon-heffing']);

		$this->assertSame(expected: ['loon-heffing', 'power_bi'], actual: $found);
	}//end testCompetencesMatchWholeWords()
}//end class
