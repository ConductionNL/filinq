<?php

/**
 * Unit tests for IntakeOverdueList.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Service\Intake\IntakeOverdueList;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use PHPUnit\Framework\TestCase;

/**
 * The overdue list is read for today, under the rules' meaning of handled.
 *
 * @covers \OCA\Filinq\Service\Intake\IntakeOverdueList
 */
class IntakeOverdueListTest extends TestCase {

	/**
	 * Today in UTC and each rule's handledWhen reach the repository, and its answer is the list.
	 *
	 * @return void
	 */
	public function testTheListIsReadForTodayUnderTheRules(): void {
		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->method('handledWhenByRule')->willReturn(['r-1' => 'answered', 'r-2' => 'assigned']);

		$repository = $this->createMock(IntakeRepositoryInterface::class);
		$repository->expects($this->once())->method('findOverdue')
			->with(gmdate('Y-m-d'), ['r-1' => 'answered', 'r-2' => 'assigned'])
			->willReturn([['uuid' => 'late']]);

		self::assertSame([['uuid' => 'late']], (new IntakeOverdueList($repository, $rules))->list());
	}//end testTheListIsReadForTodayUnderTheRules()
}//end class
