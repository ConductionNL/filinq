<?php

/**
 * Organisation Settings Controller
 *
 * The settings section of the active organisation: read for every member,
 * write for an admin of that organisation or an instance admin. The access
 * decision is the service's; this class maps its outcome to a status.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Exception\OrganisationSettingsDeniedException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\OrganisationSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads and saves the active organisation's settings.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */
class OrganisationSettingsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest                    $request  The request.
	 * @param OrganisationSettingsService $settings The organisation settings.
	 * @param LoggerInterface             $logger   Logger.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly OrganisationSettingsService $settings,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);
	}//end __construct()

	/**
	 * The active organisation's settings and whether the caller may change them.
	 *
	 * Every signed-in member may read their own organisation's settings; the
	 * read runs under OpenRegister's enforcement, so it shows no other
	 * organisation's object.
	 *
	 * @return JSONResponse The view.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		try {
			return new JSONResponse($this->settings->view());
		} catch (Throwable $e) {
			$this->logger->error('The organisation settings could not be read', ['exception' => $e->getMessage()]);
			return new JSONResponse(['error' => 'The organisation settings could not be read.'], Http::STATUS_CONFLICT);
		}
	}//end index()

	/**
	 * Save the active organisation's settings.
	 *
	 * The service refuses anyone but an admin of the organisation or an
	 * instance admin (403); a value out of range is 400.
	 *
	 * @return JSONResponse The view after the save.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
	 */
	#[NoAdminRequired]
	public function update(): JSONResponse {
		$input = [];
		foreach (['name', 'consentPeriodDays', 'anonymizationProfile', 'defaultHuisstijl', 'notes'] as $field) {
			$input[$field] = $this->request->getParam($field);
		}

		try {
			return new JSONResponse($this->settings->save(input: $input));
		} catch (OrganisationSettingsDeniedException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (Throwable $e) {
			$this->logger->error('The organisation settings could not be saved', ['exception' => $e->getMessage()]);
			return new JSONResponse(['error' => 'The organisation settings could not be saved.'], Http::STATUS_CONFLICT);
		}
	}//end update()
}//end class
