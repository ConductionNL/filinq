<?php

/**
 * Filinq Document Generation Registrar
 *
 * Wires Filinq's published document-generation contract: the
 * `filinq.generate-document` node on OpenRegister's flow catalogue, and the
 * DocumentGenerationRequestedEvent command other apps dispatch. Called from
 * RegistrationBootstrap::register(), which Application::register() delegates
 * to.
 *
 * @category  AppInfo
 * @package   OCA\Filinq\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\AppInfo;

use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\EventListener\DocumentGenerationRequestedListener;
use OCA\Filinq\EventListener\DocumentStampRequestedListener;
use OCA\Filinq\Flow\FilinqFlowNodeListener;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers the flow-node listener and the generation command listener.
 *
 * @category AppInfo
 * @package  OCA\Filinq\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
 */
class DocumentGenerationRegistrar {

	/**
	 * OpenRegister's node-registration event, as a string literal on purpose.
	 *
	 * During Filinq's own register() the `OCA\OpenRegister\` prefix is not on
	 * the autoloader yet, so the class cannot be probed here and must not be
	 * resolved. It does not need to be: the dispatcher keys listeners by name,
	 * so with OpenRegister absent the name is simply never dispatched and
	 * Filinq boots exactly as before.
	 *
	 * @var string
	 */
	public const FLOW_NODES_EVENT = 'OCA\\OpenRegister\\Service\\Flow\\RegisterFlowNodesEvent';

	/**
	 * Register the listeners.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-filinq-registers-its-node-and-still-boots-without-openregister
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(
			event: self::FLOW_NODES_EVENT,
			listener: FilinqFlowNodeListener::class
		);

		$context->registerEventListener(
			event: DocumentGenerationRequestedEvent::class,
			listener: DocumentGenerationRequestedListener::class
		);

		// A sibling app asks filinq to stamp a text on every page of a PDF
		// (work-stamp-text-on-every-page, REQ-PST-002).
		$context->registerEventListener(
			event: DocumentStampRequestedEvent::class,
			listener: DocumentStampRequestedListener::class
		);

	}//end register()
}//end class
