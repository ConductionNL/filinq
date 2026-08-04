<?php

/**
 * Metrics Controller — AppHost adopter by COMPOSITION, not inheritance.
 *
 * The `metrics#index` route resolves to this concrete DocuDesk class, which
 * owns the admin-only auth posture (no #[NoAdminRequired]) and drives the
 * OpenRegister AppHost metrics engine — pulled out of the DI container BY FQCN
 * STRING at dispatch time rather than inherited. The declarative
 * `observability.metrics` block in src/manifest.json still drives the
 * Prometheus 0.0.4 exposition; the previous hand-written metric lines and
 * MetricsCollector remain gone.
 *
 * ⚠️ DO NOT "simplify" this back into a subclass of the AppHost generic, and do
 * not `use`-import an OpenRegister class here. Nextcloud's router
 * `ReflectionClass()`es every file in `lib/Controller/` while MATCHING a route,
 * so an unresolvable parent makes EVERY route in DocuDesk return HTTP 500, not
 * just this one. `extends` is resolved by the AUTOLOADER, not the container, so
 * lazy DI cannot rescue it. See decidesk#377 / #388.
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

use OCA\DocuDesk\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IRequest;
use Psr\Container\ContainerInterface;

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
class MetricsController extends Controller
{

    /**
     * FQCN of the AppHost observability manifest loader.
     *
     * Referenced as a string, never imported: the class only exists when
     * openregister is installed, and an import in a resolved position would
     * 500 every DocuDesk route when it is not.
     *
     * @var string
     */
    private const MANIFEST_LOADER = 'OCA\\OpenRegister\\AppHost\\Observability\\ManifestLoader';

    /**
     * FQCN of the AppHost Prometheus metrics engine.
     *
     * Referenced as a string, never imported: see {@see self::MANIFEST_LOADER}.
     *
     * @var string
     */
    private const METRICS_ENGINE = 'OCA\\OpenRegister\\AppHost\\Observability\\MetricsEngine';

    /**
     * Prometheus text exposition content type.
     *
     * Mirrors the engine's `PrometheusRenderer::CONTENT_TYPE`, copied as a
     * literal because a foreign class CONSTANT is also a class reference.
     *
     * @var string
     */
    private const CONTENT_TYPE = 'text/plain; version=0.0.4; charset=utf-8';

    /**
     * Constructor.
     *
     * @param IRequest           $request   The request object.
     * @param ContainerInterface $container DI container — resolves the AppHost engine lazily.
     *
     * @return void
     */
    public function __construct(
        IRequest $request,
        private readonly ContainerInterface $container,
    ) {
        parent::__construct(appName: Application::APP_ID, request: $request);

    }//end __construct()

    /**
     * GET /api/metrics — declarative Prometheus metrics (admin-only, ADR-006).
     *
     * Drives the AppHost metrics engine. Returns HTTP 503 with a Prometheus
     * comment line when the engine is unavailable (openregister absent or
     * disabled) — never a 500.
     *
     * @return TextPlainResponse Prometheus text exposition 0.0.4.
     *
     * @spec openspec/specs/adopt-apphost/spec.md
     */
    #[NoCSRFRequired]
    public function index(): TextPlainResponse
    {
        try {
            $manifestLoader = $this->container->get(self::MANIFEST_LOADER);
            $engine         = $this->container->get(self::METRICS_ENGINE);

            $manifest = $manifestLoader->load(appId: $this->appName);
            $body     = (string) $engine->render(manifest: $manifest);
            $status   = Http::STATUS_OK;
        } catch (\Throwable $e) {
            $body   = '# metrics unavailable: the OpenRegister AppHost observability engine is not installed'."\n";
            $status = Http::STATUS_SERVICE_UNAVAILABLE;
        }//end try

        $response = new TextPlainResponse($body, $status);
        $response->addHeader('Content-Type', self::CONTENT_TYPE);

        return $response;

    }//end index()
}//end class
