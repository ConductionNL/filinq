<?php

/**
 * Health Controller — AppHost adopter by COMPOSITION, not inheritance.
 *
 * The `health#index` route resolves to this concrete DocuDesk class carrying
 * the ADR-006 auth posture (#[PublicPage]). The declarative
 * `observability.health` checks from src/manifest.json are still executed by
 * OpenRegister's AppHost engine — but the engine collaborators are pulled out
 * of the DI container BY FQCN STRING at dispatch time rather than inherited,
 * and the published `{status, app, version, checks}` body shape is rendered
 * here. The hand-written database + openregister checks remain gone (declared
 * in the manifest instead).
 *
 * ⚠️ DO NOT "simplify" this back into a subclass of the AppHost generic, and do
 * not `use`-import an OpenRegister class here. Nextcloud's router
 * `ReflectionClass()`es every file in `lib/Controller/` while MATCHING a route,
 * so an unresolvable parent makes EVERY route in DocuDesk return HTTP 500, not
 * just this one. DocuDesk does not declare `<app>openregister</app>`, so an
 * admin can create exactly that configuration. `extends` is resolved by the
 * AUTOLOADER, not the container, so lazy DI cannot rescue it.
 * See decidesk#377 / #388.
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
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Container\ContainerInterface;

/**
 * Public, declarative health endpoint backed by the AppHost engine.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class HealthController extends Controller {

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
	 * FQCN of the AppHost declarative health-check executor.
	 *
	 * Referenced as a string, never imported: see {@see self::MANIFEST_LOADER}.
	 *
	 * @var string
	 */
	private const HEALTH_EXECUTOR = 'OCA\\OpenRegister\\AppHost\\Observability\\HealthCheckExecutor';

	/**
	 * Constructor.
	 *
	 * @param IRequest $request The request object.
	 * @param IConfig $config The Nextcloud config service (app version fallback).
	 * @param ContainerInterface $container DI container — resolves the AppHost engine lazily.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly IConfig $config,
		private readonly ContainerInterface $container,
	) {
		parent::__construct(appName: Application::APP_ID, request: $request);

	}//end __construct()

	/**
	 * GET /api/health — declarative health check (ADR-006).
	 *
	 * Public per ADR-006: an anonymous probe (load balancer, uptime monitor)
	 * must reach this without a session. Drives the AppHost engine, which runs
	 * the `observability.health` checks declared in src/manifest.json.
	 *
	 * When the engine cannot be resolved — openregister absent or disabled —
	 * the endpoint still ANSWERS (the entire point of a health probe):
	 * `status: degraded`, `checks: {openregister: unavailable}`, HTTP 200.
	 *
	 * @return JSONResponse `{status, app, version, checks}`.
	 *
	 * @spec openspec/specs/adopt-apphost/spec.md
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function index(): JSONResponse {
		$body = $this->engineBody();
		if ($body === null) {
			$body = [
				'status' => 'degraded',
				'version' => $this->config->getAppValue(Application::APP_ID, 'installed_version', ''),
				'checks' => ['openregister' => 'unavailable'],
				'cors' => false,
				'httpStatus' => Http::STATUS_OK,
			];
		}

		$response = new JSONResponse(
			[
				'status' => $body['status'],
				'app' => $this->appName,
				'version' => $body['version'],
				'checks' => $body['checks'],
			],
			(int)$body['httpStatus']
		);

		if ($body['cors'] === true) {
			$response->addHeader('Access-Control-Allow-Origin', '*');
			$response->addHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
		}

		return $response;
	}//end index()

	/**
	 * Run the AppHost observability engine and collect its result.
	 *
	 * Returns null when the engine is unavailable (openregister absent/disabled).
	 *
	 * @return array{status: string, version: string, checks: array<string, mixed>, cors: bool, httpStatus: int}|null
	 */
	private function engineBody(): ?array {
		try {
			$manifestLoader = $this->container->get(self::MANIFEST_LOADER);
			$executor = $this->container->get(self::HEALTH_EXECUTOR);

			$appId = $this->appName;
			$manifest = $manifestLoader->load(appId: $appId);
			$result = $executor->execute(manifest: $manifest);

			return [
				'status' => (string)$result->status,
				'version' => (string)$manifestLoader->appVersion(appId: $appId),
				'checks' => (array)$result->checks,
				'cors' => ($manifest->cors === true),
				'httpStatus' => (int)$result->httpStatusCode,
			];
		} catch (\Throwable $e) {
			return null;
		}//end try

	}//end engineBody()
}//end class
