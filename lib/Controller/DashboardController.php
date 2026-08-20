<?php

/**
 * DocuDesk Dashboard Controller.
 *
 * SPA host: renders the SPA from `templates/index.php` and serves the Vue
 * history-mode catch-all. Behaviourally identical to the OpenRegister AppHost
 * `GenericDashboardController` it used to subclass, but implemented locally and
 * depending on nothing outside DocuDesk and OCP. The conventional
 * `dashboard#page` route resolves to this concrete
 * `OCA\DocuDesk\Controller\DashboardController`, making the route name
 * `docudesk.dashboard.page` that the navigation (info.xml) and dashboard widgets
 * link to resolvable.
 *
 * ⚠️ DO NOT "simplify" this back into a subclass of the AppHost generic.
 * Nextcloud's router `ReflectionClass()`es every file in `lib/Controller/` while
 * MATCHING a route, so an unresolvable parent makes EVERY route in DocuDesk
 * return HTTP 500 — including routes with no OpenRegister involvement at all.
 * DocuDesk does not declare `<app>openregister</app>`, so an admin can create
 * exactly that configuration. `extends` is resolved by the AUTOLOADER, not the
 * DI container, so no amount of lazy registration can rescue it, and the few
 * lines below are cheaper than a whole-app outage. See decidesk#377 / #388.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://docudesk.conduction.nl
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

/**
 * Controller for the main DocuDesk SPA page.
 *
 * @psalm-suppress UnusedClass
 */
class DashboardController extends Controller {
	/**
	 * Constructor.
	 *
	 * Supplies the docudesk app id so Nextcloud's DI can auto-wire this
	 * controller from `IRequest` alone.
	 *
	 * @param IRequest $request HTTP request.
	 */
	public function __construct(IRequest $request) {
		parent::__construct(appName: Application::APP_ID, request: $request);

	}//end __construct()

	/**
	 * Render the main SPA page from `templates/index.php`.
	 *
	 * `#[NoAdminRequired]` / `#[NoCSRFRequired]` were previously INHERITED from
	 * the AppHost generic; they are declared explicitly here so the auth posture
	 * is byte-for-byte unchanged by dropping the inheritance.
	 *
	 * @return TemplateResponse The rendered DocuDesk index template.
	 *
	 * @spec openspec/specs/adopt-apphost/spec.md
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function page(): TemplateResponse {
		return $this->renderIndex();
	}//end page()

	/**
	 * Serve the SPA for deep links (Vue history mode). Delegates to {@see page()}.
	 *
	 * @return TemplateResponse The rendered DocuDesk index template.
	 *
	 * @spec openspec/specs/adopt-apphost/spec.md
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function catchAll(): TemplateResponse {
		return $this->page();
	}//end catchAll()

	/**
	 * Build the `index` TemplateResponse.
	 *
	 * @return TemplateResponse The rendered DocuDesk index template.
	 */
	protected function renderIndex(): TemplateResponse {
		return new TemplateResponse($this->appName, 'index');
	}//end renderIndex()
}//end class
