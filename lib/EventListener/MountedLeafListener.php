<?php

/**
 * Filinq MountedLeafListener.
 *
 * Shared half of the listeners that contribute one Filinq leaf to
 * OpenRegister's cross-app leaf catalogue (`RegisterLeafProvidersEvent`,
 * ADR-066). A listener writes its descriptor out in full, so the
 * integration-parity gate can read it; this base registers it and logs a
 * failure.
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
	 * The descriptor to register.
	 *
	 * @return LeafDescriptor The leaf.
	 */
	abstract protected function descriptor(): LeafDescriptor;

	/**
	 * Add the leaf to the catalogue. A failure is logged and loses only this
	 * leaf, never the whole catalogue.
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
			$event->registerLeaf($this->descriptor(), null);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq could not register the ' . static::LEAF_ID . ' leaf: ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try
	}//end handle()
}//end class
