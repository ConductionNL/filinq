<?php

/**
 * Filinq Register Templates Leaf Listener
 *
 * Registers the `filinq-templates` data-provider leaf with OpenRegister: the
 * descriptor and the app-local provider that lists the templates applying to
 * a host object. Wired by IntegrationLeafRegistrar behind its class_exists()
 * guard, so Filinq boots without OpenRegister's leaf API.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Integration\TemplatesLeafProvider;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafAvailability;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCA\OpenRegister\Service\Integration\LeafIdentity;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Registers the templates data-provider leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
 */
class RegisterTemplatesLeafListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param TemplatesLeafProvider $provider The data half.
	 * @param IL10N                 $l10n     Translates the label.
	 * @param LoggerInterface       $logger   The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplatesLeafProvider $provider,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Register the leaf and its provider.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.1
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterLeafProvidersEvent === false) {
			return;
		}

		try {
			$event->registerLeaf(
				new LeafDescriptor(
					identity: new LeafIdentity(
						id: TemplatesLeafProvider::ID,
						label: $this->l10n->t('Templates'),
						icon: TemplatesLeafProvider::ICON,
						group: TemplatesLeafProvider::GROUP,
					),
					kinds: [LeafDescriptor::KIND_DATA_PROVIDER],
					availability: new LeafAvailability(requiredApp: AppIdentity::APP_ID),
				),
				$this->provider
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq could not register the ' . TemplatesLeafProvider::ID . ' leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

	}//end handle()
}//end class
