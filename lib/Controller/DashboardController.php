<?php

/**
 * Dashboard controller for DocuDesk
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-18
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

/**
 * Dashboard controller for DocuDesk
 *
 * This controller handles dashboard-related requests and views.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/docudesk
 */
class DashboardController extends Controller
{
    /**
     * Constructor for DashboardController
     *
     * @param string   $appName The application name
     * @param IRequest $request The request object
     *
     * @return void
     */
    public function __construct($appName, IRequest $request)
    {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Render the main dashboard page
     *
     * @param string|null $getParameter Optional GET parameter
     *
     * @return TemplateResponse The dashboard page template
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-18
     */
    public function page(?string $getParameter): TemplateResponse
    {
        try {
            $response = new TemplateResponse(
                $this->appName,
                'index',
                []
            );

            return $response;
        } catch (\Exception $e) {
            return new TemplateResponse(
                $this->appName,
                'error',
                ['error' => $e->getMessage()],
                'error'
            );
        }

    }//end page()

    /**
     * Serve the SPA shell for any deep link under /apps/docudesk/* so
     * vue-router (HTML5 history mode) can resolve sub-routes on a hard
     * URL refresh — otherwise NC's PHP router 404s before the SPA ever
     * mounts. Delegates to {@see page()}.
     *
     * @param string $path Deep-link path captured by the route's `{path}`
     *                     placeholder. Defaulted to empty so the same
     *                     controller method serves `/` cleanly.
     *
     * @return TemplateResponse The dashboard page template
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-18
     */
    public function catchAll(string $path=''): TemplateResponse
    {
        return $this->page(getParameter: null);

    }//end catchAll()
}//end class
