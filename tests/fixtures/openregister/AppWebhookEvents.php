<?php

/**
 * A recording stand-in for OpenRegister's AppWebhookEvents.
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
 * Records every declaration it is asked for.
 */
class AppWebhookEvents {

	/**
	 * The declarations received, as [dispatcher, event, name, description, category, payload].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	public static array $calls = [];

	/**
	 * Record a declaration.
	 *
	 * @param object $dispatcher  The dispatcher.
	 * @param string $event       The event class.
	 * @param string $name        The catalogue name.
	 * @param string $description The catalogue description.
	 * @param string $category    The catalogue category.
	 * @param string $payload     The payload method.
	 *
	 * @return bool Always true.
	 */
	public static function declare(
		object $dispatcher,
		string $event,
		string $name,
		string $description,
		string $category,
		string $payload='getPayload'
	): bool {
		self::$calls[] = [$dispatcher, $event, $name, $description, $category, $payload];
		return true;
	}//end declare()
}//end class
