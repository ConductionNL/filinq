<?php

/**
 * Filinq RegisterSigningFolderLeafListener.
 *
 * Contributes the `filinq-signing-folder` leaf to OpenRegister's cross-app
 * leaf catalogue (`RegisterLeafProvidersEvent`, ADR-066), so dossiq, decidiq
 * and any other app place the same signing folder on a dashboard: everything
 * still waiting for the signer's signature, across every record. The folder is
 * per signer, not per record, so it targets the dashboard surfaces only.
 *
 * The JS half lives under the same id in
 * `src/integrations/registerSigningFolderLeaf.js`.
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
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
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
 * Registers the signing folder leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
class RegisterSigningFolderLeafListener implements IEventListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-signing-folder';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FileSign';

	/**
	 * The surfaces, written out so both halves can be compared.
	 *
	 * @var string[]
	 */
	public const SURFACES = [
		'user-dashboard',
		'app-dashboard',
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
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterLeafProvidersEvent === false) {
			return;
		}

		try {
			$descriptor = new LeafDescriptor(
				id: self::LEAF_ID,
				label: $this->l10n->t('Waiting for your signature'),
				icon: self::ICON,
				kinds: [LeafDescriptor::KIND_RENDER_SURFACE],
				requiredApp: Application::APP_ID,
				group: 'signing',
				surfaces: self::SURFACES,
				referenceType: self::LEAF_ID,
				renderMode: LeafDescriptor::RENDER_MODE_MOUNT,
			);

			$event->registerLeaf($descriptor, null);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq could not register the filinq-signing-folder leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

	}//end handle()
}//end class
