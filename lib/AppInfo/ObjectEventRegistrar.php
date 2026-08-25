<?php

/**
 * Filinq Object Event Registrar
 *
 * Wires Filinq's dashboard widgets and its OpenRegister object-lifecycle
 * listeners — both the unfiltered create/update/delete registrations made at
 * `register()` time and the register/schema-filtered dossier listener that must
 * be declared from `boot()`. Extracted from `Application` so the bootstrap class
 * no longer references every collaborator in the app.
 *
 * @category  AppInfo
 * @package   OCA\Filinq\AppInfo
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

namespace OCA\Filinq\AppInfo;

use OCA\Filinq\Dashboard\AnonymizationWidget;
use OCA\Filinq\Dashboard\FileEntitiesWidget;
use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\EventListener\EntityRelationDecisionListener;
use OCA\Filinq\EventListener\FilinqEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Registers dashboard widgets and OpenRegister object-lifecycle listeners.
 *
 * @category AppInfo
 * @package  OCA\Filinq\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class ObjectEventRegistrar {
	/**
	 * Register dashboard widgets and the unfiltered object-lifecycle listeners.
	 *
	 * Deliberately NOT narrowed to a register/schema set: FilinqEventHandler
	 * identifies its work by PAYLOAD SHAPE (`looksLikeDossier()`,
	 * `detectPolicyShape()`) rather than by schema, and EnrichmentRunner
	 * enriches metadata on EVERY object on the instance regardless of
	 * register. Declaring any slug list here would silently drop work.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 */
	public function register(IRegistrationContext $context): void {
		// Register dashboard widgets.
		$context->registerDashboardWidget(AnonymizationWidget::class);
		$context->registerDashboardWidget(FileEntitiesWidget::class);

		// Register event listeners for OpenRegister events.
		// When documents are created/updated/deleted in OpenRegister,
		// Filinq will enrich metadata and manage consent tracking.
		$context->registerEventListener(ObjectCreatedEvent::class, FilinqEventListener::class);
		$context->registerEventListener(ObjectUpdatedEvent::class, FilinqEventListener::class);
		$context->registerEventListener(ObjectDeletedEvent::class, FilinqEventListener::class);

		// REGISTERED BY STRING, NOT BY `::class`. `EntityRelationDecisionUpdatedEvent`
		// belongs to OpenRegister, which is an OPTIONAL peer — a `use` plus
		// `::class` would resolve the class at registration time and make this
		// app unbootable wherever OpenRegister is absent. The listener itself
		// checks the type by name for the same reason.
		//
		// OpenRegister has dispatched this event from EntityRelationMapper for
		// some time and nothing here subscribed to it, so every operator
		// decision to publish an entity unredacted was dropped and its consent
		// record never created (ConductionNL/filinq#805). A missing subscriber
		// produces no error anywhere: the dispatch succeeds, there is simply
		// nobody on the other end.
		$context->registerEventListener(
			'OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent',
			EntityRelationDecisionListener::class
		);

	}//end register()

	/**
	 * Declare the register/schema-filtered dossier listener.
	 *
	 * Auto-regen the dossier grondslagen summary when `checkedOn` is updated.
	 * Declared from boot() rather than register() so the OpenRegister guard is
	 * independent of this app's position in the bootstrap order.
	 *
	 * @param ContainerInterface $container The server container.
	 * @param string $appName The filinq app id, for log context.
	 *
	 * @return void
	 */
	public function boot(ContainerInterface $container, string $appName): void {
		$this->registerFilteredObjectListener(
			dispatcher: $container->get(IEventDispatcher::class),
			event: ObjectUpdatedEvent::class,
			listener: DossierCheckedOnListener::class,
			registers: ['filinq'],
			schemas: ['dossier'],
			appName: $appName
		);

	}//end boot()

	/**
	 * Register an object-lifecycle listener that declares its interest up front.
	 *
	 * OpenRegister's `ObjectEventSubscription` records the register/schema slugs
	 * a listener reacts to and routes dispatches through a single shared proxy,
	 * so an uninterested listener is neither constructed nor invoked. When
	 * OpenRegister is absent — Filinq carries no hard dependency on it — this
	 * degrades to the plain global registration it replaced, which is exactly
	 * the behaviour every listener had before.
	 *
	 * MUST be called from boot(), never from register(). Nextcloud enables each
	 * app's autoloader immediately before calling that app's own register()
	 * (`OC\AppFramework\Bootstrap\Coordinator::registerApps()`), so at register()
	 * time OpenRegister's classes are only autoloadable to apps that boot after
	 * it. Filinq is app 21 of 92 and OpenRegister is 52, so the class_exists()
	 * guard below was ALWAYS false there and this app silently fell back to an
	 * unfiltered registration — one of seven fleet conversions that looked
	 * successful while being inert. boot() runs only after every app's
	 * register() has completed, which makes the guard order-independent.
	 *
	 * @param IEventDispatcher $dispatcher The live event dispatcher.
	 * @param string $event OpenRegister event class name.
	 * @param string $listener Listener class name.
	 * @param array<int,string> $registers Register slugs the listener reacts to.
	 * @param array<int,string> $schemas Schema slugs the listener reacts to.
	 * @param string $appName The filinq app id, for log context.
	 *
	 * @return void
	 */
	private function registerFilteredObjectListener(
		IEventDispatcher $dispatcher,
		string $event,
		string $listener,
		array $registers,
		array $schemas,
		string $appName,
	): void {
		$subscription = '\\OCA\\OpenRegister\\Event\\ObjectEventSubscription';
		if (class_exists($subscription) === true) {
			$subscription::subscribe(
				dispatcher: $dispatcher,
				event: $event,
				listener: $listener,
				registers: $registers,
				schemas: $schemas
			);
			return;
		}

		// Loud on purpose. This fallback is correct but UNFILTERED, and while it
		// was silent it was indistinguishable from a working narrowing.
		Server::get(LoggerInterface::class)->warning(
			'OpenRegister ObjectEventSubscription unavailable: ' . $listener
			. ' fell back to an UNFILTERED registration for ' . $event
			. ' and will be invoked on every object write instance-wide.',
			['app' => $appName]
		);

		$dispatcher->addServiceListener($event, $listener);

	}//end registerFilteredObjectListener()
}//end class
