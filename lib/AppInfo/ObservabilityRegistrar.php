<?php

/**
 * DocuDesk Observability Registrar
 *
 * Wires the AppHost metrics engine into DocuDesk's container. Extracted from
 * `Application`.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
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

namespace OCA\DocuDesk\AppInfo;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;

/**
 * Registers the AppHost MetricsEngine under its OpenRegister FQCN.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */
class ObservabilityRegistrar {
	/**
	 * Wire the AppHost metrics engine.
	 *
	 * DocuDesk's HealthController / MetricsController do NOT subclass the
	 * OpenRegister generics and do NOT take engine collaborators as constructor
	 * parameters — they resolve them out of the container BY FQCN STRING at
	 * dispatch time and degrade (health `degraded` / metrics 503) when the
	 * lookup fails. Both auto-wire from OCP alone, so neither is registered
	 * here.
	 *
	 * ⚠️ Do NOT re-introduce a `registerService(HealthController::class, …)` /
	 * `registerService(MetricsController::class, …)` pair that passes engine
	 * collaborators, and do NOT turn those controllers back into subclasses of
	 * the OpenRegister generics. Nextcloud's router `ReflectionClass()`es every
	 * file in lib/Controller/ while MATCHING a route, so one unresolvable parent
	 * returns HTTP 500 for EVERY docudesk route — and DocuDesk does not declare
	 * `<app>openregister</app>`, so an admin can create exactly that
	 * configuration. `extends` is resolved by the AUTOLOADER, not this
	 * container, so lazy registration cannot rescue it. See docudesk#369 /
	 * decidesk#377.
	 *
	 * MetricsEngine still needs an explicit factory: OpenRegister's own
	 * MetricsEngine factory is registered under the `openregister` app container
	 * and is not visible here, and auto-wiring it fresh would fail on the
	 * multi-arg constructor. Registering it under its own FQCN string keeps that
	 * explicit construction while letting MetricsController find it with a plain
	 * `$container->get()`.
	 *
	 * ⚠️ The closure body is the ONLY place the OpenRegister name is resolved,
	 * and a closure body runs on `get()`, never at registration. The closure
	 * therefore declares `object` as its return type, not the engine's own type.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/adopt-apphost/spec.md
	 */
	public function register(IRegistrationContext $context): void {
		$engineFactory = new MetricsEngineFactory();

		$context->registerService(
			'OCA\\OpenRegister\\AppHost\\Observability\\MetricsEngine',
			static function (ContainerInterface $container) use ($engineFactory): object {
				return $engineFactory->build(container: $container);
			}
		);

	}//end register()
}//end class
