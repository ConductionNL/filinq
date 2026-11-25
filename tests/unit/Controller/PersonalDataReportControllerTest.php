<?php

/**
 * Unit tests for PersonalDataReportController
 *
 * `GET api/reports/personal-data` serves the personal data report to a
 * signed-in reader and refuses without a session (specify-capability-rows REQ-SCR-010).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\PersonalDataReportController;
use OCA\Filinq\Service\Reports\PersonalDataReport;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\PersonalDataReportController
 */
#[CoversClass(PersonalDataReportController::class)]
class PersonalDataReportControllerTest extends TestCase {

	/**
	 * The report is served with its total and its availability flags.
	 *
	 * @return void
	 */
	public function testTheReportIsServed(): void {
		$rows = [['fileId' => 11, 'name' => 'Besluit.docx', 'entityCounts' => ['BSN' => 1], 'entityTotal' => 1, 'anonymisedCopy' => false]];
		$report = $this->createMock(PersonalDataReport::class);
		$report->expects($this->once())->method('forReader')->willReturn(['results' => $rows, 'available' => true, 'truncated' => false]);

		$response = $this->controller(report: $report, uid: 'privacy')->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => $rows, 'total' => 1, 'available' => true, 'truncated' => false], $response->getData());

	}//end testTheReportIsServed()

	/**
	 * Without a session the report answers 401 and nothing is read.
	 *
	 * @return void
	 */
	public function testNobodySignedInIsRefused(): void {
		$report = $this->createMock(PersonalDataReport::class);
		$report->expects($this->never())->method('forReader');

		$response = $this->controller(report: $report, uid: null)->index();

		$this->assertSame(401, $response->getStatus());

	}//end testNobodySignedInIsRefused()

	/**
	 * The controller with this caller, or nobody.
	 *
	 * @param PersonalDataReport $report The report double.
	 * @param string|null        $uid    The caller, or null.
	 *
	 * @return PersonalDataReportController
	 */
	private function controller(PersonalDataReport $report, ?string $uid): PersonalDataReportController {
		$session = $this->createMock(IUserSession::class);
		if ($uid === null) {
			$session->method('getUser')->willReturn(null);
		} else {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$session->method('getUser')->willReturn($user);
		}

		return new PersonalDataReportController('filinq', $this->createMock(IRequest::class), $report, $session);

	}//end controller()
}//end class
