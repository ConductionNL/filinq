<?php

/**
 * DocuDesk Signing Event Registrar
 *
 * Wires the signing-related event listeners: the bridge from OpenRegister's
 * ApprovalStep events into DocuDesk's typed Signer* events, and the cross-app
 * delegated-signing request contract. Extracted from `Application`.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use OCA\DocuDesk\Event\DocumentSigningRequestedEvent;
use OCA\DocuDesk\EventListener\ApprovalStepListener;
use OCA\DocuDesk\EventListener\DocumentSigningRequestedListener;
use OCA\OpenRegister\Event\ApprovalStepApprovedEvent;
use OCA\OpenRegister\Event\ApprovalStepCompletedEvent;
use OCA\OpenRegister\Event\ApprovalStepInitiatedEvent;
use OCA\OpenRegister\Event\ApprovalStepRejectedEvent;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers the approval-step bridge and the cross-app signing-request listener.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SigningEventRegistrar {
	/**
	 * Register the signing event listeners.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function register(IRegistrationContext $context): void {
		// Bridge OR ApprovalStep events into typed docudesk Signer*Events
		// and invoke the configured SigningProviderInterface when a step
		// becomes pending. Per migrate-signing-to-or-approval-workflow
		// (D2.1) — OR's `add-approval-step-events` shipped upstream as of
		// 2026-06-12 so the four event classes referenced below resolve at
		// runtime; if the OR app is absent (degraded install) the listener
		// simply never receives the events.
		$context->registerEventListener(ApprovalStepInitiatedEvent::class, ApprovalStepListener::class);
		$context->registerEventListener(ApprovalStepApprovedEvent::class, ApprovalStepListener::class);
		$context->registerEventListener(ApprovalStepRejectedEvent::class, ApprovalStepListener::class);
		$context->registerEventListener(ApprovalStepCompletedEvent::class, ApprovalStepListener::class);

		// Cross-app delegated-signing contract (docudesk-signing-events): any
		// installed consumer app (e.g. shillinq) dispatches
		// DocumentSigningRequestedEvent and DocuDesk raises the signing request
		// synchronously via SigningService::createRequest, writing the resolved
		// signingRequestId back onto the event. The in-process replacement for
		// the broken $registry->call('docudesk','createSigningRequest',…) path.
		$context->registerEventListener(
			DocumentSigningRequestedEvent::class,
			DocumentSigningRequestedListener::class
		);

	}//end register()
}//end class
