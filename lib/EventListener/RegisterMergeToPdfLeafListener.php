<?php

/**
 * Filinq RegisterMergeToPdfLeafListener.
 *
 * Contributes the `filinq-merge-to-pdf` leaf to OpenRegister's cross-app leaf
 * catalogue (`RegisterLeafProvidersEvent`, ADR-066), so a handler on a case in
 * another app can pick that case's documents, put them in order and get one
 * PDF back in the case folder.
 *
 * A RENDER SURFACE, NOT A BULK ACTION. The shared integration registry offers
 * the surfaces user-dashboard, app-dashboard, detail-page and single-entity,
 * and no bulk-action slot on a host's file list. The leaf therefore carries its
 * own selection and dialog. It invokes nothing in the host app: the result
 * reaches the host as a file beside the first document (ADR-066 decision 2).
 *
 * The JS half lives under the same id in
 * `src/integrations/registerMergeToPdfLeaf.js`.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
 * Registers the merge-to-PDF leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class RegisterMergeToPdfLeafListener implements IEventListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-merge-to-pdf';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FilePdfBox';

	/**
	 * The surfaces, written out so both halves can be compared.
	 *
	 * @var string[]
	 */
	public const SURFACES = [
		'detail-page',
		'single-entity',
	];

	/**
	 * Constructor.
	 *
	 * @param IL10N           $l10n   The translator for the label.
	 * @param LoggerInterface $logger Where a failed registration is reported.
	 */
	public function __construct(
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Add the leaf to the catalogue.
	 *
	 * @param Event $event The collect-event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterLeafProvidersEvent === false) {
			return;
		}

		try {
			$descriptor = new LeafDescriptor(
				id: self::LEAF_ID,
				label: $this->l10n->t('Merge to PDF'),
				icon: self::ICON,
				kinds: [LeafDescriptor::KIND_RENDER_SURFACE],
				requiredApp: Application::APP_ID,
				group: 'documents',
				surfaces: self::SURFACES,
				referenceType: self::LEAF_ID,
				renderMode: LeafDescriptor::RENDER_MODE_MOUNT,
			);

			$event->registerLeaf($descriptor, null);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq could not register the filinq-merge-to-pdf leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

	}//end handle()
}//end class
