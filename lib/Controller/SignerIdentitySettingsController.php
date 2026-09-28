<?php

/**
 * Signer Identity Settings Controller
 *
 * The admin panel's endpoint for the signer identity rails.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Service\SignerAuth\OidcBrokerProvider;
use OCA\Filinq\Service\SignerAuth\SignerAuthSettings;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Reads and writes the `signer_auth_*` settings, for admins only.
 *
 * Mounted from the admin settings page (the settings framework), never from
 * the in-app router. The response carries the credential reference and no
 * secret, because there is none to carry: filinq never holds it (ADR-064).
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class SignerIdentitySettingsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest $request The request.
	 * @param SignerAuthSettings $settings The settings.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly SignerAuthSettings $settings,
	) {
		parent::__construct(appName: Application::APP_ID, request: $request);

	}//end __construct()

	/**
	 * GET /api/settings/signer-identity
	 *
	 * @return JSONResponse The settings, with the default acr mapping for reference.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function index(): JSONResponse {
		return new JSONResponse($this->payload());

	}//end index()

	/**
	 * PUT /api/settings/signer-identity
	 *
	 * @return JSONResponse The stored settings, or 400 naming the invalid field.
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function update(): JSONResponse {
		try {
			$this->settings->update(data: $this->request->getParams());
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse($this->payload());

	}//end update()

	/**
	 * The settings plus the default mapping.
	 *
	 * @return array<string, mixed>
	 */
	private function payload(): array {
		return $this->settings->toArray() + ['defaultAcrMapping' => OidcBrokerProvider::DEFAULT_ACR_MAPPING];

	}//end payload()
}//end class
