<?php

/**
 * Filinq MountedLeafListener.
 *
 * Shared half of the listeners that contribute one Filinq leaf to
 * OpenRegister's cross-app leaf catalogue (`RegisterLeafProvidersEvent`,
 * ADR-066). Each leaf is a render surface in mount mode; a listener names its
 * id, icon, group and surfaces as constants and its label in label().
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

use OCA\Filinq\Kernel\AppIdentity;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Registers the leaf a subclass describes.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec exclude shared registration plumbing; each leaf is specified with its own listener
 */
abstract class MountedLeafListener implements IEventListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = '';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = '';

	/**
	 * The catalogue group the leaf is listed under.
	 *
	 * @var string
	 */
	public const GROUP = '';

	/**
	 * The surfaces, written out so both halves can be compared.
	 *
	 * @var string[]
	 */
	public const SURFACES = [];

	/**
	 * Constructor.
	 *
	 * @param IL10N $l10n The translator for the label.
	 * @param LoggerInterface $logger Where a failed registration is reported.
	 */
	public function __construct(
		protected readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The label the host shows.
	 *
	 * @return string The translated label.
	 */
	abstract protected function label(): string;

	/**
	 * Add the leaf to the catalogue. A failure is logged and loses only this
	 * leaf, never the whole catalogue.
	 *
	 * Filinq is Vue 3 and a consuming host may still be Vue 2.7, so the JS half
	 * hands over a bare DOM element and roots its own app there. The server half
	 * must name the same mode under the shared id or the host renders the leaf
	 * the wrong way.
	 *
	 * @param Event $event The collect-event.
	 *
	 * @return void
	 *
	 * @spec exclude shared registration plumbing; each leaf is specified with its own listener
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterLeafProvidersEvent === false) {
			return;
		}

		try {
			$descriptor = new LeafDescriptor(
				id: static::LEAF_ID,
				label: $this->label(),
				icon: static::ICON,
				kinds: [LeafDescriptor::KIND_RENDER_SURFACE],
				requiredApp: AppIdentity::APP_ID,
				group: static::GROUP,
				surfaces: static::SURFACES,
				referenceType: static::LEAF_ID,
				renderMode: LeafDescriptor::RENDER_MODE_MOUNT,
			);

			$event->registerLeaf($descriptor, null);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq could not register the ' . static::LEAF_ID . ' leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try
	}//end handle()
}//end class
