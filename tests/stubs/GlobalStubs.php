<?php

/**
 * Global-namespace stubs for unit tests
 *
 * Loaded first by bootstrap-unit.php. Provides global stubs that cannot
 * live inside a namespace block (e.g. \OC) and additional OCP interfaces
 * used by new production classes.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

/**
 * Stub for \OC global class used by OCP\Server::get()
 *
 * HealthController calls Server::get() which delegates to \OC::$server->get().
 * In unit tests we set \OC::$server to an anonymous object that throws so that
 * the catch (\Exception $e) branch in the controller is exercised cleanly.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class OC {

	/**
	 * @var object|null
	 */
	public static ?object $server = null;

}//end class
