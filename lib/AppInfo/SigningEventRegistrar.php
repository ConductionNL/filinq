<?php

/**
 * Filinq Signing Event Registrar
 *
 * Wires the signing-related event listeners: the bridge from OpenRegister's
 * task-sequence events into Filinq's typed Signer* events, and the cross-app
 * delegated-signing request contract. Extracted from `Application`.
 *
 * @category  AppInfo
 * @package   OCA\Filinq\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\AppInfo;

use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\EventListener\SigningTaskListener;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers the task-sequence bridge and the cross-app signing-request listener.
 *
 * @category AppInfo
 * @package  OCA\Filinq\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SigningEventRegistrar {

	/**
	 * The OpenRegister task events the signing bridge consumes, as FQN
	 * string literals on purpose. `::class` on an imported name is a
	 * compile-time string too, but a literal keeps that true even if
	 * someone later adds the import — and during our own register() the
	 * `OCA\OpenRegister\` prefix is not on the autoloader yet, so neither a
	 * `class_exists()` probe (always false here) nor an eager reference
	 * (aborts register()) is an option; `BootstrapOrderIndependenceTest`
	 * pins both rules. Registering for an event class that never comes to
	 * exist is harmless: the dispatcher keys listeners by name, and the
	 * name is simply never dispatched. Mapping per openregister#3302
	 * (flow-approval-consolidation, approval-events-migration.md):
	 * transitioned-to-enabled replaces the retired step-initiated signal,
	 * committed terminality replaces step-approved and step-rejected, and
	 * sequence completion replaces chain completion.
	 *
	 * @var array<int, string>
	 */
	public const TASK_EVENTS = [
		'OCA\\OpenRegister\\Event\\TaskTransitionedEvent',
		'OCA\\OpenRegister\\Event\\TaskTerminalEvent',
		'OCA\\OpenRegister\\Event\\TaskSequenceCompletedEvent',
	];

	/**
	 * Register the signing event listeners.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function register(IRegistrationContext $context): void {
		// Bridge OR's task-sequence events into typed filinq Signer*Events
		// and invoke the configured SigningProviderInterface when a sequence
		// position becomes enabled. If the OR app is absent (degraded
		// install) or predates the task surface, the listener simply never
		// receives the events.
		foreach (self::TASK_EVENTS as $taskEvent) {
			$context->registerEventListener(event: $taskEvent, listener: SigningTaskListener::class);
		}

		// Cross-app delegated-signing contract (filinq-signing-events): any
		// installed consumer app (e.g. shillinq) dispatches
		// DocumentSigningRequestedEvent and Filinq raises the signing request
		// synchronously via SigningService::createRequest, writing the resolved
		// signingRequestId back onto the event. The in-process replacement for
		// the broken $registry->call('filinq','createSigningRequest',…) path.
		$context->registerEventListener(
			DocumentSigningRequestedEvent::class,
			DocumentSigningRequestedListener::class
		);

	}//end register()
}//end class
