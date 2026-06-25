<?php

/**
 * DocuDesk Dashboard Controller.
 *
 * Thin subclass of the OpenRegister AppHost GenericDashboardController. The SPA
 * shell (`page()` / `catchAll()`) is inherited unchanged from the engine; this
 * subclass exists so the conventional `dashboard#page` route resolves to a real
 * `OCA\DocuDesk\Controller\DashboardController` class (auto-wired by Nextcloud
 * DI from `IRequest` alone), making the route name `docudesk.dashboard.page`
 * that the navigation (info.xml) and dashboard widgets link to resolvable.
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
use OCA\OpenRegister\AppHost\Controller\GenericDashboardController;
use OCP\IRequest;

/**
 * Controller for the main DocuDesk SPA page.
 *
 * @psalm-suppress UnusedClass
 */
class DashboardController extends GenericDashboardController
{
    /**
     * Constructor.
     *
     * Supplies the docudesk app id to the engine base controller so Nextcloud's
     * DI can auto-wire this subclass from `IRequest` alone.
     *
     * @param IRequest $request HTTP request.
     */
    public function __construct(IRequest $request)
    {
        parent::__construct(appName: Application::APP_ID, request: $request);

    }//end __construct()
}//end class
