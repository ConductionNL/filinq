<?php

/**
 * Filinq RegisterDownloadAllFilesLeafListener.
 *
 * Contributes the `filinq-download-all-files` leaf to OpenRegister's cross-app
 * leaf catalogue (`RegisterLeafProvidersEvent`, ADR-066), so a handler on an
 * object in another app can download every file filinq holds for it as one
 * archive with a manifest, and is warned first when the files exceed the
 * administered ceiling.
 *
 * The JS half lives under the same id in
 * `src/integrations/registerDownloadAllFilesLeaf.js`.
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
 * Registers the download-all-files leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
class RegisterDownloadAllFilesLeafListener implements IEventListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-download-all-files';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FolderZipOutline';

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
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterLeafProvidersEvent === false) {
			return;
		}

		try {
			$descriptor = new LeafDescriptor(
				id: self::LEAF_ID,
				label: $this->l10n->t('Download all files'),
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
				'Filinq could not register the filinq-download-all-files leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

	}//end handle()
}//end class
