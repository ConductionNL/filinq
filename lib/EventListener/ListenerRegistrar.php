<?php

/**
 * Filinq Listener Registrar
 *
 * The EventListener component's own registration: the object-event,
 * signing, integration-leaf and document-generation registrars, run in that
 * order. The AppInfo bootstrap calls this one class instead of naming each
 * listener.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
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

namespace OCA\Filinq\EventListener;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;

/**
 * Registers every Filinq event listener.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Container wiring, no functional requirement.
 */
class ListenerRegistrar {
	/**
	 * Register the listeners. The OpenRegister autoloader prelude must have run.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec exclude Container wiring, no functional requirement.
	 */
	public function register(IRegistrationContext $context): void {
		(new ObjectEventRegistrar())->register(context: $context);
		(new SigningEventRegistrar())->register(context: $context);
		(new IntegrationLeafRegistrar())->register(context: $context);
		(new DocumentGenerationRegistrar())->register(context: $context);

	}//end register()

	/**
	 * Run the boot-time listener step.
	 *
	 * @param ContainerInterface $container The server container.
	 * @param string $appName The filinq app id.
	 *
	 * @return void
	 *
	 * @spec exclude Container wiring, no functional requirement.
	 */
	public function boot(ContainerInterface $container, string $appName): void {
		(new ObjectEventRegistrar())->boot(container: $container, appName: $appName);

	}//end boot()
}//end class
