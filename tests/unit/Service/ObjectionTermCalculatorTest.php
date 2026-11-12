<?php

/**
 * Unit tests for ObjectionTermCalculator
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ObjectionTermCalculator;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The objection deadline of a decision letter (REQ-DLB-001).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class ObjectionTermCalculatorTest extends TestCase {

	/**
	 * Build a calculator whose app config answers the given term.
	 *
	 * @param int $weeks The configured weeks
	 *
	 * @return ObjectionTermCalculator
	 */
	private function calculator(int $weeks = ObjectionTermCalculator::DEFAULT_WEEKS): ObjectionTermCalculator {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueInt')
			->with('filinq', ObjectionTermCalculator::CONFIG_KEY, ObjectionTermCalculator::DEFAULT_WEEKS)
			->willReturn($weeks);

		return new ObjectionTermCalculator($config);

	}//end calculator()

	/**
	 * A decision on Tuesday 1 September 2026 can be objected to until 13 October.
	 *
	 * @return void
	 */
	public function testATuesdayDecisionEndsSixWeeksLater(): void {
		$term = $this->calculator()->calculate('2026-09-01');

		$this->assertSame(
			[
				'termijnWeken' => 6,
				'vanaf' => '02-09-2026',
				'uiterlijk' => '13-10-2026',
				'uiterlijkIso' => '2026-10-13',
			],
			$term
		);

	}//end testATuesdayDecisionEndsSixWeeksLater()

	/**
	 * A deadline on a Saturday moves to the Monday.
	 *
	 * @return void
	 */
	public function testASaturdayDeadlineMovesToMonday(): void {
		$this->assertSame('2026-10-19', $this->calculator()->calculate('2026-09-05')['uiterlijkIso']);

	}//end testASaturdayDeadlineMovesToMonday()

	/**
	 * A deadline on a Sunday moves to the Monday.
	 *
	 * @return void
	 */
	public function testASundayDeadlineMovesToMonday(): void {
		$this->assertSame('2026-10-19', $this->calculator()->calculate('2026-09-06T14:30:00+02:00')['uiterlijkIso']);

	}//end testASundayDeadlineMovesToMonday()

	/**
	 * A missing or unreadable date gives no term rather than a wrong one.
	 *
	 * @return void
	 */
	public function testAMissingDateGivesNoTerm(): void {
		$this->assertNull($this->calculator()->calculate(''));
		$this->assertNull($this->calculator()->calculate('not a date'));

	}//end testAMissingDateGivesNoTerm()

	/**
	 * The configured term replaces the default, and a nonsense value falls back to six.
	 *
	 * @return void
	 */
	public function testTheConfiguredTermIsUsed(): void {
		$this->assertSame('2026-09-29', $this->calculator(4)->calculate('2026-09-01')['uiterlijkIso']);
		$this->assertSame(6, $this->calculator(0)->calculate('2026-09-01')['termijnWeken']);

	}//end testTheConfiguredTermIsUsed()

	/**
	 * Ad-hoc data wins over a resolved object; a resolved object is found otherwise.
	 *
	 * @return void
	 */
	public function testTheDecisionDateIsFoundInTheData(): void {
		$calculator = $this->calculator();

		$this->assertSame('2026-09-01', $calculator->findDecisionDate(['besluitDatum' => '2026-09-01', 'zaak' => ['besluitDatum' => '2026-01-01']]));
		$this->assertSame('2026-02-03', $calculator->findDecisionDate(['zaak' => ['naam' => 'x'], 'besluit' => ['decisionDate' => '2026-02-03']]));
		$this->assertNull($calculator->findDecisionDate(['zaak' => ['naam' => 'x']]));

	}//end testTheDecisionDateIsFoundInTheData()

	/**
	 * Empty and non-string dates are skipped, at the top level and in objects; key order holds.
	 *
	 * @return void
	 */
	public function testEmptyOrNonStringDatesAreSkipped(): void {
		$calculator = $this->calculator();

		$this->assertSame('2026-03-04', $calculator->findDecisionDate(['besluitDatum' => '', 'decisionDate' => '2026-03-04']));
		$nested = ['besluitDatum' => 20260101, 'zaak' => ['besluitDatum' => '', 'decisionDate' => '2026-05-06']];
		$this->assertSame('2026-05-06', $calculator->findDecisionDate($nested));
		$ordered = ['a' => 'text', 'b' => ['besluitDatum' => null], 'c' => ['besluitDatum' => '2026-07-08', 'decisionDate' => '2026-01-01']];
		$this->assertSame('2026-07-08', $calculator->findDecisionDate($ordered));
		$this->assertNull($calculator->findDecisionDate([]));

	}//end testEmptyOrNonStringDatesAreSkipped()

	/**
	 * A Wednesday deadline stays where it is, and `vanaf` is the day after the decision.
	 *
	 * @return void
	 */
	public function testAWeekdayDeadlineIsNotMoved(): void {
		$term = $this->calculator(1)->calculate('2026-09-02 18:00');

		$expected = ['termijnWeken' => 1, 'vanaf' => '03-09-2026', 'uiterlijk' => '09-09-2026', 'uiterlijkIso' => '2026-09-09'];
		$this->assertSame($expected, $term);
		$this->assertNull($this->calculator()->calculate('   '));

	}//end testAWeekdayDeadlineIsNotMoved()

	/**
	 * The context gains `grondslag` from `base` unless it has one, and `bezwaar` from the decision date.
	 *
	 * @return void
	 */
	public function testAddToContextAddsTheBasisAndTheTerm(): void {
		$calculator = $this->calculator();

		$result = $calculator->addToContext(['base' => ['naam' => 'Wet'], 'besluitDatum' => '2026-09-01'], '{{ bezwaar.uiterlijk }}');
		$this->assertSame(['naam' => 'Wet'], $result['data']['grondslag']);
		$this->assertSame('2026-10-13', $result['data']['bezwaar']['uiterlijkIso']);
		$this->assertSame([], $result['warnings']);

		$kept = $calculator->addToContext(['grondslag' => 'eigen', 'base' => ['naam' => 'Wet']], 'geen');
		$this->assertSame(['data' => ['grondslag' => 'eigen', 'base' => ['naam' => 'Wet']], 'warnings' => []], $kept);

		$scalarBase = $calculator->addToContext(['base' => 'tekst'], '');
		$this->assertSame(['data' => ['base' => 'tekst'], 'warnings' => []], $scalarBase);

	}//end testAddToContextAddsTheBasisAndTheTerm()

	/**
	 * An existing `bezwaar` block is kept; a missing or unreadable date warns only when the template uses `bezwaar`.
	 *
	 * @return void
	 */
	public function testAddToContextWarnsOnlyWhenTheTemplateNeedsTheTerm(): void {
		$calculator = $this->calculator();

		$existing = $calculator->addToContext(['bezwaar' => 'x', 'besluitDatum' => '2026-09-01', 'base' => ['a' => 1]], 'bezwaar');
		$expected = ['bezwaar' => 'x', 'besluitDatum' => '2026-09-01', 'base' => ['a' => 1], 'grondslag' => ['a' => 1]];
		$this->assertSame(['data' => $expected, 'warnings' => []], $existing);

		$this->assertSame(['data' => [], 'warnings' => []], $calculator->addToContext([], 'Geachte heer'));

		$warning = ['No decision date found (besluitDatum or decisionDate), so the objection deadline is left out.'];
		$this->assertSame(['data' => [], 'warnings' => $warning], $calculator->addToContext([], '{{ bezwaar.uiterlijk }}'));
		$unreadable = $calculator->addToContext(['besluitDatum' => 'not a date'], 'bezwaar');
		$this->assertSame(['data' => ['besluitDatum' => 'not a date'], 'warnings' => $warning], $unreadable);

	}//end testAddToContextWarnsOnlyWhenTheTemplateNeedsTheTerm()
}//end class
