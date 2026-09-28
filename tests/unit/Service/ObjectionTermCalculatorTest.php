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
}//end class
