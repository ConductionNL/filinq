<?php

/**
 * Filinq RegisterDocumentsLeafListener.
 *
 * Contributes the `filinq-documents` leaf to OpenRegister's cross-app leaf
 * catalogue (`RegisterLeafProvidersEvent`, ADR-066), so the documents Filinq
 * holds for an object are visible on that object wherever it is shown, and not
 * only on Filinq's own pages.
 *
 * WHY THIS EXISTS. Filinq declared no leaf at all. Its flat case-document list
 * has always been reachable at `GET /api/case-documents/files?register=&schema=
 * &id=`, which takes exactly the object context a leaf is handed, and nothing
 * outside Filinq could reach it. Declaring the schemas as leaf HOSTS
 * (`configuration.linkedTypes`, this change's other half) makes other apps'
 * leaves land on Filinq records; this listener is the opposite direction.
 *
 * RENDER-AND-READ ONLY. The descriptor carries no verb and no provider: the
 * leaf reads the list through Filinq's own authenticated route, under the same
 * access control that route already applies. It is a render surface, so it
 * passes `null` where an `IntegrationProvider` would go.
 *
 * BOTH HALVES OR NEITHER. The render half lives in Filinq's own JS under the
 * SAME id (`src/integrations/registerDocumentsLeaf.js`, shipped as the
 * `filinq-leaves` bundle). `SURFACES` below is written out verbatim rather than
 * left to the host's default, because a half that declares its surfaces by
 * omission is how two registrations of one leaf drift apart without any gate
 * noticing.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\AppInfo\Application;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Contributes the `filinq-documents` render leaf to OpenRegister.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
class RegisterDocumentsLeafListener implements IEventListener {

	/**
	 * The shared leaf id, equal to the JS `register()` id.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-documents';

	/**
	 * The render surfaces this leaf targets — the SAME set, in the same order,
	 * as `src/integrations/registerDocumentsLeaf.js` declares.
	 *
	 * Both dashboard surfaces are deliberately absent: the leaf renders the
	 * documents of ONE object and has nothing to say without one, so offering
	 * it as a dashboard widget would advertise a surface that can only render
	 * empty.
	 *
	 * @var array<int, string>
	 */
	public const SURFACES = [
		'detail-page',
		'single-entity',
	];

	/**
	 * Constructor.
	 *
	 * @param IL10N           $l10n   Localisation for the human-readable label.
	 * @param LoggerInterface $logger PSR-3 logger; a throwing listener costs its own leaf only.
	 */
	public function __construct(
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Contribute the `filinq-documents` leaf descriptor.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/tasks.md#task-2-1
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterLeafProvidersEvent === false) {
			return;
		}

		try {
			$descriptor = new LeafDescriptor(
				id: self::LEAF_ID,
				label: $this->l10n->t('Documents'),
				icon: 'FileDocumentMultipleOutline',
				kinds: [LeafDescriptor::KIND_RENDER_SURFACE],
				requiredApp: Application::APP_ID,
				group: 'documents',
				surfaces: self::SURFACES,
				referenceType: self::LEAF_ID,
				// Filinq is Vue 3 and a consuming host may still be Vue 2.7, so
				// the JS half hands over a bare DOM element and roots its own
				// app there. The server half MUST name the same mode under the
				// shared id or the host renders the leaf the wrong way.
				renderMode: LeafDescriptor::RENDER_MODE_MOUNT,
			);

			$event->registerLeaf($descriptor, null);
		} catch (Throwable $e) {
			// Never take the whole leaf catalogue down: log, and lose this leaf.
			$this->logger->warning(
				'Filinq could not register the filinq-documents leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

	}//end handle()
}//end class
