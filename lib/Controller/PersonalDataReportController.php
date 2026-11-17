<?php

/**
 * PersonalDataReportController
 *
 * Serves the personal data report (specify-capability-rows REQ-SCR-010).
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\ReadsCurrentUserId;
use OCA\Filinq\Kernel\Contracts\PersonalDataReportInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Serves `GET api/reports/personal-data`.
 *
 * @spec openspec/changes/specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
 */
class PersonalDataReportController extends Controller {
	use ReadsCurrentUserId;

	/**
	 * Constructor.
	 *
	 * @param string             $appName     App name.
	 * @param IRequest           $request     Request object.
	 * @param PersonalDataReportInterface $report      The report.
	 * @param IUserSession       $userSession The person at the keyboard.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PersonalDataReportInterface $report,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The documents the caller may read in which detection found personal
	 * data, each with its entity types and counts and whether an anonymised
	 * copy exists. Never a detected value.
	 *
	 * @return JSONResponse `{results, total, available, truncated}`, or 401 without a session.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		if ($this->currentUserId() === '') {
			return new JSONResponse(data: ['error' => 'Not authenticated'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		$report = $this->report->forReader();

		return new JSONResponse(
			data: [
				'results' => $report['results'],
				'total' => count($report['results']),
				'available' => $report['available'],
				'truncated' => $report['truncated'],
			],
			statusCode: Http::STATUS_OK
		);

	}//end index()
}//end class
