<?php

/**
 * Unit tests for CheckedGateModeRule and CheckedGateReport.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Controller\Support\CheckedGateReport;
use OCA\Filinq\Service\Review\CheckedGateModeRule;
use OCP\AppFramework\Http\JSONResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The gate-mode setting rule and the advisory report helper.
 *
 * @covers \OCA\Filinq\Service\Review\CheckedGateModeRule
 * @covers \OCA\Filinq\Controller\Support\CheckedGateReport
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CheckedGateModeRuleTest extends TestCase {

	/**
	 * Both modes and a save without the key pass.
	 *
	 * @return void
	 */
	public function testTheTwoModesPass(): void {
		$rule = new CheckedGateModeRule();
		$rule->assertValid(data: ['filinq.review.checked_gate' => 'enforced']);
		$rule->assertValid(data: ['filinq.review.checked_gate' => 'advisory']);
		$rule->assertValid(data: ['signing_provider' => 'native']);
		$this->addToAssertionCount(3);
	}//end testTheTwoModesPass()

	/**
	 * Anything else is refused with 400, a non-string too.
	 *
	 * @return void
	 */
	public function testAnythingElseIsRefused(): void {
		foreach (['off', '', 1, null] as $value) {
			try {
				(new CheckedGateModeRule())->assertValid(data: ['filinq.review.checked_gate' => $value]);
				$this->fail('accepted '.var_export($value, true));
			} catch (RuntimeException $e) {
				$this->assertSame(400, $e->getCode());
			}
		}
	}//end testAnythingElseIsRefused()

	/**
	 * The report goes on a successful array answer only.
	 *
	 * @return void
	 */
	public function testTheReportGoesOnASuccessfulAnswerOnly(): void {
		$report = new CheckedGateReport();
		$gate = ['refusal' => null, 'report' => ['passed' => false, 'uncheckedFiles' => [['fileId' => 1, 'fileName' => '']]]];

		$this->assertSame(['ok' => 1, 'checkedGate' => $gate['report']], $report->onto(response: new JSONResponse(['ok' => 1]), gate: $gate)->getData());
		$this->assertSame(['ok' => 1], $report->onto(response: new JSONResponse(['ok' => 1], 422), gate: $gate)->getData());
		$this->assertSame(['ok' => 1], $report->onto(response: new JSONResponse(['ok' => 1]), gate: null)->getData());
		$this->assertSame(['ok' => 1], $report->onto(response: new JSONResponse(['ok' => 1]), gate: ['refusal' => null, 'report' => null])->getData());
		$this->assertSame(['ok' => 1, 'checkedGate' => $gate['report']], $report->ontoArray(result: ['ok' => 1], gate: $gate));
		$this->assertSame(['ok' => 1], $report->ontoArray(result: ['ok' => 1], gate: null));
	}//end testTheReportGoesOnASuccessfulAnswerOnly()
}//end class
