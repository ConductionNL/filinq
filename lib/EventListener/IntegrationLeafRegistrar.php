<?php

/**
 * Filinq Integration Leaf Registrar.
 *
 * Subscribes the server half of Filinq's OpenRegister leaves to OpenRegister's
 * typed collect-event (ADR-066). Today that is one leaf, `filinq-documents`.
 *
 * ⚠️ THE AUTOLOADER PRELUDE IS NOT OPTIONAL, AND THE CALLER RUNS IT. Nextcloud
 * registers apps in sorted order and `filinq` sorts before `openregister`, so
 * during our own `register()` the `OCA\OpenRegister\` prefix is not on the
 * autoloader yet: a bare `class_exists()` answers FALSE on a perfectly healthy
 * instance and the leaf is skipped in silence. `RegistrationBootstrap::register()`
 * therefore calls `OpenRegisterAutoloader::register()` before
 * {@see ListenerRegistrar}, the same prelude `bindStoreController()` needs.
 *
 * The guard itself stays: on an instance whose OpenRegister predates the leaf
 * hook, or has none at all, registering a listener for a class that will never
 * exist is harmless in principle (the dispatcher keys listeners by NAME and that
 * name is simply never dispatched), and skipping it keeps the intent legible.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers Filinq's leaf descriptors with OpenRegister's collect-event.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 */
class IntegrationLeafRegistrar {

	/**
	 * OpenRegister's leaf collect-event, as an FQN string literal.
	 *
	 * A literal rather than `::class` on an import: this name is read while the
	 * OpenRegister prefix may still be unresolvable, and a literal keeps that
	 * true even if someone later adds the `use`.
	 *
	 * @var string
	 */
	public const LEAF_EVENT = 'OCA\\OpenRegister\\Event\\RegisterLeafProvidersEvent';

	/**
	 * Register the server-side half of every Filinq integration leaf.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function register(IRegistrationContext $context): void {
		if (class_exists(self::LEAF_EVENT) === false) {
			return;
		}

		$context->registerEventListener(
			event: self::LEAF_EVENT,
			listener: RegisterDocumentsLeafListener::class
		);

		$context->registerEventListener(
			event: self::LEAF_EVENT,
			listener: RegisterMergeToPdfLeafListener::class
		);

		$context->registerEventListener(
			event: self::LEAF_EVENT,
			listener: RegisterDownloadAllFilesLeafListener::class
		);

		$context->registerEventListener(
			event: self::LEAF_EVENT,
			listener: RegisterSigningFolderLeafListener::class
		);

		$context->registerEventListener(
			event: self::LEAF_EVENT,
			listener: RegisterDocumentIntakeLeafListener::class
		);

	}//end register()
}//end class
