<?php

/**
 * Health Controller — AppHost delegator.
 *
 * Thin subclass of OpenRegister's engine-owned GenericHealthController. It
 * exists only so the `health#index` route resolves to a concrete DocuDesk
 * class carrying the ADR-006 auth posture (#[PublicPage]); all behaviour —
 * running the declarative `observability.health` checks from src/manifest.json
 * and rendering `{status, app, version, checks}` — lives in the engine. The
 * previous hand-written database + openregister checks are gone (declared in
 * the manifest instead).
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\OpenRegister\AppHost\Controller\GenericHealthController;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Public, declarative health endpoint backed by the AppHost engine.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class HealthController extends GenericHealthController
{
    /**
     * GET /api/health — declarative health check (ADR-006).
     *
     * Public per ADR-006: an anonymous probe (load balancer, uptime monitor)
     * must reach this without a session. Delegates entirely to the engine.
     *
     * @return JSONResponse `{status, app, version, checks}`.
     *
     * @spec openspec/specs/adopt-apphost/spec.md
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function index(): JSONResponse
    {
        return parent::index();

    }//end index()
}//end class
