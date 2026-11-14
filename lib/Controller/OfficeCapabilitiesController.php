<?php

/**
 * Filinq OfficeCapabilitiesController
 *
 * Exposes whether an office suite's WOPI host is usable on this instance, so the
 * frontend can degrade a suite-backed affordance visibly (ADR-075 §4) instead of
 * showing a control that fails in a user's hands.
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
 * @spec openspec/changes/office-suite-portability
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\OfficeSuiteAvailabilityServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Answers which office suites have a usable WOPI host.
 *
 * Only booleans leave this endpoint. The probe's reasons can carry internal
 * host names and transport errors, so they stay with the operator through
 * `occ filinq:office:probe`.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-suite-portability
 */
class OfficeCapabilitiesController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                                  $appName      The app name.
	 * @param IRequest                                $request      The request.
	 * @param OfficeSuiteAvailabilityServiceInterface $availability The cached per-suite probe.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly OfficeSuiteAvailabilityServiceInterface $availability,
	) {
		parent::__construct(appName: $appName, request: $request);
	}//end __construct()

	/**
	 * Whether WOPI is usable, overall and per suite.
	 *
	 * @return JSONResponse `{ available: bool, suites: { <name>: { available: bool, configured: bool } } }`
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/changes/office-suite-portability
	 */
	public function show(): JSONResponse {
		$verdict = $this->availability->resolve();

		$suites = [];
		foreach ($verdict['suites'] as $name => $suite) {
			$suites[$name] = [
				'available' => $suite['available'] === true,
				'configured' => $suite['configured'] === true,
			];
		}

		return new JSONResponse(['available' => $verdict['available'] === true, 'suites' => $suites]);
	}//end show()
}//end class
