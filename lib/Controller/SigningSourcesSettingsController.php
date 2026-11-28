<?php

/**
 * Signing Sources Settings Controller
 *
 * The admin panel's list of the apps that raise signing requests.
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
 * @spec openspec/changes/archive/2026-11-26-one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\SigningSourceTally;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads, for admins only, which apps use the signing rail (REQ-DDSRC-004).
 *
 * Mounted from the admin settings page, never from the in-app router. The
 * answer names apps and counts, never a request or its subject.
 *
 * @spec openspec/changes/archive/2026-11-26-one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */
class SigningSourcesSettingsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest           $request The request.
	 * @param SigningSourceTally $tally   Counts the requests per source app.
	 * @param LoggerInterface    $logger  Logs a count that failed.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly SigningSourceTally $tally,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * GET /api/settings/signing-sources
	 *
	 * @return JSONResponse `{sources: [{app, count}], total, truncated}`, or 500 when the requests cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-11-26-one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function index(): JSONResponse {
		try {
			return new JSONResponse($this->tally->bySourceApp());
		} catch (Throwable $e) {
			$this->logger->error(message: '[SigningSourcesSettingsController] counting signing requests failed', context: ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'The signing requests could not be counted.'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end index()
}//end class
