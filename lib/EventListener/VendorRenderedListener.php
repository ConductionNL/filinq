<?php

/**
 * Vendor Rendered Listener
 *
 * Hears integriq's `DocumentRenderedEvent`, the outcome of a render a
 * document generation source made, and hands it to VendorRenderOutcome to
 * settle filinq's pending generatedDocument. The event is integriq's class,
 * reached by name so filinq runs without integriq; its readers are looked up
 * at runtime.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\Vendor\VendorRenderOutcome;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Settles a vendor render when integriq reports its outcome.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
 */
class VendorRenderedListener implements IEventListener {

	/**
	 * integriq's outcome event, as a string literal so filinq boots without integriq.
	 *
	 * @var string
	 */
	public const RENDERED_EVENT = 'OCA\\Integriq\\Event\\DocumentRenderedEvent';

	/**
	 * Constructor.
	 *
	 * @param VendorRenderOutcome $outcome Settles the pending entry.
	 */
	public function __construct(
		private readonly VendorRenderOutcome $outcome,
	) {

	}//end __construct()

	/**
	 * Settle the render the event reports on.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
	 */
	public function handle(Event $event): void {
		if (method_exists($event, 'getJobId') === false || method_exists($event, 'getStatus') === false) {
			return;
		}

		$error = '';
		if (method_exists($event, 'getError') === true) {
			$error = (string) $event->getError();
		}

		$this->outcome->settle(
			jobId: (string) $event->getJobId(),
			status: (string) $event->getStatus(),
			error: $error
		);

	}//end handle()
}//end class
