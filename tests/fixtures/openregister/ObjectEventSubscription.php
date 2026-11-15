<?php

/**
 * A recording stand-in for OpenRegister's ObjectEventSubscription.
 *
 * Loaded only by tests that run in their own process, so the class never
 * exists for the rest of the suite.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Event;

/**
 * Records every subscription it is asked for.
 */
class ObjectEventSubscription {

	/**
	 * The subscriptions received, as [dispatcher, event, listener, registers, schemas].
	 *
	 * @var array<int, array{0: object, 1: string, 2: string, 3: array<int, string>, 4: array<int, string>}>
	 */
	public static array $calls = [];

	/**
	 * Record a subscription.
	 *
	 * @param object             $dispatcher The event dispatcher.
	 * @param string             $event      The event class.
	 * @param string             $listener   The listener service.
	 * @param array<int, string> $registers  The registers to narrow to.
	 * @param array<int, string> $schemas    The schemas to narrow to.
	 *
	 * @return void
	 */
	public static function subscribe(object $dispatcher, string $event, string $listener, array $registers, array $schemas): void {
		self::$calls[] = [$dispatcher, $event, $listener, $registers, $schemas];
	}//end subscribe()
}//end class
