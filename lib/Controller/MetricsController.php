<?php

/**
 * Metrics Controller — AppHost delegator.
 *
 * Thin subclass of OpenRegister's engine-owned GenericMetricsController. It
 * exists only so the `metrics#index` route resolves to a concrete DocuDesk
 * class; the admin-only auth posture (no #[NoAdminRequired]) and the Prometheus
 * 0.0.4 exposition are owned by the engine. The declarative
 * `observability.metrics` block in src/manifest.json drives the output —
 * the previous hand-written metric lines and MetricsCollector are gone.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/adopt-apphost/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\OpenRegister\AppHost\Controller\GenericMetricsController;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TextPlainResponse;

/**
 * Admin-only declarative Prometheus metrics endpoint backed by the AppHost engine.
 *
 * No #[NoAdminRequired]: the absence means Nextcloud requires an admin session,
 * the intended ADR-006 posture. Anonymous callers get 401, never metric data.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class MetricsController extends GenericMetricsController
{
    /**
     * GET /api/metrics — declarative Prometheus metrics (admin-only, ADR-006).
     *
     * Delegates entirely to the engine.
     *
     * @return TextPlainResponse Prometheus text exposition 0.0.4.
     *
     * @spec openspec/changes/adopt-apphost/tasks.md
     */
    #[NoCSRFRequired]
    public function index(): TextPlainResponse
    {
        return parent::index();

    }//end index()
}//end class
